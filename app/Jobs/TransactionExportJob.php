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

class TransactionExportJob implements ShouldQueue
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
        Log::info("TransactionExportJob Job Started");

        $officeId = $this->args['officeId'];
        $fileHash = $this->args['hash'];
        $filters = unserialize($this->args['filters']);
        $salespeople = unserialize($this->args['salespeople']);
        $office = PocomosCompanyOffice::where('id', $officeId)->whereActive(true)->firstOrFail();
        $results = $this->getTransactionInvoices($office, $filters, $salespeople);
        /**CSV heading data */
        $heading = array(
            'Customer Name',
            'Street',
            'City',
            'Zipcode',
            'State',
            'Invoice #',
            'Invoice Due Date',
            'Initial Service Date',
            'Contract Creation Date',
            'Payment Date',
            'Payment Method',
            'Payment Type',
            'Payment Amount',
            'Pre Tax Amount',
            'Tax Rate',
            'Payment Status',
            'Reference Number',
            'Account Id',
            'Service type',
            'First Year Contract Value',
            'Autopay' ,
            'Job Type',
            'Agreement Name',
            'Contract Start Date',
        );

        $res = array();
        $res[] = $heading;

        /**CSV content data */
        foreach ($results as $value) {
            $value = (array)$value;
            $row = array();

            $row[] = $value['name'];
            $row[] = $value['custStreet'];
            $row[] = $value['custCity'];
            $row[] = $value['custPostalCode'];
            $row[] = $value['custState'];
            $row[] = $value['id'];
            $row[] = $value['dateDue'];
            $row[] = $value['initialServiceDate'];
            $row[] = $value['contractCreationDate'];
            $row[] = $value['paymentDate'];
            $row[] = $value['paymentType'];
            $row[] = $value['actualPaymentType'];
            $row[] = $value['paymentAmount'];
            $row[] = $value['preTaxAmount'];
            $row[] = $value['taxRate'];
            $row[] = $value['paymentStatus'];
            $row[] = $value['refNumber'];
            $row[] = $value['custAcctId'];
            $row[] = $value['service_type_name'];
            $row[] = $value['first_year_contract_value'];
            $row[] = $value['autopay'];
            $row[] = $value['jobtype'];
            $row[] = $value['agreement_name'];
            $row[] = $value['initialdate'];

            $res[] = $row;
        }

        $filename = 'TransactionsExport_'.strtotime(date('Y-m-d H:i:s')).'.csv';
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

        $transactionsFile = OrkestraFile::create([
            'path' => $filePath, 'filename' => $filename, 'mime_type' => 'text/csv', 'file_size' => 0, 'active' => true, 'md5_hash' => $fileHash, 'date_created' => date('Y-m-d H:i:s')
        ]);

        $alert_details['name'] = 'Transaction Export';
        $alert_details['description'] = 'The Transaction export has completed successfully<br><br><a href="'.$filePath.'">Download  Export File </a>';
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
        Log::info("TransactionExportJob Job End");
    }
}
