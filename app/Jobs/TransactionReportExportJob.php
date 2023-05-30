<?php

namespace App\Jobs;

use DB;
use Excel;
use Illuminate\Bus\Queueable;
use App\Exports\ExportEmailReport;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Exports\ExportPaymentReport;
use App\Models\Pocomos\PocomosAlert;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosOfficeAlert;
use App\Exports\ExportFinancialTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;

class TransactionReportExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $data = $this->data;
        // Excel::download(new ExportEmailReport($data), 'ExportEmailReport.csv');
        // dd(11);
        Log::info("transaction report export job started");

        $data = $this->data;

        $filename = 'financial_transactions_'.strtotime(date('Y-m-d H:i:s')).'.csv';
        // open csv file for writing
        $f = fopen($filename, 'w');
        // write each row at a time to a file
        foreach ($data as $row) {
            $row = (array)$row;
            fputcsv($f, $row);
        }
        // close the file
        fclose($f);

        $url =  'Exports/' . $filename;
        Storage::disk('s3')->put($url, file_get_contents($filename), 'public');
        $filePath = Storage::disk('s3')->url($url);

        $fileSize = Storage::disk('s3')->size($url);

        // dd($path);

        //Create Alert
        $alert_details['name'] = 'Transactions report';
        $alert_details['description'] = 'The Transactions report export has completed successfully<br><br><a href="'.$filePath.'">Download Transactions report </a>';
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

        Log::info("transaction report export ended");
    }
}
