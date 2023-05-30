<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Orkestra\OrkestraFile;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosOfficeAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class UnpaidInvoicesExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $args;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($args)
    {
        $this->args = $args;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("UnpaidInvoicesExportJob Job Started");
        $officeId = $this->args['officeId'];
        $fileHash = $this->args['hash'];

        $office = PocomosCompanyOffice::where('id', $officeId)->whereActive(true)->firstOrFail();

        // $filePath = $this->getFileHelper()->getUnpaidExportPathFromHash($fileHash);
        $terms = unserialize($this->args['terms']);

        $res = $this->getUnpaidInvoices($office, $terms);

        $filename = 'unpaid_export_'.strtotime(date('Y-m-d H:i:s')).'.csv';
        // open csv file for writing
        $f = fopen($filename, 'w');
        // write each row at a time to a file
        foreach ($res as $row) {
            $row = (array)$row;
            fputcsv($f, $row);
        }
        // close the file
        fclose($f);

        $url =  'Exports/' . $filename;
        Storage::disk('s3')->put($url, file_get_contents($filename), 'public');
        $filePath = Storage::disk('s3')->url($url);

        $fileSize = Storage::disk('s3')->size($url);

        $transactionsFile = OrkestraFile::create([
            'path' => $filePath, 'filename' => $filename, 'mime_type' => 'text/csv', 'file_size' => $fileSize, 'active' => true, 'md5_hash' => $fileHash, 'date_created' => date('Y-m-d H:i:s')
        ]);

        $alert_details['name'] = ' Unpaid Invoices Export';
        $alert_details['description'] = 'The Unpaid Invoices Export has completed successfully<br><br><a href="'.$filePath.'">Download Unpaid Invoices Export</a>';
        $alert_details['status'] = 'Posted';
        $alert_details['priority'] = 'Success';
        $alert_details['type'] = 'Alert';
        $alert_details['date_due'] = null;
        $alert_details['active'] = true;
        $alert_details['notified'] = false;
        $alert = PocomosAlert::create($alert_details);

        $office_alert_details['alert_id'] = $alert->id;
        $office_alert_details['assigned_by_user_id'] = auth()->user()->pocomos_company_office_user->id ?? null;
        $office_alert_details['assigned_to_user_id'] = auth()->user()->pocomos_company_office_user->id ?? null;
        $office_alert_details['active'] = true;
        $office_alert_details['date_created'] = date('Y-m-d H:i:s');
        PocomosOfficeAlert::create($office_alert_details);

        unlink($filename);

        Log::info("UnpaidInvoicesExportJob Job End");
    }
}
