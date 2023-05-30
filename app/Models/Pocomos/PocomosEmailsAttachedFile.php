<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocomosEmailsAttachedFile extends Model
{
    use HasFactory;

    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'email_id',
        'file_id'
    ];
}
