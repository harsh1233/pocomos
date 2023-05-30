<?php

namespace App\Models\Pocomos;

use App\Models\Orkestra\OrkestraFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosRecruitsFile extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'recruit_id',
        'file_id'
    ];

    public function attachment()
    {
        return $this->belongsTo(OrkestraFile::class, 'file_id');
    }
}
