<?php

namespace App\Models\Orkestra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Orkestra\OrkestraCountryRegion;

class PocomosUserTransaction extends Model
{
    protected $table = 'pocomos_user_transactions';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'transaction_id',
        'user_id',
        'past_due',
        'active',
        'date_modified',
        'date_created',
        'memo',
        'type',
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
