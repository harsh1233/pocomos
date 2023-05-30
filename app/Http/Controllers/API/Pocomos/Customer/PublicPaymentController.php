<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Excel;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosJob;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosSubCustomer;
use App\Models\Orkestra\OrkestraTransaction;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosInvoicePayment;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosInvoiceInvoicePayment;

class PublicPaymentController extends Controller
{
    use Functions;


    /**
     * API for list payment account
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function quickPaymentAction(Request $request, $custId, $hash, $paymentid = null)
    {
        $customer = PocomosCustomer::with('state_details', 'sales_profile.office_details.contact')->find($custId);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate Customer entity']));
        }

        $office = $customer->sales_profile->office_details;

        $profile = PocomosCustomerSalesProfile::whereCustomerId($custId)->first();

        if (!$profile) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Customer Profile.']));
        }

        $payment = null;
        if ($paymentid) {
            $payment = PocomosInvoiceInvoicePayment::join('pocomos_invoices as pi', 'pocomos_invoices_invoice_payments.invoice_id', 'pi.id')
                ->join('pocomos_contracts as pc', 'pi.contract_id', 'pc.id')
                ->where('pc.profile_id', $profile->id)
                ->where('pocomos_invoices_invoice_payments.payment_id', $paymentid)
                ->first();
        }

        // QuickPaymentType form
        $invoices = PocomosInvoice::select(
            '*',
            'pocomos_invoices.*',
            'ppcst.name as service_type',
            'pcu.first_name as cust_fname',
            'pcu.last_name as cust_lname',
            'pj.id as job_id',
            'pocomos_invoices.id as qqq'
        )
        ->leftJoin('pocomos_jobs as pj', 'pocomos_invoices.id', 'pj.invoice_id')
        ->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
        ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')

        //added
        ->leftjoin('pocomos_pest_contracts as ppc', 'pc.id', 'ppc.contract_id')
        ->leftjoin('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id')
        ->leftjoin('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
        ->leftjoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')

        ->where('pcsp.id', $profile->id)
        ->whereNotIn('pocomos_invoices.status', ['Paid', 'Cancelled', 'Past due', 'Not sent'])
        ->where(function ($query) use ($request) {
            $query->where('pj.status', 'Complete')
                ->orWhere('pj.id', null);
        })
        ->orderBy('pocomos_invoices.date_due')
        ->get();


        return $this->sendResponse(true, 'customer data', array(
            // 'office' => $office,
            'customer' => $customer,
            'invoices' => $invoices,
            // 'profile' => $profile,
            // 'payment' => $payment,
            // 'hash' => $verifyHash,
        ));
    }


    public function miscInvoiceDownloadAction($custId, $invoiceId)
    {
        $customer = PocomosCustomer::find($custId);

        $office = $customer->sales_profile->office_details;

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate Customer entity']));
        }

        $profile = PocomosCustomerSalesProfile::whereCustomerId($custId)->first();

        $officeSettings = PocomosPestOfficeSetting::whereOfficeId($office->id)->first();

        if (!$officeSettings) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Office Settings.']));
        }

        $invoice = PocomosInvoice::find($invoiceId);

        if (!$invoice) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Invoice.']));
        }

        $invoiceProfile = $invoice->contract->profile_id;

        if ($invoiceProfile !== $profile->id) {
            $parentRelationship = PocomosSubCustomer::where('child_id', $custId)->first();

            if ($parentRelationship) {
                $parentId = $parentRelationship->parent_id;
                $profile = PocomosCustomerSalesProfile::whereCustomerId($parentId)->first();
            }
            if ($invoiceProfile !== $profile->id) {
                // throw new \Exception(__('strings.message', ['message' => 'Unable to find the Invoice Entitiy.']));
            }
        }

        $pdf = $this->getInvoiceBasePdf($invoice);

        $url =  "public_payment/" . 'invoice_'. $invoice->id .''. strtotime("now") . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output(), 'public');
        $path = Storage::disk('s3')->url($url);

        return $this->sendResponse(true, 'Misc Invoice pdf', $path);
    }


    public function createPublicPaymentAction(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'account_name' => 'required',
            'payment_method' => 'required|in:card,ach',
            'account_number' => 'required',
            'ach_routing_number' => 'required_if:payment_method,ach',
            'card_exp_year' => 'required_if:payment_method,card',
            'card_exp_month' => 'required_if:payment_method,card',
            'autopay' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::with('state_details', 'sales_profile.office_details.contact')->find($custId);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate Customer entity']));
        } else {
            $office = $customer->sales_profile->office_details;

            $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $office->id)->first();

            if (!$PocomosOfficeSetting) {
                throw new \Exception(__('strings.message', ['message' => 'Unable to find the Office Configuration']));
            }

            $profile = $customer->sales_profile;
            if (!$profile) {
                throw new \Exception(__('strings.message', ['message' => 'Unable to find the Profile.']));
            }

            $this->hasACHCredentials($office->id);

            $account = $this->getEntity_pymAC($request);

            $this->fillAccountWithDetails($account, $customer);

            $account->account_number = preg_replace('/[^0-9]/', '', $account->account_number);

            $account->save();

            // return $account->id;

            $this->addAccount_custSalesProf($account->id, $profile->id);

            $autoPay = $request->autopay;
            $paymentMethod = $request->payment_method;
            $profile = $this->setAutoPay_custHelper($profile, $account, $autoPay, $paymentMethod);
        }

        return $this->sendResponse(true, 'Account created successfully.');
    }

    public function submitPayment(Request $request, $custId)
    {

        /*$url = 'https://sandbox-secure.zift.io/gates/xurl?';

        $data = array(
                'requestType'=>'sale',
                'userName' => 'api-pcms-smt-6843000',
                'password' => '47EQSfu4Gjf5suSIzD4oV12RpMIQocue',
                'accountId'=>6843001,
                'amount'=>10.00,
                'accountType'=>'R',
                'transactionIndustryType'=>'RE',
                'holderType'=>'O',
                'holderName'=>'John Smith',
                'accountNumber'=>4857616368183660,
                'accountAccessory'=>1024
            );
    
        $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded",
            'method'  => 'POST',
            'content' => http_build_query($data),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        dd($result);*/

