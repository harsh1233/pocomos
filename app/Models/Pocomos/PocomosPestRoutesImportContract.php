<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosPestRoutesImportContract extends Model
{
    protected $table = 'pocomos_pest_routes_import_contract';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'domain',
        'total_customer_count',
        'customer_ids',
        'employee_ids',
        'total_employee_count',
        'subscription_ids',
        'total_subscription_count',
        'service_type_ids',
        'total_service_type_count',
        'processing_time',
        'processed_at',
        'office_id',
        'errors',
        'status',
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
