<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use DateTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosNote;
use App\Http\Controllers\Controller;
use App\Models\Orkestra\OrkestraFile;
use App\Http\Requests\CustomerRequest;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosCustomersFile;
use App\Models\Pocomos\PocomosCustomersNote;
use App\Models\Pocomos\PocomosCustomerState;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Pocomos\PocomosInvoicePayment;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\PocomosPestContractsPest;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosPestContractsInvoice;
use App\Models\Pocomos\PocomosInvoiceInvoicePayment;

class QuickChargeController extends Controller
{
    use Functions;

    /**
     * Allows a user to bill their customers
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *  Quick Invoice
     */

    public function quickchargeCreate(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_pest_contracts,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'description' => 'required',
            'price' => 'required',
            'paymentType' => 'required|in:0,1,2',
            'method' => 'required|in:card,ach,cash,check,token,points',
            'paymentAccountId' => 'required_if:paymentType,==,1|nullable|exists:orkestra_accounts,id',
            'amount' => 'required_if:paymentType,==,1',
            'paymentReferenceNumber' => 'nullable',
            'memo' => 'nullable',
            'installmentStartDate' => 'required',
            'installmentFrequency' => 'required|in:Weekly,Monthly,Bi-monthly,Quarterly',
            'no_of_payments' => 'required|gt:0',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find Customer entity.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $pest_contarct = PocomosPestContract::findOrFail($request->contract_id);
        $contract = PocomosContract::findOrFail($pest_contarct->contract_id);
        $taxCode = PocomosTaxCode::where('id', $contract->tax_code_id)->first();

        if ($request->paymentType != 2) {
            if ($request->price <= 0) {
                return $this->sendResponse(false, 'Amount due must be greater than $0.');
            }

            $input = [];

            $input['contract_id'] = $contract->id;
            $input['sales_tax'] = $taxCode->tax_rate;
            $input['tax_code_id'] = $taxCode->id;
            $input['status'] = 'Due';
            $input['date_due'] = Carbon::now()->format('Y-m-d');
            $input['amount_due'] = 0;
            $input['balance'] = 0;
            $input['active'] = '1';
            $pocomos_invoice = PocomosInvoice::create($input);

            $invoice_transaction['invoice_id'] = $pocomos_invoice->id;
            $invoice_transaction['pest_contract_id'] = $request->contract_id;
            $invoicetransaction = PocomosPestContractsInvoice::create($invoice_transaction);

            $invoice_item['price'] = $request->price;
            $invoice_item['itemType'] = 'Regular';
            $invoice_item['description'] = $request->description;
            $valueType = 'static';

            $result = $this->addInvoiceItems($pocomos_invoice, $invoice_item, $valueType);

            if ($request->memo != "") {
                $this->addItem('Memo: ' . $request->memo, 0, false, $pocomos_invoice);
            }
        }

        if ($request->paymentType == 1) {
            $payment['account_id'] = $request->paymentAccountId;
            $payment['amount'] = $request->amount;
            $payment['method'] = $request->method;
            $payment['referenceNumber'] = $request->paymentReferenceNumber;
            $payment['customer_id'] = $request->customer_id;

            $items['date_scheduled'] = date('Y-m-d');
            $items['amount_in_cents'] =  round($pocomos_invoice->amount_due, 2) * 100;
            $items['status'] = "Unpaid";
            $items['active'] = true;
            $invoicePayment = PocomosInvoicePayment::create($items);

            $IIPayment = PocomosInvoiceInvoicePayment::create(['invoice_id' => $pocomos_invoice->id, 'payment_id' => $invoicePayment->id]);

            $transaction = $this->processPayments($payment, $profile);

            $applyTransaction = $this->applyTransaction($profile, $transaction, $IIPayment, auth()->user(), $payment);

            if ($transaction['responseMessage'] != 'Approved') {
                throw new \Exception(__('strings.message', ['message' => 'Unable to process payment, but a new invoice has been generated.']));
            } else {
                return $this->sendResponse(true, 'A new invoice and payment have been made');
            }
        } elseif ($request->paymentType == 2) {
            $invoiceDescription = $request->description;
            $numberOfPayments = $request->no_of_payments; //should be >0
            $installmentFrequency = $request->installmentFrequency;
            $installmentStartDate = $request->installmentStartDate;
            $installmentPrice = $request->price / $numberOfPayments;

            $installmentDates = $this->createInstallmentDates($installmentFrequency, $installmentStartDate, $numberOfPayments);

            $n = 0;
            $item = 1;
            while ($n < $numberOfPayments) {
                // return $installmentPrice;
                if ($installmentPrice <= 0) {
                    return $this->sendResponse(false, 'Amount due must be greater than $0.');
                }

                $invoice['sales_tax'] = $taxCode->tax_rate;
                $invoice['tax_code_id'] = $taxCode->id;
                $invoice['contract_id'] = $contract->id;
                $invoice['date_due'] = $installmentDates;
                $invoice['status'] = 'Due';
                $invoice['amount_due'] = $installmentPrice;
                $invoice['balance'] = $installmentPrice;
                $invoice['active'] = true;
                $invoice['closed'] = false;
                $invoiceid = PocomosInvoice::create($invoice);

                $invoice_transaction['invoice_id'] = $invoiceid->id;
                $invoice_transaction['pest_contract_id'] = $request->contract_id;
                $invoicetransaction = PocomosPestContractsInvoice::create($invoice_transaction);

                $invoice_item['price'] = $installmentPrice;
                $invoice_item['itemType'] = 'Regular';
                $invoice_item['description'] = $installmentFrequency . " " . $invoiceDescription . " : " . $item;
                $valueType = 'static';

                $invoiceItem = $this->addInvoiceItems($invoiceid, $invoice_item, $valueType);

                $invoiceid->update(['balance' => round($installmentPrice + ($installmentPrice * $invoiceItem->sales_tax), 2)]);

                return $this->sendResponse(true, 'A new invoice for installment payment have been made.');
            }
        } else {
            return $this->sendResponse(true, 'A new invoice is created.');
        }

        return $this->sendResponse(true, 'A new invoice and payment have been made');
    }

    public function createInstallmentDates($frequency, $installmentStartDate, $numberInstallments)
    {
        //  $installmentStartDate;
        // $date = strtotime("+7 day", $installmentStartDate);

        // return $date;

        $newDate = new DateTime($installmentStartDate);

        $n = 0;
        // $dates = array();
        while ($n < $numberInstallments) {
            if ($frequency == "Weekly") {
                $modify_day = '+' . $n * 7;
                if ($n == 0) {
                    $dates[] = $newDate;
                } else {
                    $dates[] = $newDate->modify($modify_day . ' days');
                }
            } elseif ($frequency == "Monthly") {
                $modify_day = '+' . $n;
                if ($n == 0) {
                    $dates[] = $newDate;
                } else {
                    $dates[] = $newDate->modify($modify_day . ' month');
                }
            } elseif ($frequency == "Bi-monthly") {
                $modify_day = '+' . $n * 2;
                if ($n == 0) {
                    $dates[] = $newDate;
                } else {
                    $dates[] = $newDate->modify($modify_day . ' month');
                }
            } elseif ($frequency == "Quarterly") {
                $modify_day = '+' . $n * 3;
                if ($n == 0) {
                    $dates[] = $newDate;
                } else {
                    $dates[] = $newDate->modify($modify_day . ' month');
                }
            }
            $n++;
        }

        // $dates = $dates[0]->format('Y-m-d h:i:s');

        return $dates[0]->format('Y-m-d h:i:s');
    }
}
