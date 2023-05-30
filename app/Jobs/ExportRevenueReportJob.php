<?php

namespace App\Jobs;

use DB;
use Excel;
use Illuminate\Bus\Queueable;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosAlert;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use PDF;
use Illuminate\Support\Facades\Storage;
use App\Exports\ExportRevenueReport;

class ExportRevenueReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $results;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($results)
    {
        $this->results = $results;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Revenue report Job Started For Export");

        $data = $this->results;
        $url =  "revenue_report/" .preg_replace('/[^A-Za-z0-9\-]/', '', strtotime(date('Y-m-d H:i:s'))).'.csv';
        // $pdf = PDF::loadView('pdf.individual_unpaid_invoice', compact('data'));
        $excel = Excel::download(new ExportRevenueReport($data), 'ExportRevenueReport.csv');

        /*
        Storage::disk('s3')->put($url, $excel, 'public');
        $path = Storage::disk('s3')->url($url);
        $res['url'] = $path;
        $status = true;
        $message = __('strings.details', ['name' => 'Revenue report']);
        $res['data'] = $data;
        */

        // dd($path);

        /*
        //Create Alert
        $alert_details['name'] = 'Revenue report';
        $alert_details['description'] = 'The Revenue report export has completed successfully<br><br><a href="'.$res['url'].'">Download  Revenue report </a>';
        $alert_details['status'] = 'Posted';
        $alert_details['priority'] = 'Success';
        $alert_details['type'] = 'Alert';
        $alert_details['date_due'] = null;
        $alert_details['active'] = true;
        $alert_details['notified'] = false;

        PocomosAlert::create($alert_details);
        */

        Log::info("Revenue report Job End For Export");
    }
}
