<?php

namespace App\Http\Requests;

use App\Http\Controllers\Functions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LocationRequest extends FormRequest
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
            'service_address' => 'array',
            'service_address.first_name' => 'required',
            'service_address.last_name' => 'required',
            'service_address.email' => 'nullable|email',
            'service_address.secondary_emails' => 'nullable|array',
            'service_address.secondary_emails.*' => 'email',
            'service_address.account_type' => 'nullable:in:Residential,Commercial',
            'service_address.phone' => 'required',
            'service_address.phone_type' => 'nullable|in:Home,Mobile,Fax,Office',
            'service_address.alt_phone' => 'nullable',
            'service_address.alt_phone_type' => 'nullable|in:Home,Mobile,Fax,Office',
            'service_address.company_name' => 'nullable',
            'service_address.street' => 'required',
            'service_address.suite' => 'nullable',
            'service_address.city' => 'nullable',
            'service_address.state' => 'nullable',
            'service_address.region_id' => 'nullable|exists:orkestra_countries_regions,id',
            'service_address.postal' => 'required',
            'service_address.county_id' => 'nullable|exists:pocomos_counties,id',

            'billToParent' => 'required|boolean',
            'parentContracts' => 'nullable|exists:pocomos_contracts,id',
            'inheritContractSettingsFromContract' => 'nullable|exists:pocomos_contracts,id',
            'priceIncludedInParent' => 'nullable|boolean',
            'useParentTaxCode' =>  'required|boolean',

            'service_information' => 'array',
            'service_information.office_id' => 'required|exists:pocomos_company_offices,id',
            'service_information.contract_type_id' => 'required|exists:pocomos_pest_agreements,id',
            'service_information.contract_type' => 'nullable|exists:pocomos_agreements,id',
            'service_information.service_type_id' => 'nullable|exists:pocomos_pest_contract_service_types,id',
            'service_information.service_frequency' => 'nullable',
            'service_information.billing_frequency' => 'nullable',
            'service_information.contract_start_date' => 'nullable|date|date_format:Y-m-d',
            'service_information.contract_end_date' => 'nullable|date|date_format:Y-m-d',
            'service_information.num_of_jobs' => 'nullable|integer',
            'service_information.pricing_information' => 'array',
            'service_information.scheduling_information' => 'array',
            'service_information.additional_information' => 'array',
            'service_information.options' => 'array',
            'service_information.tags' => 'array',
            'service_information.permanent_notes' => 'nullable',
            'service_information.initial_job_notes' => 'nullable',
            'service_information.display_note_time_of_service' => 'nullable|boolean',
            'service_information.targeted_pests' => 'array',
            'service_information.specialty_pests' => 'array',
            'service_information.map_code' => 'nullable',
            'service_information.week_of_the_month' => 'nullable',
            'service_information.day_of_the_week' => 'nullable',
            'service_information.preferred_time' => 'nullable',
            'service_information.additional_information.custom_fields' => 'nullable|array',
            'service_information.service_schedule' => 'nullable|array',
            'service_information.pest_discount_types' => 'nullable|array',
            'service_information.additional_information.tax_code_id' => 'required|exists:pocomos_tax_codes,id',
            'service_information.scheduling_information.technician_id' => 'nullable|exists:pocomos_technicians,id',

            'billing_information' => 'array',
            'billing_information.same_as_service_address' => 'nullable|boolean',
            'billing_information.billing_name' => 'nullable',
            'billing_information.billing_street' => 'required',
            'billing_information.billing_suite' => 'nullable',
            'billing_information.billing_city' => 'nullable',
            'billing_information.billing_state' => 'nullable',
            'billing_information.billing_postal' => 'required',
            'billing_information.is_enroll_auto_pay' => 'nullable|boolean',
            'billing_information.alias' => 'nullable',
            'billing_information.payment_method' => 'nullable|in:card,ach',
            'billing_information.account_number' => 'nullable|max:20',
            'billing_information.exp_month' => 'nullable',
            'billing_information.exp_year' => 'nullable',
            'billing_information.cvv' => 'nullable',
            'billing_information.sales_person_id' => 'nullable|exists:pocomos_salespeople,id',
            'billing_information.marketing_type_id' => 'nullable|exists:pocomos_marketing_types,id',
            'billing_information.sales_status_id' => 'nullable|exists:pocomos_sales_status,id',
            'billing_information.subscribe_to_mailing_list' => 'nullable|boolean',
            'billing_information.account_token' => 'nullable',
            'billing_information.last_four' => 'nullable',
            'billing_information.region_id' => 'required|exists:orkestra_countries_regions,id',
            'billing_information.country' => 'nullable',
            'customer_id' => 'nullable|exists:pocomos_customers,id',
            'agreement' => 'array',
            'agreement.addendum' => 'nullable',
            // 'agreement.terms_of_pocomos_agreement' => 'nullable|boolean',
            'agreement.signature' => 'nullable|mimes:png,jpg,jpeg',
            'lead_id' => 'nullable|exists:pocomos_leads,id'
        ];
    }

    /**Validation messages */
    public function messages()
    {
        return [
            /**Dynamic validation messages strings*/
            'service_address.email.email' => __('validation.email', ['attribute' => 'email variable']),

            'service_address.county_id.exists' => __('validation.exists', ['attribute' => 'county_id']),

            'service_information.contract_type_id.exists' => __('validation.exists', ['attribute' => 'contract_type_id']),
            'service_information.service_type_id.exists' => __('validation.exists', ['attribute' => 'service_type_id']),
            'service_information.contract_start_date.date_format' => __('validation.date_format', ['attribute' => 'contract_start_date'])
        ];
    }

    /**Return validation error response */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->sendResponse(false, $validator->errors()->first()));
    }
}
