<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosAcsJobEventsAgreement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'acs_event_id',
        'agreement_id'
    ];

    public function agreement_detail()
    {
        return $this->belongsTo(PocomosAgreement::class, 'agreement_id');
    }
}
