<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosNote;
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
use App\Models\Pocomos\PocomosInvoicePayment;
use App\Models\Pocomos\PocomosInvoiceInvoicePayment;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Orkestra\OrkestraTransaction;
use App\Models\Pocomos\PocomosUserTransaction;
use App\Models\Pocomos\PocomosInvoiceTransaction;
use App\Models\Pocomos\PocomosJob;
use Illuminate\Support\Facades\DB as FacadesDB;

class ReceiveMoneyController extends Controller
{
    use Functions;

    /**
     * API for create of quick charge
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function receiveMoney(Request $request)
    {
        $v = validator($request->all(), [
            'pay_what' => 'required|in:job,invoice,payment',
            'id' => 'required|integer|min:1',
            'method' => 'required',
            'account' => 'required',
            'description' => 'required',
            'referenceNumber' => 'required|boolean',
            'amount' => 'required',
            'emailInvoice' => 'required',
            'salesStatus' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if ($request->pay_what == "Job") {
            // find invoice id
            $invoice = PocomosJob::where('id', $request->id)->first();
        }
        if ($request->pay_what == "invoice") {
            // find invoice id
            $invoice = PocomosInvoice::where('id', $request->id)->first();
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $contract = PocomosContract::where('profile_id', $profile->id)->first();

        $taxCode = PocomosTaxCode::where('id', $contract->tax_code_id)->first();

        $input = [];
        $input['contract_id'] = $contract->id;
        $input['date_due'] = Carbon::now()->format('Y-m-d');
        $input['amount_due'] = $request->price;
        if ($request->bill_now == '0') {
            $input['status'] = 'Due';
            $input['balance'] = $request->price;
        } else {
            if ($request->price == $request->amount) {
                $input['status'] = 'Paid';
                $input['balance'] = '0.00';
            } else {
                $input['status'] = 'Due';
                $input['balance'] = $request->price - $request->amount;
            }
        }
        $input['sales_tax'] = $taxCode->tax_rate;
        $input['tax_code_id'] = $taxCode->id;
        $input['active'] = '1';
        $pocomos_invoice = PocomosInvoice::create($input);

        // entry into invoice item table
        $invoice_item = [];
        $invoice_item['invoice_id'] = $pocomos_invoice->id;
        $invoice_item['description'] = $request->description;
        $invoice_item['price'] = $request->price;
        $invoice_item['active'] = '1';
        $invoice_item['sales_tax'] = $taxCode->tax_rate;
        $invoice_item['tax_code_id'] = $taxCode->id;
        $invoice_item['type'] = 'Regular';
        $invoice_item = PocomosInvoiceItems::create($invoice_item);
        if ($request->bill_now == '1') {
            $this->addPrice($request, $invoice_item);
        }
        return $this->sendResponse(true, 'Discuount added successfully', $pocomos_invoice);
    }


    /**
     * API for create of quick charge
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function createAction(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'contract_id' => 'required|exists:pocomos_contracts,id',
            'newPayment' => 'required|boolean',
            'payWhat' => 'required_if:newPayment,0|in:job,invoice,payment',
            'job_id' => 'required_if:payWhat,job',
            'invoice_ids' => 'required_if:payWhat,invoice|required_if:newPayment,1|array',
            'payment_id' => 'required_if:payWhat,payment',
            'method' => 'required|in:card,ach,cash,check,account_credit,processed_outside',
            'account' => 'required|exists:orkestra_accounts,id',
            'transaction_description' => 'nullable',
            'referenceNumber' => 'nullable',
            'emailInvoice' => 'nullable|boolean',
            'amount' => 'required|gt:0',
            'salesStatus' => 'required|exists:pocomos_sales_status,id',

        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($custId);

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find Customer entity.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $custId)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $contract = PocomosContract::where('id', $request->contract_id)->first();

        if ($request->salesStatus) {
            $salesStatus = $request->salesStatus;
        }

        $credentials = PocomosOfficeSetting::where('office_id', $profile->office_id)->first();
        if (!$credentials) {
            throw new \Exception(__('strings.message', ['message' => 'The OfficeConfiguration has no associated Credentials']));
        }

        $user = PocomosCompanyOfficeUser::where('office_id', $profile->office_id)->first();
        $invoicesIds = array();

        // if charge multiple
        if ($request->newPayment) {
            $balance = 0;

            $items['date_scheduled'] = date('Y-m-d');
            $items['amount_in_cents'] = 0;
            $items['status'] = "Paid";
            $items['active'] = true;
            $PocomosInvoicePayment = PocomosInvoicePayment::create($items);

            foreach ($request->invoice_ids as $invoice) {
                $pocomos_invoices = PocomosInvoice::findorfail($invoice);

                $balance += $pocomos_invoices->balance;
                $payment['invoice_id'] = $invoice;
                $payment['payment_id'] = $PocomosInvoicePayment->id;
                $IIPayment = PocomosInvoiceInvoicePayment::create($payment);
            }

            $PocomosInvoicePayment->update(['amount_in_cents' => round($balance, 2) * 100]);

            $invoicesIds[] = $request->invoice_ids;
        } else {
            $payWhat = $request->payWhat;

            if ($payWhat == 'payment') {
                $IIPayment = PocomosInvoiceInvoicePayment::wherePaymentId($request->payment_id)->firstOrFail();
            } elseif ($payWhat == 'invoice') {
                $invoice = PocomosInvoice::whereIn('id', $request->invoice_ids)->firstOrFail();
                $items['date_scheduled'] = date('Y-m-d');
                $items['amount_in_cents'] = round($invoice->balance, 2) * 100;
                $items['status'] = "Paid";
                $items['active'] = true;
                $PocomosInvoicePayment = PocomosInvoicePayment::create($items);

                $payment['invoice_id'] = $invoice->id;
                $payment['payment_id'] = $PocomosInvoicePayment->id;
                $IIPayment = PocomosInvoiceInvoicePayment::create($payment);

                $invoicesIds[]  = $invoice->id;
            } else {
                $job = PocomosJob::where('id', $request->job_id)->firstOrFail();
                $invoice = PocomosInvoice::where('id', $job->invoice_id)->firstOrFail();

                $items['date_scheduled'] = date('Y-m-d');
                $items['amount_in_cents'] = round($invoice->balance, 2) * 100;
                $items['status'] = "Paid";
                $items['active'] = true;
                $PocomosInvoicePayment = PocomosInvoicePayment::create($items);

                $payment['invoice_id'] = $invoice->id;
                $payment['payment_id'] = $PocomosInvoicePayment->id;
                $IIPayment = PocomosInvoiceInvoicePayment::create($payment);

                $invoicesIds[]  = $invoice->id;
            }
        }

        $model['account_id'] = $request->account;
        $model['amount'] = $request->amount;
        $model['method'] = $request->method;
        $model['referenceNumber'] = $request->referenceNumber;
        $model['description'] = $request->transaction_description;
        $model['customer_id'] = $custId;

        // return $model;

        $transaction = $this->processPayments($model, $profile);

        $result = $this->applyTransaction($profile, $transaction, $IIPayment, auth()->user(), $model);


        if (count($invoicesIds) && ($request->emailInvoice == 1)) {
            $formData['contract'] =  $request->contract_id;
            $formData['invoices'] = $invoicesIds;
            $formData['type'] = 'invoices';
            //$this->resendEmails($profile, $user, $formData);
        }

        if ($transaction['responseMessage'] != 'Approved') {
            return $this->sendResponse(false, 'Payment failed.');
        }

        if ($salesStatus) {
            $input['sales_status_id'] = $salesStatus;
            $contract->update($input);
        }

        return $this->sendResponse(true, 'The selected payments have been processed');
    }


    public function listinvoices(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        $res = DB::select(DB::raw("SELECT pi.id, pi.balance, pi.date_due, pi.status, pj.id as job_id
                        FROM pocomos_invoices AS pi
                        left JOIN pocomos_jobs AS pj ON pj.invoice_id = pi.id
                        JOIN pocomos_contracts AS pc ON pc.id = pi.contract_id
                        JOIN pocomos_agreements AS pa ON pa.id = pc.agreement_id
                        JOIN pocomos_customer_sales_profiles AS pcsp ON pcsp.id = pc.profile_id
                        WHERE pcsp.id  = '$profile->id' AND pi.status NOT IN ('Paid','Cancelled')
                        AND (pj.status NOT IN ('Cancelled') OR pj.id is null) ORDER BY pi.date_due ASC"));

        return $this->sendResponse(true, 'List of invoices', $res);

        //show id - balance - status - date_due
        //pass id as invoice
    }


    public function listjobs(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        $res = DB::select(DB::raw("SELECT pj.id, pi.id as invoice_id, pi.balance, pj.status,pi.date_due
                            FROM pocomos_jobs AS pj
                            JOIN pocomos_invoices AS pi ON pi.id  = pj.invoice_id
                            JOIN pocomos_contracts AS pc ON pc.id = pi.contract_id
                            JOIN pocomos_customer_sales_profiles AS pcsp ON pcsp.id = pc.profile_id
                            WHERE pcsp.id  = '$profile->id' AND pi.status NOT IN ('Paid')
                             AND (pj.status NOT IN ('Cancelled') OR pj.id is null)
                             ORDER BY pi.date_due"));

        return $this->sendResponse(true, 'List of Jobs', $res);

        //show invoice_id - balance - status - date_due
        //pass id as job
    }


    public function listpayments(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        $res = DB::select(DB::raw("SELECT piip.payment_id, pi.date_due, pi.balance
                    FROM pocomos_invoices_invoice_payments AS piip
                    JOIN pocomos_invoice_payments AS pip ON pip.id  = piip.payment_id
                    JOIN pocomos_invoices AS pi ON pi.id = piip.invoice_id
                    JOIN pocomos_contracts AS pc ON pc.id = pi.contract_id
                    JOIN pocomos_customer_sales_profiles AS pcsp ON pcsp.id = pc.profile_id
                    WHERE pc.profile_id  = $profile->id AND pip.status IN ('Unpaid') ORDER BY pip.date_scheduled ASC"));

        return $this->sendResponse(true, 'List of Payments', $res);
    }
}
