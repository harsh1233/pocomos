<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosTechnician extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'active',
        'date_modified',
        'date_created',
        'color',
        'commission_type',
        'commission_value',
        'routing_address_id',
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

    public function user_detail()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'user_id');
    }

    public function jobs()
    {
        return $this->hasMany(PocomosJob::class, 'technician_id');
    }

    public function routing_address()
    {
        return $this->belongsTo(PocomosAddress::class, 'routing_address_id');
    }

    public function licenses()
    {
        return $this->hasMany(PocomosTechnicianLicenses::class, 'technician_id', 'id');
    }
}
