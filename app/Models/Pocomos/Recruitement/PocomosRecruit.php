<?php

namespace App\Models\Pocomos\Recruitement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosRecruit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'recruit_status_id',
        'recruit_contract_id',
        'recruiter_id',
        'recruiting_office_id',
        'current_address_id',
        'primary_address_id',
        'legal_name',
        'first_name',
        'last_name',
        'date_of_birth',
        'email',
        'active',
        'user_id',
        'remote_user_id',
        'linked',
        'profile_pic_id',
        'recruiting_region_id',
        'desired_username',
        'desired_password',
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
