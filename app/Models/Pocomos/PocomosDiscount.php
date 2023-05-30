<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosDiscount extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'value_type',
        'amount',
        'modify_description',
        'modify_rate',
        'office_id',
        'auto_renew',
        'is_available',
        'active',
        'deleted',
        'position',
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
