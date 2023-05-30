<?php

namespace App\Models\Pocomos;

use App\Models\Pocomos\PocomosNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosRecruitNote extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'recruit_id',
        'note_id'
    ];

    public function note()
    {
        return $this->belongsTo(PocomosNote::class, 'note_id');
    }
}
