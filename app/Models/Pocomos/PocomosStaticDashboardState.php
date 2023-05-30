<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosStaticDashboardState extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'revenue_received_today',
        'revenue_earned_today',
        'new_customers_today',
        'accounts_receivable_today',
        'attrition_rate_today',
        'jobs_completed_today',
        'cancelled_on_hold_customers_today',
        'revenue_last_twelve',
        'revenue_received_ytd',
         'revenue_earned_ytd',
        'new_customers_ytd',
        'jobs_scheduled_today',
         'jobs_scheduled_ytd',
        'jobs_completed_ytd',
        'active_contracts_total',
        'technicians_total',
        'salespeople_total',
        'autopay_customers',
        'customer_credit_card_on_file_total',
        'average_customer_age',
        'average_customer_value',
         'average_customer_lifespan',
        'upcoming_jobs_by_month',
        'upcoming_revenue_by_month',
         'revenue_by_marketing_type_ytd',
        'revenue_by_service_type_ytd',
        'revenue_by_payment_type_ytd',
        'accounts_receivable_by_age_ytd',
        'new_customers_last_twelve',
        'jobs_completed_last_twelve',
        'cancellations_last_twelve',
        'accounts_receivable_last_twelve',
        'reservices_last_twelve',
         'average_contract_value_total',
        'average_job_value_total',
        'employees_total',
         'active_customers_total',
        're_service_ratio_all_time',
        'active',
         'date_created',
        'date_modified',
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
