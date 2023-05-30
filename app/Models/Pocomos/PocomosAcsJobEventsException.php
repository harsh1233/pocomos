<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosAcsJobEventsException extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'acs_event_id',
        'exception_id'
    ];

    public function exception_tag_detail()
    {
        return $this->belongsTo(PocomosTag::class, 'exception_id');
    }
}
