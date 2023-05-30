<?php

namespace App\Models\Pocomos;

use App\Models\Pocomos\PocomosNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosCustomersNote extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'note_id'
    ];

    /**Get note details */
    public function note()
    {
        return $this->belongsTo(PocomosNote::class, 'note_id');
    }
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
