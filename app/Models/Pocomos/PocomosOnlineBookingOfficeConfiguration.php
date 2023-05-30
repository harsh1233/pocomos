<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosOnlineBookingOfficeConfiguration extends Model
{
    public $timestamps = false;

    protected $table = 'pocomos_online_booking_office_configuration';

    protected $primaryKey = 'id';

    protected $fillable = [
        'active',
        'date_modified',
        'date_created',
        'office_id',
        'enabled',
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
