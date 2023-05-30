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
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use Illuminate\Support\Facades\DB as FacadesDB;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Pocomos\PocomosCustomField;
use App\Models\Pocomos\PocomosOfficeAlert;
use App\Models\Pocomos\PocomosUserTransaction;

class InvoiceController extends Controller
{
    use Functions;

    /**
     * API for List of quick invoices
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * symphony - showAction InvoiceController
     */

    public function customerInvoiceShow(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'invoice_id' => 'required|exists:pocomos_invoices,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $invoice_id = PocomosInvoice::where('id', $request->invoice_id)->first();

        $contract = PocomosContract::where('id', $invoice_id->contract_id)->select('profile_id')->first();

        if ($profile->id != $contract->profile_id) {
            $parentRelationship = PocomosSubCustomer::where('child_id', $request->customer_id)->select('parent_id')->first();

            if ($parentRelationship) {
                $profile = PocomosCustomerSalesProfile::where('customer_id', $parentRelationship->parent_id)->first();
            }

            if ($contract->profile_id != $profile->id) {
                return $this->sendResponse(false, 'Unable to find the Invoice Entitiy.');
            }
        }

        $item = PocomosInvoiceItems::with('tax_code:id,tax_rate')->where('invoice_id', $request->invoice_id)->get();

        $chargesRefunds = PocomosUserTransaction::whereHas(
            'transactions'
        )->with('transactions.account_detail', 'user_details_name')->whereInvoiceId($request->invoice_id)->get();

        $add = DB::select(DB::raw("SELECT SUM((amount)/100) as amount FROM orkestra_transactions where account_id='$profile->points_account_id' AND type = 'Credit'"));

        $remove = DB::select(DB::raw("SELECT SUM((amount)/100) as amount FROM orkestra_transactions where account_id='$profile->points_account_id' AND type = 'Sale'"));

        $result = $add[0]->amount - $remove[0]->amount;

        $data = [
            'invoice_data' => $invoice_id,
            'invoice_item_data' => $item,
            'charges_refunds' => $chargesRefunds,
            'account_credit' => $result,
        ];

        return $this->sendResponse(true, 'Details of invoices', $data);
    }

    public function completeJobAction($custId, $jobid)
    {
        // return 11;

        $customer = PocomosCustomer::find($custId);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Customer.']));
        }

        $job = PocomosJob::find($jobid);

