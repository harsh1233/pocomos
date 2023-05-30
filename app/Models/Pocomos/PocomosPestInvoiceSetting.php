<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosPestInvoiceSetting extends Model
{
    protected $table = 'pocomos_pest_invoice_settings';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'problem_label',
        'specialty_problem_label',
        'targeted_label',
        'show_technician',
        'show_technician_photo',
        'show_time_in',
        'show_time_out',
        'show_technician_license',
        'show_business_license',
        'show_targeted_pests',
        'show_products_used',
        'show_areas_applied',
        'show_application_type',
        'show_amount_of_product',
        'show_technician_note',
        'show_dilution_rate',
        'show_application_rate',
        'active',
        'date_modified',
        'date_created',
        'use_legacy_layout',
        'show_outstanding_balance',
        'show_epa_code',
        'show_date_printed',
        'show_customer_portal_link',
        'show_portal_or_quick_link',
        'show_office_phone',
        'show_purchase_order_number',
        'show_last_service_date',
        'show_technician_signature',
        'show_payment_method',
        'display_outstanding_on_invoice',
        'include_do_not_pay',
        'show_child_customer_name',
        'show_attn',
        'show_due_date',
        'show_tax_code',
        'enlarge_name_address_size_on_invoice',
        'adjust_name_address_to_right',
        'show_customer_emailaddress',
        'show_all_phone_number_along_type',
        'show_attach_photo',
        'rename_application_type',
        'application_type_text',
        'show_weather',
        'show_technician_list',
        'show_technician_list_required',
        'show_technician_note_template',
        'show_phone_number',
        'show_custom_fields',
        'show_map_code',
        'show_appointment_time',
        'show_marketing_type',
        'epa_code_or_number',
        'show_job_notes',
        'show_dilution_rate_on_template',
        'show_application_rate_on_template',
    ];


    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $record->date_created = date("Y-m-d H:i:s");
        });

        // create a event to happen on updating
        static::updating(function ($record) {
            $record->date_modified = date("Y-m-d H:i:s");
        });
    }
}
