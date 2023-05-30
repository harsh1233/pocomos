<?php

namespace App\Jobs;

use DB;
use PDF;
use Excel;
use Illuminate\Bus\Queueable;
use App\Exports\ExportCustomer;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosAlert;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosOfficeAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;

class PaidInvoiceSummary implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $miscInvoices;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($miscInvoices)
    {
        $this->miscInvoices = $miscInvoices;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Paid invoice Job Started For Export");

        $data = $this->miscInvoices;
        $url =  "paid_invoice_summary/" .preg_replace('/[^A-Za-z0-9\-]/', '', strtotime(date('Y-m-d H:i:s'))).'.pdf';
        $pdf = PDF::loadView('pdf.paid_invoice_summary', compact('data'));

        Storage::disk('s3')->put($url, $pdf->output(), 'public');
        $path = Storage::disk('s3')->url($url);
        $res['url'] = $path;
        $status = true;
        $message = __('strings.details', ['name' => 'Paid invoice summary']);
        $res['data'] = $data;

        //Create Alert
        $alert_details['name'] = 'Paid invoice summary';
        $alert_details['description'] = 'The Paid invoice summary export has completed successfully<br><br><a href="'.$res['url'].'">Download Paid invoice summary </a>';
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

        Log::info("Paid invoice  Job End For Export");
    }
}
