<?php

namespace App\Models\Pocomos;

use Exception;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Orkestra\OrkestraUser;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosCompanyOffice;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Orkestra\OrkestraCountryRegion;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosSalespersonProfile;

class PocomosCompanyOfficeUserProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'default_office_user_id',
        'active',
        'date_modified',
        'date_created',
        'phone_id',
        'photo_id',
        'signature_id',
        'deleted_by_user_id',
        'pp_username',
        'bio',
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

    /* relations */
    public function user_details()
    {
        return $this->belongsTo(OrkestraUser::class, 'user_id');
    }

    public function photo_details()
    {
        return $this->belongsTo(OrkestraFile::class, 'photo_id');
    }

    public function phone_details()
    {
        return $this->belongsTo(PocomosPhoneNumber::class, 'phone_id');
    }

    public function pocomosuserprofiles()
    {
        return $this->hasMany(PocomosCompanyOfficeUser::class, 'profile_id');
    }

    public function region_details()
    {
        return $this->belongsTo(OrkestraCountryRegion::class, 'region_id');
    }

    public function salesPersonProfile()
    {
        return $this->hasOne(PocomosSalespersonProfile::class, 'office_user_profile_id');
    }

    public function defaultOfficeUser()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'default_office_user_id');
    }

    public function office_users()
    {
        return $this->hasMany(PocomosCompanyOfficeUser::class, 'profile_id');
    }

    /**Get signature details */
    public function signature_details()
    {
        return $this->belongsTo(OrkestraFile::class, 'signature_id');
    }
}
