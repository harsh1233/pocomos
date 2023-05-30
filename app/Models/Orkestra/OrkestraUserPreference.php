<?php

namespace App\Models\Orkestra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class OrkestraUserPreference extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'timezone',
        'date_format',
        'time_format',
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
