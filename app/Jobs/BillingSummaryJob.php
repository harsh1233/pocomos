<?php

namespace App\Jobs;

use PDF;
use Illuminate\Bus\Queueable;
use App\Models\Pocomos\PocomosJob;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Pocomos\PocomosInvoice;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosOfficeAlert;
use App\Models\Pocomos\PocomosPhoneNumber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class BillingSummaryJob implements ShouldQueue
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
        // $this->miscInvoiceIds = $miscInvoiceIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Unpaid invoice Job Started For Export");

        $pdf = PDF::loadView('pdf.blank');
        $pdf = $pdf->download('blank_' . strtotime('now') . '.pdf');
        // dd($this->args);
        $args = $this->args;

        $jobIds = $args['job_ids'];
        $miscInvoiceIds = $args['misc_invoice_ids'];

        $ids = array_merge($jobIds, $miscInvoiceIds);
        // dd($ids);
        sort($ids);

        $billingSummaryPath = $this->getBillingSummaryFilename(implode(',', $ids));

        $matches = array();
        preg_match('/(?<=BillingSummary\/)([^.]*)/', $billingSummaryPath, $matches);
        $billingSummaryHash = $matches[0];
        if (file_exists($billingSummaryPath)) {
            return $billingSummaryHash;
        }
        // $jobIds = [50888888];
        // dd($jobIds);

        $jobs = PocomosJob::find($jobIds);
        // dd($jobs);

        $miscInvoices = PocomosInvoice::
        // select(
        //     '*',
        //     'pocomos_invoices.id as invoice_id'
        // )
            join('pocomos_pest_contracts_invoices as ppci', 'pocomos_invoices.id', 'ppci.invoice_id')
            ->join('pocomos_pest_contracts as ppc', 'ppci.pest_contract_id', 'ppc.id')

            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
            ->join('pocomos_company_offices as pco', 'pag.office_id', 'pco.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->whereIn('invoice_id', $miscInvoiceIds)
            ->get();

            // dd(11);
        $invoices = array();
        foreach ($jobs as $job) {
            $invoice = $job->invoice;
            // dd($invoice);
            $pdf = $this->createBuilder_PdfFactory('Invoice', $invoice);

            $invoices[] = $pdf;
        }
// dd(99);
        foreach ($miscInvoices as $invoice) {
            $pdf = $this->createBuilder_PdfFactory('Invoice', $invoice);
            $invoices[] = $pdf;
        }

        // $pdf = array_shift($invoices);
        $finalPdf = $pdf;

        // foreach ($invoices as $invoice) {
        //     $invoice = PdfDocument::parse($invoice);

        //     foreach ($invoice->pages as $page) {
        //         $finalPdf->pages[] = clone $page;
        //     }
        // }
        // dd(11);
        // $file = $this->fileContentBaseUploadS3(config('constants.INVOICES'), $finalPdf, implode('_', $ids));

        //////

        $parameters = $this->args;
        $url =  "unpaid_invoices/"."billing_summary_" .strtotime('now').'.pdf';

        Storage::disk('s3')->put($url, $pdf, 'public');
        $path = Storage::disk('s3')->url($url);
        $res['url'] = $path;
        $status = true;
        $message = __('strings.details', ['name' => 'Individual unpaid invoice']);
        // $res['data'] = $data;


        //Create Alert
        $alert_details['name'] = 'Billing Summary';
        $alert_details['description'] = 'The Billing Summary has completed successfully<br><br><a href="'.$res['url'].'">Download Billing Summary</a>';
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

        Log::info("Unpaid individual invoice Job End For Export");
    }

    public function getBillingSummaryFilename($jobIds)
    {
        return $this->ensureDirectoryExists($this->ensureDirectoryExists(config('constants.INTERNAL_PATH') . '/invoices') . DIRECTORY_SEPARATOR . 'BillingSummary') . DIRECTORY_SEPARATOR . md5($jobIds) . ".pdf";
        // return $this->ensureDirectoryExists(config('constants.INTERNAL_PATH') . DIRECTORY_SEPARATOR . 'contracts');
    }



    /* public function buildPdf_InvoiceType($invoice)
    {
        // dd($invoice->contract);

        $pdf = $this->getInvoiceBasePdf($invoice);

        return $pdf;

    } */

    public function getActivePhones($phoneIds)
    {
        return PocomosPhoneNumber::whereIn('id', $phoneIds)->whereActive(1)->get();
    }
}
