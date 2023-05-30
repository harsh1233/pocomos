<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosTermiteStateForm extends Model
{
    protected $table = 'pocomos_termite_state_forms';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'form_body',
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