        $v = validator($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:card,ach',
            'name_on_card' => 'required',
            'account_number' => 'required',
            'ach_routing_number' => 'required_if:payment_method,ach',
            'card_exp_year' => 'required_if:payment_method,card',
            'card_exp_month' => 'required_if:payment_method,card',
            'autopay' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $paymentid = null;

        $customer = PocomosCustomer::find($custId);

        $office = $customer->sales_profile->office_details;

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate Customer entity']));
        }

        $profile = PocomosCustomerSalesProfile::whereCustomerId($custId)->first();

        if (!$profile) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Customer Profile Or Invalid User.']));
        }

        $officeUser = $customer->sales_profile->sales_people->office_user_details;

        $payment = null;
        if ($paymentid) {
            $payment = PocomosInvoiceInvoicePayment::join('pocomos_invoices as pi', 'pocomos_invoices_invoice_payments.invoice_id', 'pi.id')
                ->join('pocomos_contracts as pc', 'pi.contract_id', 'pc.id')
                ->where('pc.profile_id', $profile->id)
                ->where('pocomos_invoices_invoice_payments.payment_id', $paymentid)
                ->first();
        }

        $salesStatus = "Active";
        $invoicesIds = [];

        // if charge multiple
        if ($request->multiple_invoices) {
            $balance = 0;

            $items['date_scheduled'] = date('Y-m-d');
            $items['amount_in_cents'] = 0;
            $items['status'] = "Paid";
            $items['active'] = true;
            $PocomosInvoicePayment = PocomosInvoicePayment::create($items);

            foreach ($request->multiple_invoices as $invoice) {
                $pocomos_invoices = PocomosInvoice::findorfail($invoice);

                $balance += $pocomos_invoices->balance;
                $payment['invoice_id'] = $invoice;
                $payment['payment_id'] = $PocomosInvoicePayment->id;
                $IIPayment = PocomosInvoiceInvoicePayment::create($payment);
            }

            $PocomosInvoicePayment->update(['amount_in_cents' => round($balance, 2) * 100]);

            $invoicesIds[] = $request->multiple_invoices;
        } else {
            $invoice = PocomosInvoice::whereId($request->invoice)->firstOrFail();
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

        $customerProfile = $customer->sales_profile;
        $accountName = $request->name_on_card;
        $address = $customer->billing_address;

        if ($request->payment_method == 'card') {
            $input['account_number'] = trim($request->account_number);
            $input['card_exp_month'] = $request->card_exp_month;
            $input['card_exp_year'] = $request->card_exp_year;
            $input['name'] = $customer->first_name.' '.$customer->last_name;
            $input['address'] = trim($address->street . ' ' . $address->suite);
            $input['city'] = $address->city;

            $input['region'] = $address->region->code ?? '';
            $input['country'] = $address->region->country_detail->code ?? '';

            $input['postal_code'] = $address->postal_code;
            $input['phoneNumber'] = $address->primaryPhone->number;
            $input['alias'] = $request->name_on_card;

            $input['type'] =  'CardAccount';
            $input['active'] = true;
            $input['ip_address'] = '';
            $input['email_address'] = '';
            $input['external_person_id'] = '';
            $input['external_account_id'] = '';
            $account =  OrkestraAccount::create($input);

            PocomosCustomersAccount::create(['profile_id' => $profile->id, 'account_id' => $account->id]);

            if ($request->autopay == true) {
                $account->update(['active' => 1]);
                $profile->update(['autopay' => 1, 'autopay_account_id' => $account->id]);
            } else {
                $account->update(['active' => 0]);
            }
        } else {
            $input['alias'] = $request->name_on_card;
            $input['account_number'] = $request->account_number;
            $input['ach_routing_number'] = trim($request->routing_number);
            $input['name'] = $customer->first_name.' '.$customer->last_name;
            $input['address'] = trim($address->street . ' ' . $address->suite);
            $input['city'] = $address->city;

            $input['region'] = $address->region->code ?? '';
            $input['country'] = $address->region->country_detail->code ?? '';

            $input['postal_code'] = $address->postal_code;
            $input['phoneNumber'] = $address->primaryPhone->number;

            $input['type'] =  'BankAccount';
            $input['active'] = true;
            $input['ip_address'] = '';
            $input['email_address'] = '';
            $input['external_person_id'] = '';
            $input['external_account_id'] = '';
            $account =  OrkestraAccount::create($input);

            PocomosCustomersAccount::create(['profile_id' => $profile->id, 'account_id' => $account->id]);

            if ($request->autopay == true) {
                $account->update(['active' => 1]);
                $profile->update(['autopay' => 1, 'autopay_account_id' => $account->id]);
            } else {
                $account->update(['active' => 0]);
            }
        }

        // added
        $input['account_id'] = $account->id;
        $input['amount'] = $request->amount;
        $input['type'] = 'Credit';
        $input['network'] = $request->payment_method == 'card' ? 'Card' : 'ACH';
        $input['status'] = 'Approved';
        $input['active'] = 1;
        $transaction = OrkestraTransaction::create($input);

        $model = [];
        $model['account_id'] = $account->id;
        $model['amount'] = $request->amount;
        $model['method'] = $request->payment_method;
        $model['transaction'] = $transaction;
        $model['customer'] = $customer;
        $model['account'] = $account;

        $transaction = $this->processPayment_salesPaymentHelper($model, $profile);

        $transaction = $transaction->toArray();

        $this->applyTransaction($profile, $transaction, $IIPayment, null, $model);

        if (count($invoicesIds) && $request->emailInvoice) {
            $formData['contract'] =  $request->contract_id;
            $formData['invoices'] = $invoicesIds;
            $formData['type'] = 'invoices';
            $this->resendEmails($profile, $officeUser, $formData);
        }

        if ($transaction['status'] == 'Approved') {
            return $this->sendResponse(true, 'The selected payments have been processed.');
        } else {
            return $this->sendResponse(true, 'Payment failed.');
        }
    }
}
