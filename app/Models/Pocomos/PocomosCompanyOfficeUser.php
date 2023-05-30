<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;

class PocomosCompanyOfficeUser extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'active',
        'office_id',
        'profile_id',
        'deleted',
        'deactivated_with_office',
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

    public function profile_details()
    {
        return $this->belongsTo(PocomosCompanyOfficeUserProfile::class, 'profile_id');
    }
    public function company_details()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }
    public function technician_user_details()
    {
        return $this->hasOne(PocomosTechnician::class, 'user_id');
    }
    public function user_details_name()
    {
        return $this->belongsTo(OrkestraUser::class, 'user_id')->select('id', 'first_name', 'last_name');
    }

    public function pest_contract_service_types()
    {
        return $this->hasMany(PocomosPestContractServiceType::class, 'office_id', 'office_id');
    }

    public function salespeople()
    {
        return $this->hasOne(PocomosSalesPeople::class, 'user_id');
    }
}
