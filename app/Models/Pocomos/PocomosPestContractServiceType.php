<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosPestContractServiceType extends Model
{
    protected $table = 'pocomos_pest_contract_service_types';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'name',
        'description',
        'requires_license',
        'color',
        'active',
        'date_modified',
        'date_created',
        'position',
        'shows_on_estimates',
        'default_cost',
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
