<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosJob;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosCustomersNote;
use App\Models\Pocomos\PocomosCustomerState;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosInvoice;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosTaxCode;
use Carbon\Carbon;
use App\Models\Pocomos\PocomosPestContractsPest;
use App\Models\Pocomos\PocomosCustomersFile;
use App\Models\Pocomos\PocomosSubCustomer;
use App\Models\Pocomos\PocomosInvoiceTransaction;
use App\Models\Orkestra\OrkestraTransaction;
use App\Models\Pocomos\PocomosOfficeOpiniionSetting;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosPestInvoiceSetting;
use App\Models\Pocomos\PocomosAgreement;
use PDF;
use App\Models\Pocomos\PocomosPestContractsSpecialtyPest;
use App\Models\Pocomos\PocomosPest;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Pocomos\PocomosPestEstimates;
use App\Models\Pocomos\PocomosPestEstimateProducts;
use App\Models\Pocomos\PocomosCustomersNotifyMobilePhone;

class DownloadController extends Controller
{
    use Functions;

    /**
     * API for download service record
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function serviceRecordAction()
    {
        $PocomosCustomer = PocomosCustomer::where('id', $_GET['customer_id'])->firstOrFail();

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $pestConfig = PocomosPestOfficeSetting::where('office_id', $_GET['office_id'])->firstOrFail();

        if (!$pestConfig) {
            return $this->sendResponse(false, 'Unable to find Office Settings.');
        }

        $job = PocomosJob::where('id', $_GET['job_id'])->latest('date_created')->firstOrFail();

        $pest_contract = PocomosPestContract::where('id', $job->contract_id)->firstOrFail();

        $contracts = PocomosContract::where('id', $pest_contract->contract_id)->firstOrFail();

        $profile = PocomosCustomerSalesProfile::where('id', $contracts->profile_id)->firstOrFail();

        if ((!$job) || ($profile->customer_id != $_GET['customer_id'])) {
            return $this->sendResponse(false, 'Unable to find job.');
        }

        $invoice = PocomosInvoice::findOrFail($job->invoice_id);

        $profile = $invoice->contract->profile_details;
        $contract = $invoice->contract;
        $office = $profile->office_details;
        $customer = $invoice->contract->profile_details->customer_details;
        $invoiceConfig = PocomosPestInvoiceSetting::where('office_id', $office->id)->firstOrFail();
        $customerState = PocomosCustomerState::where('customer_id', $customer->id)->firstOrFail();

        $outstandingBalance = $customerState->balance_outstanding ?? 00;

        if ($invoiceConfig->use_legacy_layout) {
            // Use the old generator
            $params = array(
                'office' => $office,
                'customer' => $contract->profile_details->customer_details,
                'agreement' => $contract->agreement_details,
                'pest_contract' => $contract->pest_contract_details,
                'invoice' => $invoice,
                'job' => $job,
            );

            $pdf = $this->legacyInvoiceGeneratorGenerate($params);
        } else {
            $serviceCustomer = $billingCustomer = $profile->customer_details;
            $serviceContract = $billingContract = $contract->pest_contract_details;
            $agreement = $contract->agreement_details;
            $invoiceIntro = $this->renderDynamicTemplate($agreement->invoice_intro, null, $serviceCustomer, $serviceContract, null, true);

            $parameters = array(
                'serviceCustomer' => $serviceCustomer,
                'serviceContract' => $serviceContract,
                'invoice' => $invoice,
                'job' => $job,
                'office' => $office,
                'invoiceConfig' => $invoiceConfig,
                'invoiceIntro' => $invoiceIntro,
            );

            $pdf = PDF::loadView('pdf.service_record', compact('parameters'));
        }


        // return $pdf->download('service_record_' . $_GET['customer_id'] . '.pdf');

        $url = "service_history/" . 'invoice_' . $invoice->id . '_' . strtotime('now') . '.pdf';

        Storage::disk('s3')->put($url, $pdf->output(), 'public');

        $path = Storage::disk('s3')->url($url);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'invoice pdf']), $path);
    }


    /*  API for download invoice */

