<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosPestInvoiceSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class InvoiceConfigurationController extends Controller
{
    use Functions;

    /**
     * API for list of Invoice Note
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function getinvoicesettings($officeid)
    {
        $PocomosPestInvoiceSetting = PocomosPestInvoiceSetting::where('office_id', $officeid)->first();

        return $this->sendResponse(true, 'Data of Pest Invoice Settings.', $PocomosPestInvoiceSetting);
    }

    /**
     * API for pest ofice Configuration route Edit Update setting routes
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function invoicesettings(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'problem_label' => 'required',
            'specialty_problem_label' => 'required',
            'targeted_label' => 'required',
            'display_outstanding_on_invoice' => 'nullable|boolean',
            'epa_code_or_number' => 'nullable',
            'show_attn' => 'nullable|boolean',
            'show_amount_of_product' => 'nullable|boolean',
            'show_application_type' => 'nullable|boolean',
            'show_areas_applied' => 'nullable|boolean',
            'show_products_used' => 'nullable|boolean',
            'show_targeted_pests' => 'nullable|boolean',
            'show_technician' => 'nullable|boolean',
            'show_technician_photo' => 'nullable|boolean',
            'show_technician_license' => 'nullable|boolean',
            'show_outstanding_balance' => 'nullable|boolean',
            'show_epa_code' => 'nullable|boolean',
            'show_time_in' => 'nullable|boolean',
            'show_technician_note' => 'nullable|boolean',
            'show_date_printed' => 'nullable|boolean',
            'show_time_out' => 'nullable|boolean',
            'show_office_phone' => 'nullable|boolean',
            'show_purchase_order_number' => 'nullable|boolean',
            'show_technician_signature' => 'nullable|boolean',
            'show_customer_portal_link' => 'nullable|boolean',
            'show_last_service_date' => 'nullable|boolean',
            'show_payment_method' => 'nullable|boolean',
            'include_do_not_pay' => 'nullable|boolean',
            'show_child_customer_name' => 'nullable|boolean',
            'show_due_date' => 'nullable|boolean',
            'show_technician_note_template' => 'nullable|boolean',
            'show_tax_code' => 'nullable|boolean',
            'enlarge_name_address_size_on_invoice' => 'nullable|boolean',
            'adjust_name_address_to_right' => 'nullable|boolean',
            'rename_application_type' => 'nullable|boolean',
            'show_weather' => 'nullable|boolean',
            'show_phone_number' => 'nullable|boolean',
            'show_custom_fields' => 'nullable|boolean',
            'show_map_code' => 'nullable|boolean',
            'show_appointment_time' => 'nullable|boolean',
            'show_marketing_type' => 'nullable|boolean',
            'show_job_notes' => 'nullable|boolean',
            'show_portal_or_quick_link' => 'nullable|in:1,2'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestInvoiceSetting = PocomosPestInvoiceSetting::where('office_id', $request->office_id)->first();

        $input_details = $request->only(
            'problem_label',
            'specialty_problem_label',
            'targeted_label',
            'show_attn',
            'show_amount_of_product',
            'show_application_type',
            'show_areas_applied',
            'show_products_used',
            'show_targeted_pests',
            'show_technician',
            'show_technician_photo',
            'show_technician_license',
            'show_technician_note',
            'show_business_license',
            'show_outstanding_balance',
            'showEpaCode',
            'show_time_in',
            'show_date_printed',
            'show_time_out',
            'show_office_phone',
            'show_purchase_order_number',
            'show_technician_signature',
            'show_customer_portal_link',
            'show_last_service_date',
            'show_payment_method',
            'display_outstanding_on_invoice',
            'include_do_not_pay',
            'show_child_customer_name',
            'show_due_date',
            'show_technician_note_template',
            'show_tax_code',
            'enlarge_name_address_size_on_invoice',
            'adjust_name_address_to_right',
            'rename_application_type',
            'application_type_text',
            'epa_code_or_number',
            'show_weather',
            'show_phone_number',
            'show_custom_fields',
            'show_map_code',
            'show_appointment_time',
            'show_marketing_type',
            'show_job_notes',
            'show_dilution_rate',
            'show_application_rate',
            'show_dilution_rate_on_template',
            'show_application_rate_on_template',
            'show_technician_list_required',
            'show_technician_list',
            'show_attach_photo',
            'show_customer_emailaddress',
            'show_all_phone_number_along_type',
            'show_epa_code',
            'show_portal_or_quick_link'
        ) + ['active' => true, 'office_id' => $request->office_id];

        $input_details["show_attn"] = $input_details["show_attn"] ?? 0;
        $input_details["show_amount_of_product"] = $input_details["show_amount_of_product"] ?? 0;
        $input_details["show_application_type"] = $input_details["show_application_type"] ?? 0;
        $input_details["show_areas_applied"] = $input_details["show_areas_applied"] ?? 0;
        $input_details["show_products_used"] = $input_details["show_products_used"] ?? 0;
        $input_details["show_targeted_pests"] = $input_details["show_targeted_pests"] ?? 0;
        $input_details["show_technician"] = $input_details["show_technician"] ?? 0;
        $input_details["show_technician_photo"] = $input_details["show_technician_photo"] ?? 0;
        $input_details["show_technician_license"] = $input_details["show_technician_license"] ?? 0;
        $input_details["show_technician_note"] = $input_details["show_technician_note"] ?? 0;
        $input_details["show_business_license"] = $input_details["show_business_license"] ?? 0;
        $input_details["show_outstanding_balance"] = $input_details["show_outstanding_balance"] ?? 0;
        $input_details["showEpaCode"] = $input_details["showEpaCode"] ?? 0;
        $input_details["show_time_in"] = $input_details["show_time_in"] ?? 0;
        $input_details["show_date_printed"] = $input_details["show_date_printed"] ?? 0;
        $input_details["show_time_out"] = $input_details["show_time_out"] ?? 0;
        $input_details["show_office_phone"] = $input_details["show_office_phone"] ?? 0;
        $input_details["show_purchase_order_number"] = $input_details["show_purchase_order_number"] ?? 0;
        $input_details["show_technician_signature"] = $input_details["show_technician_signature"] ?? 0;
        $input_details["show_customer_portal_link"] = $input_details["show_customer_portal_link"] ?? 0;
        $input_details["show_last_service_date"] = $input_details["show_last_service_date"] ?? 0;
        $input_details["show_payment_method"] = $input_details["show_payment_method"] ?? 0;
        $input_details["display_outstanding_on_invoice"] = $input_details["display_outstanding_on_invoice"] ?? 0;
        $input_details["include_do_not_pay"] = $input_details["include_do_not_pay"] ?? 0;
        $input_details["show_child_customer_name"] = $input_details["show_child_customer_name"] ?? 0;
        $input_details["show_due_date"] = $input_details["show_due_date"] ?? 0;
        $input_details["show_technician_note_template"] = $input_details["show_technician_note_template"] ?? 0;
        $input_details["show_tax_code"] = $input_details["show_tax_code"] ?? 0;
        $input_details["enlarge_name_address_size_on_invoice"] = $input_details["enlarge_name_address_size_on_invoice"] ?? 0;
        $input_details["adjust_name_address_to_right"] = $input_details["adjust_name_address_to_right"] ?? 0;
        $input_details["rename_application_type"] = $input_details["rename_application_type"] ?? 0;
        $input_details["show_weather"] = $input_details["show_weather"] ?? 0;
        $input_details["show_phone_number"] = $input_details["show_phone_number"] ?? 0;
        $input_details["show_custom_fields"] = $input_details["show_custom_fields"] ?? 0;
        $input_details["show_map_code"] = $input_details["show_map_code"] ?? 0;
        $input_details["show_appointment_time"] = $input_details["show_appointment_time"] ?? 0;
        $input_details["show_marketing_type"] = $input_details["show_marketing_type"] ?? 0;
        $input_details["show_job_notes"] = $input_details["show_job_notes"] ?? 0;
        $input_details["show_dilution_rate"] = $input_details["show_dilution_rate"] ?? 0;
        $input_details["show_application_rate"] = $input_details["show_application_rate"] ?? 0;
        $input_details["show_dilution_rate_on_template"] = $input_details["show_dilution_rate_on_template"] ?? 0;
        $input_details["show_application_rate_on_template"] = $input_details["show_application_rate_on_template"] ?? 0;
        $input_details["show_technician_list_required"] = $input_details["show_technician_list_required"] ?? 0;
        $input_details["show_technician_list"] = $input_details["show_technician_list"] ?? 0;
        $input_details["show_attach_photo"] = $input_details["show_attach_photo"] ?? 0;
        $input_details["show_customer_emailaddress"] = $input_details["show_customer_emailaddress"] ?? 0;
        $input_details["show_all_phone_number_along_type"] = $input_details["show_all_phone_number_along_type"] ?? 0;
        $input_details["show_epa_code"] = $input_details["show_epa_code"] ?? 0;
        $input_details["show_portal_or_quick_link"] = $input_details["show_portal_or_quick_link"] ?? 1;

        if (!$PocomosPestInvoiceSetting) {
            $PocomosPestInvoiceSetting = PocomosPestInvoiceSetting::create($input_details);
        } else {
            $PocomosPestInvoiceSetting->update(
                $input_details
            );
        }

        return $this->sendResponse(true, 'Invoice configuration updated successfully.', $PocomosPestInvoiceSetting);
    }
}
