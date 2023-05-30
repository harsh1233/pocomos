<?php

namespace App\Models\Pocomos\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\Billing\Purchase\CompanyLineSubscriptionPurchase;

class CompanyLineSubscriptionsToReport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'line_subscription_purchase_id',
        'billing_report_id',
    ];

    public function sub_purchase()
    {
        return $this->belongsTo(CompanyLineSubscriptionPurchase::class, 'line_subscription_purchase_id');
    }
}
