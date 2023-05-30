<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosPestpacConfig extends Model
{
    protected $table = 'pocomos_pestpac_config';

    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'pestpac_company_key',
        'pestpac_api_key',
        'pestpac_client_id',
        'pestpac_client_secret',
        'pestpac_username',
        'pestpac_password',
        'vantiv_account_id',
        'vantiv_account_token',
        'vantiv_application_id',
        'vantiv_acceptor_id',
        'status',
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
}
