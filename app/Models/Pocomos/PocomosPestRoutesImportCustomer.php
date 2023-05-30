<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosPestRoutesImportCustomer extends Model
{
    // protected $table = 'pocomos_pest_routes_import_contract';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'bill_to_account_id',
        'pest_office_id',
        'fname',
        'lname',
        'company_name',
        'status',
        'status_text',
        'email',
        'phone1',
        'address',
        'city',
        'state',
        'zipcode',
        'subscription_ids',
        'subscription_data',
        'office_id',
        'added_by',
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

    public function getSubscriptionIdsAttribute($value)
    {
        return unserialize($value);
    }

    public function getSubscriptionDataAttribute($value)
    {
        return unserialize($value);
    }
}
