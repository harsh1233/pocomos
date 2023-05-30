<?php

namespace App\Models\Pocomos\Recruitement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosRecruitStatus extends Model
{
    protected $table = 'pocomos_recruit_status';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $hidden = ['date_modified', 'date_created'];

    protected $fillable = [
        'recruiting_office_configuration_id',
        'name',
        'description',
        'default_status',
        'active',
        'date_modified',
        'date_created',
    ];


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
}
