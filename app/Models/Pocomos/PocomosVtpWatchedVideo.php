<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocomosVtpWatchedVideo extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'profile_id',
        'video_id',
    ];
}
