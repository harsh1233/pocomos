<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pocomos\Billing\CompanyBillingProfile;

class OfficeBillingProfileController extends Controller
{
    use Functions;

    /**
     * API for Office billing create and edit
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createUpdate(Request $request)
    {
        $v = validator($request->all(), [
            'price_per_active_user' => 'nullable',
            'price_per_active_user_override' => 'nullable|boolean',
            'price_per_sales_user' => 'nullable',
            'price_per_sales_user_override' => 'nullable|boolean',
            'price_per_active_customer' => 'nullable',
            'price_per_active_customer_override' => 'nullable|boolean',
            'price_per_sent_sms' => 'nullable',
            'price_per_sent_sms_override' => 'nullable|boolean',
            'discount_type' => 'nullable',
            'discount_amount' => 'nullable',
            'comment' => 'nullable',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $companyBillingProfile = CompanyBillingProfile::where('office_id', $request->office_id)->first();

        if ($companyBillingProfile) {
            $updateProfile = $companyBillingProfile->update($request->all());
            $message = 'Company billing profile Updated Successfully.';
            $data = $companyBillingProfile;
        } else {
            $input = [];
            $input = $request->all();
            $input['office_id'] = $request->office_id;
            $companyProfile = CompanyBillingProfile::create($input);
            $message = 'Company billing profile added successfully.';
            $data = $companyProfile;
        }

        return $this->sendResponse(true, $message, $data);
    }

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $companyBillingProfile = CompanyBillingProfile::where('office_id', $request->office_id)->first();

        if (!$companyBillingProfile) {
            $data = [];
            $data['price_per_active_user'] = null;
            $data['price_per_active_user_override'] = null;
            $data['price_per_sales_user'] = null;
            $data['price_per_sales_user_override'] = null;
            $data['price_per_active_customer_override'] = null;
            $data['price_per_active_customer'] = null;
            $data['price_per_sent_sms_override'] = null;
            $data['price_per_sent_sms'] = null;
            $data['discount_type'] = null;
            $data['discount_amount'] = null;
            $data['comment'] = null;
        } else {
            $data = $companyBillingProfile;
        }

        return $this->sendResponse(true, 'List of company billing profile', $data);
    }
}
