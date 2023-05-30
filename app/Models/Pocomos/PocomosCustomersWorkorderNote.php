<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosNote;

class PocomosCustomersWorkorderNote extends Model
{
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
}
