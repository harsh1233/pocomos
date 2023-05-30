<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosNote;

class PocomosCustomerNote extends Model
{
    protected $table = "pocomos_customers_notes";
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'note_id'
    ];

    public function pocomosLeadsNote()
    {
        return $this->hasOne(PocomosNote::class, 'user_id');
    }

    public function note_detail()
    {
        return $this->belongsTo(PocomosNote::class, 'id');
    }

    public function lead_detail()
    {
        return $this->belongsTo(PocomosLead::class, 'lead_id');
    }
}
