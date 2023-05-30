<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosSchedule extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'time_tech_start',
        'time_tech_end',
        'lunch_duration',
        'time_lunch_start',
        'active',
        'date_modified',
         'date_created',
        'type',
        'days_open',
        'date',
         'open',
        'memo',
        'technician_id',
        'office_id',
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