        if (!$job) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Job.']));
        }

        $profile = PocomosCustomerSalesProfile::whereCustomerId($custId)->first();

        $invoice = $job->invoice;

        $invoice->hasPayments = $invoice->hasPayments();

        $profile = $this->getInvoiceProfile($invoice, $profile);

        $invoiceItems = PocomosInvoiceItems::with('tax_code:id,tax_rate')->where('invoice_id', $invoice->id)->get();

        $chargesRefunds = PocomosUserTransaction::whereHas('transactions')->with('transactions.account_detail', 'invoice')
                                        ->whereInvoiceId($invoice->id)->get();

        $accountCredit = $profile->points_account->balance;

        $data = [
            'invoice_data' =>   $invoice,
            'invoice_item_data' => $invoiceItems,
            'charges_refunds' => $chargesRefunds,   // invoice.invoiceTransactions
            'account_credit' => $accountCredit,
        ];

        return $this->sendResponse(true, 'invoice details', $data);
    }


    public function Paymentprocess(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'invoice_id' => 'required|exists:pocomos_invoices,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'user_id' => 'required|exists:orkestra_users,id',
            'payments' => 'required|array',
            'payments.*.method' => 'required|in:card,ach,cash,check,account_credit,processed_outside,points',
            'payments.*.referenceNumber' => 'nullable',
            'payments.*.description' => 'nullable',
            'payments.*.amount' => 'required',
            'payments.*.account_id' => 'required|exists:orkestra_accounts,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();
        $payments = $request->payments;
        $generalValues = $request->only('customer_id', 'invoice_id', 'office_id', 'user_id');

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $invoice = PocomosInvoice::where('id', $request->invoice_id)->first();

        $contract = PocomosContract::where('id', $invoice->contract_id)->select('profile_id')->first();

        if ($profile->id != $contract->profile_id) {
            $parentRelationship = PocomosSubCustomer::where('child_id', $request->customer_id)->select('parent_id')->first();

            if ($parentRelationship) {
                $profile = PocomosCustomerSalesProfile::where('customer_id', $parentRelationship->parent_id)->first();
            }

            if ($contract->profile_id != $profile->id) {
                return $this->sendResponse(false, 'Unable to find the Invoice Entitiy.');
            }
        }

        foreach ($payments as $value) {
            $this->processPayment($request->invoice_id, $generalValues, $value, $request->user_id);
        }

        return $this->sendResponse(true, 'Payment has been processed successfully.');
    }

    /**
     * API for Refund transaction
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function refund(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'invoice_id' => 'required|exists:pocomos_invoices,id',
            'transaction_id' => 'required|exists:orkestra_transactions,id',
            'addBackToAmountDue' => 'required|boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'user_id' => 'nullable|exists:orkestra_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $PocomosInvoice = PocomosInvoice::where('id', $request->invoice_id)->firstOrFail();

        $contract = PocomosContract::where('id', $PocomosInvoice->contract_id)->select('profile_id')->first();

        if ($profile->id != $contract->profile_id) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        try {
            FacadesDB::beginTransaction();

            $result = $this->processRefund($request->invoice_id, $request->transaction_id, $request->user_id, $request->addBackToAmountDue, $request->office_id);

            FacadesDB::commit();
        } catch (\Exception $e) {
            FacadesDB::rollback();

            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        return $this->sendResponse(true, 'The transaction has been refunded.');
    }

    /**
     * API for payment Failed transaction
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function paymentFailed(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'invoice_id' => 'required|exists:pocomos_invoices,id',
            'transaction_id' => 'required|exists:orkestra_transactions,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $invoice_id = PocomosInvoice::where('id', $request->invoice_id)->first();

        $contract = PocomosContract::where('id', $invoice_id->contract_id)->select('profile_id')->first();

        if ($profile->id != $contract->profile_id) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        try {
            FacadesDB::beginTransaction();

            $result = $this->processFailedPayment($request->invoice_id, $request->transaction_id);

            FacadesDB::commit();
        } catch (\Exception $e) {
            FacadesDB::rollback();

            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        return $this->sendResponse(true, 'The transaction has been cancelled.', $result);
    }

    /**
     * API for payment process
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function editCharge(Request $request, $id)
    {
        $v = validator($request->all(), [
            'amount' => 'required',
            'description' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $transaction = OrkestraTransaction::findOrFail($id);
        $transaction->amount = $request->amount;
        $transaction->description = $request->description;
        $transaction->save();

        return $this->sendResponse(true, 'The invoice transaction has been updated successfully.');
    }

    /**
     * API for Verifies the invoices balance against the payments made.
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function verifyPayments(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'invoice_id' => 'required|exists:pocomos_invoices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $invoice = PocomosInvoice::where('id', $request->invoice_id)->first();

        $contract = PocomosContract::where('id', $invoice->contract_id)->select('profile_id')->first();

        if ($profile->id != $contract->profile_id) {
            $parentRelationship = PocomosSubCustomer::where('child_id', $request->customer_id)->select('parent_id')->first();

            if ($parentRelationship) {
                $profile = PocomosCustomerSalesProfile::where('customer_id', $parentRelationship->parent_id)->first();
            }

            if ($contract->profile_id != $profile->id) {
                return $this->sendResponse(false, 'Unable to find the Invoice Entitiy.');
            }
        }

        $balance = $invoice->balance;
        $amountDue = $invoice->amount_due;

        if ($balance < 0.1) {
            $paymentSum = 0;

            $invoicetransaction = PocomosInvoiceTransaction::where('invoice_id', $request->invoice_id)->get();

            foreach ($invoicetransaction as $transaction) {
                $transaction = OrkestraTransaction::where('id', $transaction->transaction_id)->first();

                $status = $transaction->status;

                if ($status == 'APPROVED') {
                    $transAmount = $transaction->amount;
                    $networktype = $transaction->network;
                    if ($networktype == 'POINTS') {
                        $transAmount = $transAmount / 100;
                    }
                    $paymentSum = $paymentSum + $transAmount;
                }
            }

            $finalAmount = ($amountDue * (1 + $invoice->sales_tax)) - $paymentSum;

            $input['balance'] = round($finalAmount, 2);
            $invoice->update($input);
        }

        return $this->sendResponse(true, 'Verified the invoices balance.');
    }


    /**
     * API for Shows a form / searches for and lists invoices
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function showInvoiceHistoryAction(Request $request)
    {
        $v = validator($request->all(), [
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'locations' => 'nullable|exists:pocomos_customers,id',
            'status' => 'nullable|in:Paid,Not sent,Sent,Due,Past due,Collections,In collections,Cancelled',
            'minAmount' => 'required|min:0|integer',
            'maxAmount' => 'required|min:0|integer',
            'includeChildren' => 'required|boolean',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $opinionsetting = PocomosOfficeOpiniionSetting::where('active', 1)->where('office_id', $request->office_id)->first();

        if (isset($request->startDate)) {
            //$startDate =  date('Y-m-d', strtotime($request['startDate']));
            $startDate = $request->startDate;
            //$today = date('Y-m-d', strtotime(date('Y-m-d')));
        }
        if (isset($request->endDate)) {
            $endDate = $request->endDate;
        }

        $status = $request->status ?? null;

        if (isset($request->minAmount)) {
            $minAmount = $request->minAmount;
        }
        if (isset($request->maxAmount)) {
            $maxAmount = $request->maxAmount;
        }

        $locations = $request->locations ?? null;

        if (isset($request->includeChildren)) {
            $includeChildren = $request->includeChildren;
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->select('id')->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $contract = PocomosContract::where('profile_id', $profile->id)->get();

        if ($locations) {
            // return 88;
            $pestControlContracts = $this->findAllBillableContractsByLocation($request->customer_id, $locations, $includeChildren);
        } else {
            $pestControlContracts = $this->findAllBillableContractsByCustomer($request->customer_id, $includeChildren);
        }

        $pestControlContract = [];
        foreach ($pestControlContracts as $key => $value) {
            $pestControlContract[] = $value->id;
        }

        // return $pestControlContract;

        $pestControlContractIds = PocomosPestContract::whereIn('contract_id', $pestControlContract)->pluck('id')->toArray();

        // $sp = [
        //     'page' => $request->page,
        //     'perPage' => $request->perPage,
        //     'search' => $request->search
        // ];

        $jobsQuery = $this->getInvoiceHistoryJobs($startDate, $endDate, $pestControlContractIds, $minAmount, $maxAmount, $status);

        if ($request->search) {
            $search = '%' . $request->search . '%';

            $formatDate = date('Y-m-d', strtotime($request->search));
            $date = '%' . $formatDate . '%';

            // dd($date);

            $jobsQuery->where(function ($j) use ($search, $date) {

                $j->where('pocomos_jobs.invoice_id', 'like', $search)
                    ->orWhere('pi.date_due', 'like', $date)
                    ->orWhere('street', 'like', $search)
                    ->orWhere('pocomos_jobs.type', 'like', $search)
                    ->orWhere('pi.amount_due', 'like', $search)
                    ->orWhere('pi.balance', 'like', $search)
                    ->orWhere('pi.status', 'like', $search)
                    ->orWhere('pag.name', 'like', $search)
                    ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $search);
            });
        }

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $jobsCount = $jobsQuery->count();
        $jobsQuery->skip($perPage * ($page - 1))->take($perPage);
        $jobs = $jobsQuery->get();


        $invoicesQuery = $this->getInvoiceHistoryInvoices($startDate, $endDate, $pestControlContractIds, $minAmount, $maxAmount, $status);

        if (($page * $perPage) - $jobsCount <= 0) {
            // return $jobsCount;
            $invoices = collect();
            // $invoicesCount = $invoices->count();
        } else {
            // return 99;
            $take = $page * $perPage - $jobsCount;
            // return $take;

            if ($take <= $perPage) {
                // return 11;
                $invoices = $invoicesQuery->take($take)->get();
            } else {
                $invoices = $invoicesQuery->skip($take - $perPage)->take($perPage)->get();
            }
            // $invoicesCount = $invoices->count();
        }

        $allRoles = array();
        $newRoles = array();
        foreach ($jobs as $val) {
            $allRoles[] = PocomosJob::where('id', $val->job_id)->pluck('invoice_id')->toArray();
        }

        foreach ($allRoles as $val) {
            $newRoles[] =  $val[0];
        }

        foreach ($invoices as $val) {
            $newRoles[] = $val->invoice_id;
        }

        if ($request->export == 1) {
            return  $this->downloadexportSummary($newRoles);
        }

        $data = [
            'jobs' => $jobs,
            'entity' => $customer,
            'invoices' => $invoices,
            // 'contract' => $contract,
            'opinionsetting' => $opinionsetting,
        ];

        return $this->sendResponse(true, 'List of upcoming invoices', $data);
    }

    /* API for download export Summary */
    public function downloadexportSummary($invoices)
    {
        $pdf = $this->getMutipleinvoiceHistoryPdf($invoices);

        $url = 'invoices/'  . mt_rand(1000000000, time()) . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output());
        $url = Storage::disk('s3')->url($url);

        $input['name'] = 'Invoice History Summary ';
        $input['description'] = 'The Invoice History Summary  has completed successfully <br><br><a href="' . $url . '">Download  Invoice History Summary</a>';
        $input['status'] = 'Posted';
        $input['type'] = 'Alert';
        $input['priority'] = 'Success';
        $input['active'] = true;
        $input['notified'] = true;
        $input['date_created'] = date('Y-m-d H:i:s');
        $alert = PocomosAlert::create($input);

        $office_alert_details['alert_id'] = $alert->id;
        $office_alert_details['assigned_by_user_id'] = auth()->user()->pocomos_company_office_user->id ?? null;
        $office_alert_details['assigned_to_user_id'] = auth()->user()->pocomos_company_office_user->id ?? null;
        $office_alert_details['active'] = true;
        $office_alert_details['date_created'] = date('Y-m-d H:i:s');
        PocomosOfficeAlert::create($office_alert_details);

        return $this->sendResponse(true, 'Invoice History export has started. You will find the download link on your message board when it is complete. This could take a few minutes.', $url);
    }

    /**
     * API for  Recalculates a due invoices balance.
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function recalculateAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'invoice_id' => 'required|exists:pocomos_invoices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->firstOrFail();

        $invoice = PocomosInvoice::where('id', $request->invoice_id)->first();

        $contract = PocomosContract::findOrFail($invoice->contract_id);

        if ($profile->id != $contract->profile_id) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Customer']));
        }

        $invoiceHelper = $this->canBeRecalculated($request->invoice_id, $invoice);

        if ($invoiceHelper) {
            $invoiceHelper = $this->updateInvoiceTax($request->invoice_id, $contract, $invoice);
            return $this->sendResponse(true, 'Recalculated transaction.', $invoiceHelper);
        }
        throw new \Exception(__('strings.message', ['message' => 'The Invoice must not have any Refunds/Failed Transactions.']));
    }

    /**
     * API for Cancel invoices .
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function cancelAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'invoice_id' => 'required|exists:pocomos_invoices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->firstOrFail();

        $invoice = PocomosInvoice::where('id', $request->invoice_id)->firstOrFail();

        $contract = PocomosContract::findOrFail($invoice->contract_id);

        $currentSalesperson = null;

        if ($profile->id != $contract->profile_id) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $invoiceHelper = $this->cancelInvoice($invoice);

        $job = PocomosJob::where('invoice_id', $request->invoice_id)->first();

        if ($job) {
            $result = $this->cancelJob($job, $invoice);
        }

        $invoice_id = '';

        // if ($job->invoice_id) {
        //     $invoice_id = $job->invoice_id;
        // }

        return $this->sendResponse(true, 'Invoice cancelled successfully.');
    }

    public function invoicelookup(Request $request)
    {
        $v = validator($request->all(), [
            'invoice_id' => 'required|exists:pocomos_invoices,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $invoice = PocomosInvoice::where('id', $request->invoice_id)->first();

        if (!$invoice) {
            return $this->sendResponse(false, 'Unable to find the invoice.');
        }

        $office_id = $request->office_id;

        $contract = PocomosContract::where('id', $invoice->contract_id)->select('profile_id')->first();

        $profile = PocomosCustomerSalesProfile::where('id', $contract->profile_id)->first();

        $customer = PocomosCustomer::where('id', $profile->customer_id)->first();

        if ($profile->office_id != $office_id) {
            $this->switchOffice($office_id);

            $officeUserId = Session::get(config('constants.ACTIVE_OFFICE_USER_ID'));
            $officeUser = PocomosCompanyOfficeUser::findOrFail($officeUserId);
            $user = OrkestraUser::whereId($officeUser->user_id)->first();
            // with('pocomos_company_office_users.company_details.office_settings')->
            $allOffices = PocomosCompanyOffice::with('office_settings', 'logo', 'coontact_address')->where('parent_id', $office_id)->get()->toArray();
            if (!$allOffices) {
                $allOffices = PocomosCompanyOffice::whereId($office_id)->first();
                $allOffices = PocomosCompanyOffice::with('office_settings', 'logo', 'coontact_address')->whereId($allOffices->parent_id)->get()->toArray();
            }
            $parentOffice = PocomosCompanyOffice::with('office_settings', 'logo', 'coontact_address')->whereId($office_id)->first()->toArray();
            $allOffices[] = $parentOffice;
            $success['customer'] =  $customer;
            $success['customer_profile'] =  $profile;
            $success['user'] =  $user;
            //Create new token
            $success['token'] =  $user->createToken('MyAuthApp')->plainTextToken;

            $i = 0;
            foreach ($allOffices as $office) {
                $current_active_office = Session::get(config('constants.ACTIVE_OFFICE_ID'));
                $is_default_selected = false;
                if ($current_active_office == $office['id']) {
                    $is_default_selected = true;
                }
                $allOffices[$i]['is_default_selected'] = $is_default_selected;
                $i = $i + 1;
            }
            $user->offices_details = $allOffices;

            return $this->sendResponse(true, __('strings.sucess', ['name' => 'New Office Details']), $success);
        }

        $officeUserId = Session::get(config('constants.ACTIVE_OFFICE_USER_ID'));
        $officeUser = PocomosCompanyOfficeUser::findOrFail($officeUserId);
        $user = OrkestraUser::whereId($officeUser->user_id)->first();

        $success['customer'] =  $customer;
        $success['customer_profile'] =  $profile;
        $success['user'] =  $user;

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'current Office Details']), $success);
    }


    /*  API for download_service_history */

    public function invoiceHistoryAction()
    {
        $PocomosCustomer = PocomosCustomer::findOrFail($_GET['customer_id']);

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $_GET['customer_id'])->firstOrFail();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to locate Customer Sales Profile.');
        }

        $pestContract = PocomosPestContract::findOrFail($_GET['contract_id']);

        $contracts = PocomosContract::where('id', $pestContract->contract_id)->firstOrFail();

        $profile = PocomosCustomerSalesProfile::where('id', $contracts->profile_id)->firstOrFail();

        if ((!$pestContract) || ($profile->customer_id != $_GET['customer_id'])) {
            return $this->sendResponse(false, 'Unable to find contract.');
        }

        $invoices = PocomosJob::where('contract_id', $_GET['contract_id'])->whereIn('status', ['Complete', 'Cancelled'])->orderBy('date_completed', 'DESC')->pluck('invoice_id')->toArray();

        $pdf = $this->getMutipleinvoiceHistoryPdf($invoices);

        return $pdf->download('invoice_' . $_GET['customer_id'] . '.pdf');
    }
}
