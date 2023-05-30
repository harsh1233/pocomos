<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosSmsFormLetter extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'title',
        'description',
        'message',
        'confirm_job',
        'require_job',
        'active',
        'date_modified',
        'date_created',
        'category',
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
