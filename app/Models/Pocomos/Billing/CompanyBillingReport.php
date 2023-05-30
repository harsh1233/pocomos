<?php

namespace App\Models\Pocomos\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class CompanyBillingReport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'active_user_count',
        'price_per_active_user',
        'price_per_active_user_override',
        'sales_user_count',
        'price_per_sales_user',
        'price_per_sales_user_override',
        'active_customer_count',
        'price_per_active_customer',
        'price_per_active_customer_override',
        'sent_sms_count',
        'received_sms_count',
        'phone_number_price',
        'price_per_sent_sms',
        'price_per_sent_sms_override',
        'total_price',
        'total_price_override',
        'office_id',
        'comment',
        'report_month',
        'report_year',
        'active',
        'report_date',
        'date_modified',
        'date_created',
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

    public function office()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }

    public function subscription_purchases()
    {
        return $this->hasMany(CompanyLineSubscriptionsToReport::class, 'billing_report_id', 'id')
            // ->selectRaw('billing_report_id,SUM(billing_report_id) as payment_amount')
            // ->groupBy('billing_report_id')
        ;
    }

    // public function payments()
    // {
    //     return $this->hasMany('CompanyLineSubscriptionsToReport')
    //         ->selectRaw('SUM(payments.amount) as payment_amount')
    //         ->groupBy('id'); // as per our requirements.
    // }
}
