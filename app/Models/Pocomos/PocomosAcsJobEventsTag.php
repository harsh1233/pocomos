<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosAcsJobEventsTag extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'acs_event_id',
        'tag_id'
    ];

    public function tag_detail()
    {
        return $this->belongsTo(PocomosTag::class, 'tag_id');
    }
}
