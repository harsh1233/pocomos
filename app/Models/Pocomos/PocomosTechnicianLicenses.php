<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosPestContractServiceType;

class PocomosTechnicianLicenses extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'technician_id',
        'service_type_id',
        'license_number',
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

    public function service_type()
    {
        return $this->belongsTo(PocomosPestContractServiceType::class, 'service_type_id');
    }
}
