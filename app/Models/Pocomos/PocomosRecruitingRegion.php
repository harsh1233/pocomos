<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocomosRecruitingRegion extends Model
{
    use HasFactory;

    protected $table = 'pocomos_recruiting_region';

    public $timestamps = false;

    protected $fillable = [
        'office_configuration_id',
        'name',
        'description',
        'active',
        'date_modified',
        'date_created'
    ];
}
