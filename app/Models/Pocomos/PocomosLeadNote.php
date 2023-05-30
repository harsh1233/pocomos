<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosNote;

class PocomosLeadNote extends Model
{
    protected $table = "pocomos_leads_notes";
    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'note_id'
    ];

    public function pocomosLeadsNote()
    {
        return $this->hasOne(PocomosNote::class, 'user_id');
    }

    public function note_detail()
    {
        return $this->belongsTo(PocomosNote::class, 'note_id');
    }

    public function lead_detail()
    {
        return $this->belongsTo(PocomosLead::class, 'lead_id');
    }
}
