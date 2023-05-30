<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosOfficeBirdeyeSetting extends Model
{
    protected $table = 'pocomos_office_birdeye_settings';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'api_key',
        'tags',
        'enabled',
        'active',
        'date_created',
        'date_modified',
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
