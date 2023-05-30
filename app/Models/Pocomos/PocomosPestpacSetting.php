<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosPestpacSetting extends Model
{
    public $timestamps = false;

    // protected $table = 'pocomos_pestpac_settings';

    protected $fillable = [
        'office_id',
        'pestpac_config_id',
        'validate_and_geocode',
        'branch_name',
        'time_zone',
        'source',
        'block_tomorrow_routes',
        'default_tax_code_id',
        'default_region',
        'active',
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

    public function timezone_detail()
    {
        return $this->belongsTo(PocomosTimezone::class, 'time_zone');
    }
}
