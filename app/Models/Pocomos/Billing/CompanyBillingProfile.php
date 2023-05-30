<?php

namespace App\Models\Pocomos\Billing;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class CompanyBillingProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'price_per_active_user',
        'price_per_active_user_override',
        'price_per_sales_user',
        'price_per_sales_user_override',
        'price_per_active_customer',
        'price_per_active_customer_override',
        'price_per_sent_sms',
        'price_per_sent_sms_override',
        'discount_type',
        'discount_amount',
        'office_id',
        'comment',
        'active',
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

    /**Get office details */
    public function office_details()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }
}
