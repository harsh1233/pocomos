<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosJobService extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'service_type_id',
        'job_id',
        'active',
        'date_modified',
        'date_created'
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

    public function service_data()
    {
        return $this->belongsTo(PocomosPestContractServiceType::class, 'service_type_id');
    }
}
