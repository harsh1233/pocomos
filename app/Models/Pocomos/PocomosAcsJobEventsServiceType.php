<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosAcsJobEventsServiceType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'acs_event_id',
        'service_type_id'
    ];

    public function service_type_detail()
    {
        return $this->belongsTo(PocomosPestContractServiceType::class, 'service_type_id');
    }
}
