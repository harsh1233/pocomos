<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosRecruiter;

class PocomosRecruiterRegions extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'recruiter_id',
        'region_id'
    ];

    /* relations */

    public function pocomosuserprofilesphotos()
    {
        return $this->hasOne(PocomosRecruiter::class, '	id');
    }
}
