<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosPestpacServiceType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'pocomos_service_type_id',
        'pp_service_setup_type',
        'pp_service_setup_color',
        'pp_service_order_type',
        'pp_service_order_color',
        'pp_service_schedule',
        'pp_service_description',
        'default_service',
        'enabled',
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

    public function contract_service_type()
    {
        return $this->belongsTo(PocomosPestContractServiceType::class, 'pocomos_service_type_id');
    }
}
