<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosRecruiterRegions;
use App\Models\Pocomos\PocomosCompanyOfficeUser;

class PocomosRecruiter extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'active',
        'date_modified',
        'date_created',
        'regional',
        'default_office_id'
    ];

    protected $hidden = ['date_modified', 'date_created'];

    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $record->date_created = date("Y-m-d H:i:s");
        });

        // create a event to happen on updating
        static::updating(function ($record) {
            $record->date_modified = date("Y-m-d H:i:s");
        });
    }

    /* relations */

    public function recruiters()
    {
        return $this->hasMany(PocomosRecruiterRegions::class, 'recruiter_id');
    }

    public function user()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'user_id');
    }
}
