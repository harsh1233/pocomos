<?php

namespace App\Http\Requests;

use App\Http\Controllers\Functions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Recruitment extends FormRequest
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
            'basic_information' => 'array',
            'basic_information.first_name' => 'nullable',
            'basic_information.last_name' => 'nullable',
            'basic_information.email' => 'nullable',
            'basic_information.recruiting_office_id' => 'required|exists:pocomos_recruiting_offices,id',
            'basic_information.recruit_agreement_id' => 'required|exists:pocomos_recruit_agreements,id',
            'basic_information.recruiting_region_id' => 'nullable|exists:pocomos_recruiting_region,id',
            'basic_information.recruiter_id' => 'required',
            'basic_information.date_start' => 'date',
            'basic_information.date_end' => 'date',
            'basic_information.note' => 'nullable',
            'basic_information.agreement_addendum' => 'nullable',
            'basic_information.recruiter_sign' => 'image|mimes:jpg,png,jpeg',

            'full_information' => 'array',
            'full_information.general_information' => 'array',
            'full_information.login_information' => 'array',
            'full_information.contact_information' => 'array',
            'full_information.current_address' => 'array',
            'full_information.primary_same_as_current' => 'boolean',
            'full_information.primary_address' => 'array',
            'full_information.initial' => 'image|mimes:jpg,png,jpeg',
            'full_information.additional_information' => 'array',

            'finalize_agreement' => 'array',
            'finalize_agreement.sales_contract' => 'string',
            'finalize_agreement.acceptance_of_contract' => 'array',
            'finalize_agreement.contract_signature' => 'image|mimes:jpg,png,jpeg'
        ];
    }

    /**Validation messages */
    public function messages()
    {
        return [
            /**Dynamic validation messages strings*/
            'basic_information.recruiting_office_id.required' => __('validation.required', ['attribute' => 'recruiting_office_id']),
            'basic_information.recruit_agreement_id.required' => __('validation.required', ['attribute' => 'recruit_agreement_id']),
            'basic_information.recruiting_region_id.required' => __('validation.required', ['attribute' => 'recruiting_region_id']),
            'basic_information.recruiter_id.required' => __('validation.required', ['attribute' => 'recruiter_id']),
            'basic_information.recruiter_sign.image' => __('validation.image', ['attribute' => 'recruiter_sign']),

            'full_information.initial.image' => __('validation.image', ['attribute' => 'initial']),
            'finalize_agreement.contract_signature.image' => __('validation.image', ['attribute' => 'contract_signature']),
        ];
    }

    /**Return validation error response */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->sendResponse(false, $validator->errors()->first()));
    }
}
