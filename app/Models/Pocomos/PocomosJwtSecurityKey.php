<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosJwtSecurityKey extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'jwt_security_key',
    ];
}
