<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use PDF;
use Excel;
use Twilio\Rest\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosEmail;
use Illuminate\Support\Facades\Mail;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Pocomos\PocomosEmailMessage;
use App\Models\Pocomos\PocomosPestEstimates;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosPestInvoiceSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosPestEstimateProducts;
use App\Models\Pocomos\PocomosPestContractServiceType;

class CustomerEstimateController extends Controller
{
    use Functions;

    /**
     * API for create estimate
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function newAction(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'po_number' => 'nullable|integer',
            'subtotal' => 'nullable',
            'discount' => 'nullable',
            'total' => 'nullable',
            'terms' => 'nullable',
            'note' => 'nullable',
            'sent_on' => 'nullable',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'products' => 'required|array',
            'products.*.product' => 'nullable|exists:pocomos_pest_products,id', //product_id
            'products.*.serviceTypes' => 'nullable|exists:pocomos_pest_contract_service_types,id', //service_type_id
            'products.*.cost' => 'nullable', //cost
            'products.*.quantity' => 'required', //quantity
            'products.*.tax' => 'nullable', //tax
            'products.*.taxCode' => 'nullable|exists:pocomos_tax_codes,id', //tax_code_id
            'products.*.calculateAmount' => 'required', //calculate_amount
            'products.*.amount' => 'required', //amount
            'products.*.description' => 'nullable', //description
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestEstimates = PocomosPestEstimates::create($request->only('name', 'po_number', 'subtotal', 'discount', 'total', 'terms', 'note', 'sent_on', 'customer_id', 'office_id') + ['status' => 'Draft', 'search_for' => $request->customer_id]);

        foreach ($request->products as $product) {
            $pocomos_pest_estimate_products = [];
            $pocomos_pest_estimate_products['estimate_id'] = $PocomosPestEstimates->id;

            if (isset($product['serviceTypes'])) {
                $pocomos_pest_estimate_products['service_type_id'] = $product['serviceTypes'];
            }
            if (isset($product['product'])) {
                $pocomos_pest_estimate_products['product_id'] = $product['product'];
            }
            if (isset($product['cost'])) {
                $pocomos_pest_estimate_products['cost'] = $product['cost'];
            }
            if (isset($product['quantity'])) {
                $pocomos_pest_estimate_products['quantity'] = $product['quantity'];
            }
            if (isset($product['tax'])) {
                $pocomos_pest_estimate_products['tax'] = $product['tax'];
            }
            if (isset($product['taxCode'])) {
                $pocomos_pest_estimate_products['tax_code_id'] = $product['taxCode'];
            }
            if (isset($product['calculateAmount'])) {
                $pocomos_pest_estimate_products['calculate_amount'] = $product['calculateAmount'];
            }
            if (isset($product['amount'])) {
                $pocomos_pest_estimate_products['amount'] = $product['amount'];
            }
            if (isset($product['description'])) {
                $pocomos_pest_estimate_products['description'] = $product['description'];
            }

            $PocomosPestEstimateProducts =  PocomosPestEstimateProducts::create($pocomos_pest_estimate_products);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'The estimate has been created']), $PocomosPestEstimates);
    }


    public function itemlist(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $profile = PocomosPestProduct::where('office_id', $request->office_id)->where('active', 1)->where('enabled', 1)->where('shows_on_estimates', 1)->orderBy('id', 'ASC')->get();

        return $this->sendResponse(true, 'List of Items', $profile);
    }


    public function taxcodelist(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $profile = PocomosTaxCode::where('office_id', $request->office_id)->where('active', 1)->get();

        return $this->sendResponse(true, 'List of Tax code', $profile);
    }


    public function servicelist(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $profile = PocomosPestContractServiceType::where('office_id', $request->office_id)->where('active', 1)->where('shows_on_estimates', 1)->orderBy('id', 'ASC')->get();

        return $this->sendResponse(true, 'List of Services', $profile);
    }


    public function indexAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosPestEstimates::where('office_id', $request->office_id);

        if ($request->customer_id) {
            $PocomosLead = $PocomosLead->where('customer_id', $request->customer_id)->where('active', 1);
        }

        if (isset($request->status)) {
            if ($request->status == "All") {
                $PocomosLead = $PocomosLead->where('status', '!=', $request->status);
            } else {
                $PocomosLead = $PocomosLead->where('status', $request->status);
            }
        }

        $PocomosLead = $PocomosLead->orderBy('date_created', 'desc');

        if ($request->search) {
            $search = $request->search;
            $PocomosLead->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('sent_on', 'like', '%' . $search . '%')
                    ->orWhere('total', 'like', '%' . $search . '%')
                    ->orWhere('date_created', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosLead->count();
        $PocomosLead->skip($perPage * ($page - 1))->take($perPage);

        $PocomosLead = $PocomosLead->get();

        $PocomosLead->map(function ($PocomosLead) {
            $findProducts = PocomosPestEstimateProducts::where('estimate_id', $PocomosLead->id)->with('service_data')->with('tax_details')->with('product_data')->get();
            $PocomosLead['product_data'] = $findProducts;
        });

        return $this->sendResponse(true, 'List', [
            'Estimate' => $PocomosLead,
            'count' => $count,
        ]);
    }


    public function updateStatusAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'estimate_id' => 'required|exists:pocomos_pest_estimates,id',
            'status' => 'required|in:Draft,Sent,Won,Lost',
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

        $PocomosLead = PocomosPestEstimates::where('office_id', $request->office_id)->where('customer_id', $request->customer_id)->where('id', $request->estimate_id)->first();

        $PocomosLead->status = $request->status;
        $PocomosLead->save();

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Status Updated']), $PocomosLead);
    }


    public function deleteEstimateAction(Request $request)
    {
        $v = validator($request->all(), [
            'estimate_id' => 'required|exists:pocomos_pest_estimates,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosPestEstimates::find($request->estimate_id);

        if (!$PocomosLead) {
            return $this->sendResponse(false, 'Unable to delete the Estimate.');
        }

        $PocomosLead->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'The estimate has been removed']), $PocomosLead);
    }

    /**
     * API for edit estimate
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function updateAction(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'po_number' => 'nullable|integer',
            'subtotal' => 'nullable',
            'discount' => 'nullable',
            'total' => 'nullable',
            'terms' => 'nullable',
            'note' => 'nullable',
            'sent_on' => 'nullable',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'estimate_id' => 'required|exists:pocomos_pest_estimates,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'products' => 'required|array',
            'products.*.product' => 'nullable|exists:pocomos_pest_products,id', //product_id
            'products.*.serviceTypes' => 'nullable|exists:pocomos_pest_contract_service_types,id', //service_type_id
            'products.*.cost' => 'nullable', //cost
            'products.*.quantity' => 'required', //quantity
            'products.*.tax' => 'nullable', //tax
            'products.*.taxCode' => 'nullable|exists:pocomos_tax_codes,id', //tax_code_id
            'products.*.calculateAmount' => 'required', //calculate_amount
            'products.*.amount' => 'required', //amount
            'products.*.description' => 'nullable', //description
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        PocomosPestEstimateProducts::where('estimate_id', $request->estimate_id)->delete();

        $PestEstimates = PocomosPestEstimates::find($request->estimate_id);

        $PocomosPestEstimates = $PestEstimates->update($request->only('name', 'po_number', 'subtotal', 'discount', 'total', 'terms', 'note', 'sent_on', 'customer_id', 'office_id') + ['search_for' => $request->customer_id]);

        foreach ($request->products as $product) {
            $pocomos_pest_estimate_products = [];
            $pocomos_pest_estimate_products['estimate_id'] = $request->estimate_id;

            if (isset($product['serviceTypes'])) {
                $pocomos_pest_estimate_products['service_type_id'] = $product['serviceTypes'];
            }
            if (isset($product['product'])) {
                $pocomos_pest_estimate_products['product_id'] = $product['product'];
            }
            if (isset($product['cost'])) {
                $pocomos_pest_estimate_products['cost'] = $product['cost'];
            }
            if (isset($product['quantity'])) {
                $pocomos_pest_estimate_products['quantity'] = $product['quantity'];
            }
            if (isset($product['tax'])) {
                $pocomos_pest_estimate_products['tax'] = $product['tax'];
            }
            if (isset($product['taxCode'])) {
                $pocomos_pest_estimate_products['tax_code_id'] = $product['taxCode'];
            }
            if (isset($product['calculateAmount'])) {
                $pocomos_pest_estimate_products['calculate_amount'] = $product['calculateAmount'];
            }
            if (isset($product['amount'])) {
                $pocomos_pest_estimate_products['amount'] = $product['amount'];
            }
            if (isset($product['description'])) {
                $pocomos_pest_estimate_products['description'] = $product['description'];
            }

            $PocomosPestEstimateProducts =  PocomosPestEstimateProducts::create($pocomos_pest_estimate_products);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'The estimate has been updated']), $PocomosPestEstimates);
    }


    /**
     * send_customer_estimate_to_email_address
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function sendEstimateAction(Request $request)
    {
        $v = validator($request->all(), [
            'letter' => 'nullable|exists:pocomos_form_letters,id',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'estimate_id' => 'required|exists:pocomos_pest_estimates,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'subject' => 'required',
            'emails' => 'array|required',
            'emailMessage' => 'required',
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

        $PestEstimates = PocomosPestEstimates::find($request->estimate_id);
        $office = PocomosCompanyOffice::findOrFail($request->office_id);
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->whereUserId(auth()->user()->id)->first();

        $agreement_body = $request->emailMessage;
        $subject = $request->subject;
        $from = $this->getOfficeEmail($request->office_id);

        if ($request->emails) {
            foreach ($request->emails as $emails) {
                Mail::send('emails.dynamic_email_render', compact('agreement_body'), function ($message) use ($subject, $emails, $from) {
                    $message->from($from);
                    $message->to($emails);
                    $message->subject($subject);
                });

                $email_input['office_id'] = $office->id;
                $email_input['office_user_id'] = $officeUser->id;
                $email_input['customer_sales_profile_id'] = $profile->id;
                $email_input['type'] = 'Welcome Email';
                $email_input['body'] = $agreement_body;
                $email_input['subject'] = $subject;
                $email_input['reply_to'] = $from;
                $email_input['reply_to_name'] = $office->name ?? '';
                $email_input['sender'] = $from;
                $email_input['sender_name'] = $office->name ?? '';
                $email_input['active'] = true;
                $email = PocomosEmail::create($email_input);

                $input['email_id'] = $email->id;
                $input['recipient'] = $customer->email;
                $input['recipient_name'] = $customer->first_name.' '.$customer->last_name;
                $input['date_status_changed'] = date('Y-m-d H:i:s');
                $input['status'] = 'Delivered';
                $input['external_id'] = '';
                $input['active'] = true;
                $input['office_user_id'] = $officeUser->id;
                PocomosEmailMessage::create($input);
            }
        }

        return $this->sendResponse(true, 'Email sent.');
    }
}
