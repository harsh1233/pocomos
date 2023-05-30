<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosOfficeOptimizationSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'service_type_id',
        'allowed_movement',
        'active',
        'date_created',
        'date_modified',
        'type',
        'office_config_id',
        'optimization_type',
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