    public function invoiceAction()
    {
        $PocomosCustomer = PocomosCustomer::where('id', $_GET['customer_id'])->firstOrFail();

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $pestConfig = PocomosPestOfficeSetting::where('office_id', $_GET['office_id'])->firstOrFail();

        if (!$pestConfig) {
            return $this->sendResponse(false, 'Unable to find Office Settings.');
        }

        $job = PocomosJob::where('id', $_GET['job_id'])->firstOrFail();

        $pest_contract = PocomosPestContract::where('id', $job->contract_id)->firstOrFail();

        $contracts = PocomosContract::where('id', $pest_contract->contract_id)->firstOrFail();

        $profile = PocomosCustomerSalesProfile::where('id', $contracts->profile_id)->firstOrFail();

        // return $job->pest_contract->contract;
        // return $job->pest_contract->contract->profile_details->customer->id;

        if (!$job || ($job->pest_contract->contract->profile_details->customer->id != $_GET['customer_id'])) {
            return $this->sendResponse(false, 'Unable to find job.');
        }

        $contracts = PocomosContract::where('profile_id', $profile->id)->firstOrFail();

        $poNumber = '';

        if ($contracts->active == 1) {
            $poNumber = $contracts->purchase_order_number;
        }

        $invoice = PocomosInvoice::where('id', $job->invoice_id)->firstOrFail();

        // $office = PocomosCompanyOffice::where('id', $profile->office_id)->firstOrFail();

        // $invoiceConfig = PocomosPestInvoiceSetting::where('office_id', $profile->office_id)->firstOrFail();

        // if ($invoiceConfig->use_legacy_layout == 1) {
        // }

        // $mobile = PocomosCustomersNotifyMobilePhone::where('profile_id', $profile->id)
        //     ->firstOrFail();

        // if ($mobile) {
        //     $customerPhone = PocomosPhoneNumber::where('id', $mobile->phone_id)->firstOrFail();
        // }

        // $outstandingBalance = $this->outstandingBalance(array($profile->customer_id));

        // $taxCode = PocomosTaxCode::where('id', $contracts->tax_code_id)->firstOrFail();

        // $products = array(); // We'll only load these if we need them.

        // $serviceCustomer = $billingCustomer = $PocomosCustomer;

        // $serviceContract = $billingContract = $pest_contract;

        // $technicianSignature = false;

        // if (!($job->status == 'Complete')) {

        //     $products = PocomosPestProduct::where('office_id', $profile->office_id)->firstOrFail();
        // }

        // if (is_array($products)) {
        //     $products = array_slice($products, 0, 7);
        // }

        // $pdf = PDF::loadView('pdf.PaymentSummary', ['payslip' => $payment_history]);

        // return 11;
        $pdf = $this->getInvoiceBasePdf($invoice);

        $url =  "reminder/" . 'invoice_' . strtotime("now") . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output(), 'public');
        $path = Storage::disk('s3')->url($url);

        return $this->sendResponse(true, 'Invoice pdf', $path);
        // return $pdf->download('invoice_111.pdf');


