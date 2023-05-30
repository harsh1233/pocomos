<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class PocomosPestOfficeSetting extends Model
{
    protected $table = 'pocomos_pest_office_settings';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'initial_duration',
        'regular_duration',
        'active',
        'date_modified',
        'date_created',
        'notify_on_assign',
        'include_time_window',
        'time_window_length',
        'notify_on_reschedule',
        'send_welcome_email',
        'include_schedule',
        'include_pricing',
        'assign_message',
        'reschedule_message',
        'notify_only_verified',
        'welcome_letter',
        'separated_by_type',
        'enable_optimization',
        'include_begin_end_in_invoice',
        'complete_message',
        'enable_blocked_spots',
        'restrict_salesperson_customer_creation',
        'anytime_enabled',
        'disable_recurring_jobs',
        'require_map_code',
        'only_show_date',
        'coloring_scheme',
        'route_map_coloring_scheme',
        'view_add_days',
        'enable_remote_completion',
        'bill_message',
        'my_spots_duration',
        'best_fit_range',
        'show_initial_job_note',
        'alert_on_remote_completion',
        'email_on_remote_completion',
        'route_template_days',
        'enable_discount_type',
        'show_custom_fields_on_remote_completion',
        'default_autopay_value',
        'send_customer_portal_setup',
        'job_pool_sorting_by',
        'show_service_duration_option_agreement',
        'enable_best_fit_rescheduling',
        'validate_zipcode',
        'lock_default_job_duration',
        'hide_tax_calculation',
        'send_inbound_sms_email'
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

    public function company_details()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }

    public function custom_field_configurations()
    {
        return $this->hasMany(PocomosCustomFieldConfiguration::class, 'office_configuration_id', 'id');
    }
}
