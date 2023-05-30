<?php

namespace App\Models\Orkestra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Orkestra\OrkestraCountryRegion;

class OrkestraResult extends Model
{
    protected $table = 'orkestra_results';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'external_id',
        'message',
        'data',
        'status',
        'transacted',
        'date_transacted',
        'transactor',
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
