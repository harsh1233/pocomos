<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocomosJobPest extends Model
{
    use HasFactory;

    protected $table = "pocomos_job_pests";
    public $timestamps = false;

    protected $fillable = [
        'job_id',
        'pest_id'
    ];

    public function pest()
    {
        return $this->belongsTo(PocomosPest::class, 'pest_id');
    }
}
