<?php

namespace App\Models\Orkestra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class OrkestraEmailTemplate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'sender',
        'recipient',
        'cc',
        'headers',
        'subject',
        'body',
        'mime_type',
        'alt_body',
        'alt_mime_type',
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
