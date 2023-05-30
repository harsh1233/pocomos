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
use App\Models\Pocomos\PocomosPestContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;

class ServiceHistorySummaryJob implements ShouldQueue
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
        Log::info("Unpaid invoice Job Started For Export");

        $args = $this->args;
        // dd($args);

        $pccIds = $args['pcc_ids'];
        $paid = $args['paid'];
        $hash = $args['hash'];

        $pccs = PocomosPestContract::find($pccIds);
        // dd($pccs);

        $pdf = PDF::loadView('pdf.blank');
        $summaryPdf = $pdf->download('blank_' . strtotime('now') . '.pdf');
        
        // $q=0;
        foreach ($pccs as $pcc) {
            // if(isset($pcc->contract->pest_contract_details)){

                $params = [
                    'contract' => $pcc,
                    'paid' => $paid,
                    'summary_only' => true,
                ];

                $summaryPdf = $this->createBuilder_PdfFactory('Billing Summary', $params);
            // }

            // $q++;
        }

        // dd($q);

        $url =  "unpaid_invoices/"."unpaid_service_history_summaries_" .strtotime('now').'.pdf';

        Storage::disk('s3')->put($url, $summaryPdf, 'public');
        $path = Storage::disk('s3')->url($url);
        $res['url'] = $path;
        $status = true;
        $message = __('strings.details', ['name' => 'Individual unpaid invoice']);

        //Create Alert
        $alert_details['name'] = ''.$paid.' Service History Summaries Completed';
        $alert_details['description'] = 'The '.$paid.' service history summaries have been successfully completed.
        <br><br><a href="'.$res['url'].'">Download '.$paid.' Service History Summaries</a>';

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

        Log::info("Unpaid invoice  Job End For Export");
    }
}
