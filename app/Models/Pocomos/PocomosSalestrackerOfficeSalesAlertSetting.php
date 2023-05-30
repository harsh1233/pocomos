<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosSalestrackerOfficeSalesAlertSetting extends Model
{
    protected $table = 'pocomos_salestracker_office_sales_alert_settings';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'config_id',
        'office_id',
        'no_of_sales',
        'interval_type',
        'notify_type',
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
