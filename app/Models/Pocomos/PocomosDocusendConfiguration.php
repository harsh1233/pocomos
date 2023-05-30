<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosDocusendConfiguration extends Model
{
    protected $table = 'pocomos_docusend_configuration';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'active',
        'office_id',
        'user_email',
        'user_password',
        'live',
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
