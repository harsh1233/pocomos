<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosFreshlimeOfficeConfig extends Model
{
    protected $table = 'pocomos_freshlime_office_config';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'user',
        'password',
        'url',
        'date_created',
        'date_modified',
        'active',
        'office_id',
        'merchant_id',
        'app_id',
        'user_key',
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
