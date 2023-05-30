<?php

namespace App\Http\Controllers\API\Pocomos\Lead;

use PDF;
use Excel;
use Twilio\Rest\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosLead;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Pocomos\PocomosEmailMessage;
use App\Models\Pocomos\PocomosPestEstimates;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosPestInvoiceSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosPestEstimateProducts;
use App\Models\Pocomos\PocomosPestContractServiceType;

class EstimateController extends Controller
{
    use Functions;
    /**
     * API for list of Recruiting Agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
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
            'lead_id' => 'required|exists:pocomos_leads,id',
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

        $PocomosPestEstimates = PocomosPestEstimates::create($request->only('name', 'po_number', 'subtotal', 'discount', 'total', 'terms', 'note', 'sent_on', 'lead_id', 'office_id') + ['status' => 'Draft', 'search_for' => $request->lead_id]);

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

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'lead_id' => 'required|exists:pocomos_leads,id',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosPestEstimates::where('office_id', $request->office_id);

        if ($request->lead_id) {
            $PocomosLead = $PocomosLead->where('lead_id', $request->lead_id)->where('active', 1);
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

    public function updateStatus(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'lead_id' => 'required|exists:pocomos_leads,id',
            'estimate_id' => 'required|exists:pocomos_pest_estimates,id',
            'status' => 'required|in:Draft,Sent,Won,Lost',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $lead = PocomosLead::where('id', $request->lead_id)->first();

        if (!$lead) {
            return $this->sendResponse(false, 'Unable to find the Lead.');
        }

        $PocomosLead = PocomosPestEstimates::where('office_id', $request->office_id)->where('lead_id', $request->lead_id)->where('id', $request->estimate_id)->first();

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
            'lead_id' => 'required|exists:pocomos_leads,id',
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

        $PocomosPestEstimates = $PestEstimates->update($request->only('name', 'po_number', 'subtotal', 'discount', 'total', 'terms', 'note', 'sent_on', 'lead_id', 'office_id') + ['search_for' => $request->lead_id]);

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
     * API for SendEmail
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function SendEmail(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'nullable',
            'subject' => 'nullable',
            'body' => 'nullable',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $leads = PocomosLead::findOrFail($request->lead_id);
        $body = $this->emailTemplate($request->body);
        // $leads->email = 'kishanafaldu08@gmail.com';
        $leads->notify(new SendEmail($request->subject, $body));
        return $this->sendResponse(true, 'Lead Updated successfully.', $body);
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
            'letter' => 'required|exists:pocomos_form_letters,id',
            'lead_id' => 'required|exists:pocomos_leads,id',
            'estimate_id' => 'required|exists:pocomos_pest_estimates,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'subject' => 'required',
            'emails' => 'array|required',
            'emailMessage' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $lead = PocomosLead::findOrFail($request->lead_id);

        if (!$lead) {
            return $this->sendResponse(false, 'Unable to find Lead entity.');
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

                // $email_input['office_id'] = $office->id;
                // $email_input['office_user_id'] = Session::get(config('constants.ACTIVE_OFFICE_USER_ID')) ?? null;
                // //$email_input['customer_sales_profile_id'] = $profile->id;
                // $email_input['type'] = 'Estimate email';
                // $email_input['body'] = $agreement_body;
                // $email_input['subject'] = $subject;
                // $email_input['reply_to'] = $from;
                // $email_input['reply_to_name'] = $office->name ?? '';
                // $email_input['sender'] = $from;
                // $email_input['sender_name'] = $office->name ?? '';
                // $email_input['active'] = true;
                // PocomosEmail::create($email_input);
            }
        }

        return $this->sendResponse(true, 'Email sent.');
    }

    /**
     * API for download_customer_estimate_record
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function estimateDownloadleadAction($lead_id, $estimate_id, $print)
    {
        $PestEstimates = PocomosPestEstimates::find($estimate_id);

        if (!$PestEstimates) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Estimate']));
        }

        $office = PocomosCompanyOffice::where('id', $PestEstimates->office_id)->first();

        $invoiceConfig = PocomosPestInvoiceSetting::where('office_id', $PestEstimates->office_id)->first();

        $products =  PocomosPestEstimateProducts::where('estimate_id', $estimate_id)->get();

        $data = PocomosLead::where('id', $PestEstimates->lead_id)->first();

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
}
