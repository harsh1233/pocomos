<?php

namespace App\Models\Pocomos\Billing\Items;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class CompanyLineSubscriptionHistory extends Model
{
    protected $table = "company_line_subscription_history";

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'price',
        'office_id',
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
}
