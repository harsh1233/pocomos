<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosCustomFieldConfiguration extends Model
{
    protected $table = 'pocomos_custom_field_configuration';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_configuration_id',
        'label',
        'required',
        'active',
        'date_modified',
        'date_created',
        'tech_visible',
        'show_on_acct_status',
        'show_on_precompleted_invoice',
        'show_on_route_map',
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
