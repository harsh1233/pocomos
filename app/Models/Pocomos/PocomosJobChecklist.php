<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocomosJobChecklist extends Model
{
    use HasFactory;

    protected $table = "pocomos_job_checklist";
    public $timestamps = false;

    protected $fillable = [
        'job_id',
        'checklist_id'
    ];

    // public function pest()
    // {
    //     return $this->belongsTo(PocomosPest::class, 'pest_id');
    // }
}
