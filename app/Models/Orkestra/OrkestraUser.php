<?php

namespace App\Models\Orkestra;

use App\Http\Controllers\Functions;
use Exception;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Orkestra\OrkestraUserGroup;
use App\Models\Pocomos\PocomosSalesPeople;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use Illuminate\Foundation\Auth\User as Authenticatable;

class OrkestraUser extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;
    use Functions;

    public $timestamps = false;
    protected $appends = ['full_name', 'default_office', 'primary_role'];

    protected $hidden = ['password'];

    protected $fillable = [
        'username',
        'salt',
        'password',
        'first_name',
        'last_name',
        'expired',
        'locked',
        'active',
        'date_modified',
        'date_created',
        'email',
        'last_login',

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

    public function pocomosuserprofiles()
    {
        return $this->hasMany(PocomosCompanyOfficeUserProfile::class, 'user_id');
    }

    public function technicians()
    {
        return $this->hasOne(PocomosTechnician::class, 'user_id');
    }

    public function salesPerson()
    {
        return $this->hasOne(PocomosSalesPeople::class, 'user_id');
    }

    public function permissions()
    {
        return $this->hasMany(OrkestraUserGroup::class, 'user_id')->with('permission');
    }

    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function pocomos_company_office_user()
    {
        return $this->hasOne(PocomosCompanyOfficeUser::class, 'user_id');
    }

    public function pocomos_company_office_users()
    {
        return $this->hasMany(PocomosCompanyOfficeUser::class, 'user_id');
    }

    /**For logged in user get default office details */
    public function getDefaultOfficeAttribute()
    {
        $defaultOffice = null;
        $userProfile = PocomosCompanyOfficeUserProfile::where("user_id", $this->id)->first();
        if ($userProfile) {
            $officeUser = PocomosCompanyOfficeUser::find($userProfile->default_office_user_id);
            if ($officeUser) {
                $defaultOffice = $officeUser->office_id;
            }
        }
        return $defaultOffice;
    }

    /**For logged in user get default office details */
    public function getPrimaryRoleAttribute()
    {
        $role = 'No Privileges';
        foreach ($this->permissions->toArray() as $group) {
            if (in_array($group['permission']['role'], $this->getPrimaryRoles())) {
                $role = $group['permission']['role'];
            }
        };

        return $role;
    }
}
