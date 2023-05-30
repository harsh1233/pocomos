<?php

namespace App\Http\Requests;

use App\Http\Controllers\Functions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CustomerAdvanceRequest extends FormRequest
{
    use Functions;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customer_id' => 'nullable|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'first_name' => 'nullable',
            'last_name' => 'nullable',
            'company_name' => 'nullable',
            'street_address' => 'nullable',
            'city' => 'nullable',
            'state' => 'nullable',
            'zip' => 'nullable',
            'external_account_id' => 'nullable',
            'county' => 'nullable|array',
            'phone' => 'nullable',
            'email_address' => 'nullable',
            'customer_status' => 'nullable|array',
            'contract_status' => 'nullable',
            'signup_date_start' => 'nullable',
            'signup_date_end' => 'nullable',
            'last_modified' => 'nullable|array',
            'all_branches' => 'nullable',
            'include_cancelled_contracts' => 'nullable',
            'account_type' => 'nullable|array',
            'agreement' => 'nullable|array',
            'service_type' => 'nullable|array',
            'service_frequency' => 'nullable|array',
            'pest' => 'nullable|array',
            'specialty_pest' => 'nullable|array',
            'job_type' => 'nullable|array',
            'job_status' => 'nullable',
            'billing_frequency' => 'nullable',
            'initial_fees' => 'nullable',
            'recurring_fees' => 'nullable',
            'tag' => 'nullable',
            'is_tag_checked' => 'nullable',
            'preferred_tech' => 'nullable',
            'recurring_day' => 'nullable',
            'recurring_week' => 'nullable',
            'tax_codes' => 'nullable|array',
            'found_by_type' => 'nullable|array',
            'sales_status' => 'nullable|array',
            'invoice_status' => 'nullable|array',
            'sales_person' => 'nullable|array',
            'billing_status' => 'nullable|array',
            'technician' => 'nullable',
            'autopay' => 'nullable',
            'autorenew' => 'nullable',
            'invoice_id' => 'nullable',
            'service_start' => 'nullable',
            'service_end' => 'nullable',
            'custom_fields' => 'nullable|array',
            'initial_service_end' => 'nullable',
            'export_columns' => 'nullable|array',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
        ];
    }

    /**Validation messages */
    public function messages()
    {
        return [
            /**Dynamic validation messages strings*/
        ];
    }

    /**Return validation error response */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->sendResponse(false, $validator->errors()->first()));
    }
}
