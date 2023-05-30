<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosCustomField extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'custom_field_configuration_id',
        'pest_control_contract_id',
        'value',
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

    /**Get custom field details */
    public function custom_field()
    {
        return $this->belongsTo(PocomosCustomFieldConfiguration::class, 'custom_field_configuration_id');
    }
}
