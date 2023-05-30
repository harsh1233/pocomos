<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosCompanyOfficeUserNote extends Model
{
    public $timestamps = false;
    protected $table = 'pocomos_office_user_notes';
    protected $fillable = [
        'office_user_id',
        'note_id',
    ];
}