        // return $pdf->download('invoice_' . $_GET['customer_id'] . '.pdf');
    }


    /*  API for download misc invoice */

    public function miscInvoiceAction()
    {
        $PocomosCustomer = PocomosCustomer::where('id', $_GET['customer_id'])->firstOrFail();

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $pestConfig = PocomosPestOfficeSetting::where('office_id', $_GET['office_id'])->firstOrFail();

        if (!$pestConfig) {
            return $this->sendResponse(false, 'Unable to find Office Settings.');
        }

        $invoice = PocomosInvoice::where('id', $_GET['invoice_id'])->firstOrFail();

        if (!$invoice) {
            return $this->sendResponse(false, 'Unable to find the Invoice.');
        }

        $profile = $PocomosCustomer->sales_profile;

        $profile = $this->getInvoiceProfile($invoice, $profile);

        $pdf = $this->getInvoiceBasePdf($invoice);

        $url =  "misc_invoices/" . 'invoice_' . $invoice->id .'_'. strtotime("now") . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output(), 'public');
        $path = Storage::disk('s3')->url($url);

        return $this->sendResponse(true, 'Misc Invoice pdf', $path);
        //return $pdf->download('invoice_' . $_GET['customer_id'] . '.pdf');
    }

    /**
     * API for download_customer_estimate_record
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function estimateDownloadCustomerAction($customer_id, $estimate_id, $print)
    {
        $PestEstimates = PocomosPestEstimates::findOrFail($estimate_id);

        if (!$PestEstimates) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Estimate']));
        }

        $office = PocomosCompanyOffice::where('id', $PestEstimates->office_id)->firstOrFail();

        $invoiceConfig = PocomosPestInvoiceSetting::where('office_id', $PestEstimates->office_id)->firstOrFail();

        $products =  PocomosPestEstimateProducts::where('estimate_id', $estimate_id)->get();

        $data = PocomosCustomer::where('id', $PestEstimates->customer_id)->firstOrFail();

        $pdf_data['invoice_config'] = $invoiceConfig;
        $pdf_data['estimate'] = $PestEstimates;
        $pdf_data['office'] = $office;
        $pdf_data['service_customer'] = $data;
        $pdf_data['products'] = $products;

        $pdf = PDF::loadView('pdf.estimate_report_pdf', $pdf_data);

        //upload invoice in s3
        $url = 'Estimate/' . $estimate_id . '_invoice' . mt_rand(1000000000, time()) . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output());
        $url = Storage::disk('s3')->url($url);

        if ($print == 1) {
            return $this->sendResponse(true, 'URL', $url);
        }

        return $pdf->download($estimate_id . '_EstimateInvoice.pdf');
    }


    /**
     * API for customer payment history download
     */

    public function customerPaymentHistoryDownloadAction($customer_id)
    {
        $PocomosCustomer = PocomosCustomer::findOrFail($customer_id);

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $customer_id)->firstOrFail();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to locate Customer Sales Profile.');
        }

        $office = PocomosCompanyOffice::where('id', $profile->office_id)->firstOrFail();

        $payment_history = DB::select(DB::raw("SELECT *, prc.*,oa.alias,rsf.invoice_id
        FROM pocomos_customer_sales_profiles AS pr
        JOIN pocomos_customers_accounts AS pca ON pca.profile_id  = pr.id
        JOIN orkestra_transactions AS prc ON prc.account_id  = pca.account_id
        JOIN pocomos_user_transactions AS rsf ON  rsf.transaction_id = prc.id
        JOIN orkestra_accounts AS oa ON  oa.id = pca.account_id

        left JOIN orkestra_users AS prc4 ON rsf.user_id  = prc4.id
        WHERE pr.id = '$profile->id'  GROUP BY rsf.transaction_id"));

        $office = $profile->office_details;

        $pdf_data['userTransactions'] = $payment_history;
        $pdf_data['office'] = $office;

        $pdf = PDF::loadView('pdf.PaymentHistoryList', $pdf_data);

        //upload invoice in s3
        $url = 'PaymentHistory/' . $customer_id . '_payment_history' . mt_rand(1000000000, time()) . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output());
        $url = Storage::disk('s3')->url($url);

        return $pdf->download('payment_history_' . $customer_id . '.pdf');
    }

    /*  API for download_service_history */

    public function serviceHistoryAction(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'contract_id' => 'required|exists:pocomos_jobs,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        $customer = $this->findOneByIdAndOffice_customerRepo($custId, $officeId);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find Customer.']));
        }

        $pestContract = PocomosPestContract::findOrFail($request->contract_id);

        // return 11;
        // return $pestContract->contract->profile_details->customer;

        if (!$pestContract || $pestContract->contract->profile_details->customer->id !== $customer->id) {
            return $this->sendResponse(false, 'Unable to find contract.');
        }

        $params = ['contract' => $pestContract];

        $pdf = $this->doGenerateServiceHistoryGenerator($params);

        $url = "service_history/" . 'service_history_' . strtotime('now') . '.pdf';

        Storage::disk('s3')->put($url, $pdf, 'public');

        $path = Storage::disk('s3')->url($url);

        return $this->sendResponse(true, 'Service_history export summary', $path);
    }
}
