<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosMissionExportContract extends Model
{
    protected $table = 'pocomos_mission_export_contract';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'customer_id',
        'pest_contract_id',
        'status',
        'test_env',
        'fileId_in_mission',
        'etag_in_mission',
        'processed_at',
        'errors',
        'active',
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

    /**Get customer with contact address details */
    public function customer()
    {
        return $this->belongsTo(PocomosCustomer::class, 'customer_id');
    }

    public function pestContract()
    {
        return $this->belongsTo(PocomosPestContract::class, 'pest_contract_id');
    }
}
