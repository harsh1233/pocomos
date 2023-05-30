<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosOpiniionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'customer_id',
        'job_id',
        'first_name',
        'last_name',
        'email',
        'phone',
          'country_code',
        'notes',
        'response',
        'active',
          'date_created',
        'date_modified',
        'status',
        'errors',

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
