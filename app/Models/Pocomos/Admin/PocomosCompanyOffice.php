<?php

namespace App\Models\Pocomos\Admin;

use Exception;
use App\Models\Pocomos\PocomosTeam;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\PocomosAddress;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosOfficeSetting;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;

class PocomosCompanyOffice extends Model
{
    protected $table = 'pocomos_company_offices';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public $appends = ['email_data'];

    protected $fillable = [
        'logo_file_id',
        'contact_address_id',
        'billing_address_id',
        'parent_id',
        'name',
        'url',
        'fax',
        'contact_name',
        'enabled',
        'active',
        'date_modified',
        'date_created',
        'license_number',
        'list_name',
        'email',
        'routing_address_id',
        'billed_separately',
        'customer_portal_link',
        'average_contract_value',
    ];


    public function coontact_address()
    {
        return $this->belongsTo(PocomosAddress::class, 'contact_address_id')->with('primaryPhone', 'altPhone', 'region');
    }

    public function contact()
    {
        return $this->belongsTo(PocomosAddress::class, 'contact_address_id');
    }

    public function routing_address()
    {
        return $this->belongsTo(PocomosAddress::class, 'routing_address_id')->with('primaryPhone', 'altPhone', 'region');
    }

    public function billing_address()
    {
        return $this->belongsTo(PocomosAddress::class, 'billing_address_id')->with('primaryPhone', 'altPhone', 'region');
    }

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

    public function companyOfficeUser()
    {
        return $this->hasOne(PocomosCompanyOfficeUserProfile::class, 'default_office_user_id')->with('user_details');
    }

    public function logo()
    {
        return $this->belongsTo(OrkestraFile::class, 'logo_file_id');
    }

    public function teams()
    {
        return $this->hasMany(PocomosTeam::class, 'office_id', 'id');
    }

    public function getChildOffices()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function office_configuration()
    {
        return $this->hasOne(PocomosPestOfficeSetting::class, 'office_id');
    }

    public function getParentdOffices()
    {
        return $this->belongsTo(self::class, 'id');
    }

    public function office_settings()
    {
        return $this->hasOne(PocomosOfficeSetting::class, 'office_id');
    }

    /**Get parent details */
    public function getParent()
    {
        $office = self::find($this->id);
        if ($this->parent_id) {
            $office = self::find($this->parent_id);
        }
        return $office;
    }

    public function getChildWithParentOffices()
    {
        $allOffices = self::with('office_settings', 'logo', 'coontact_address')->where('parent_id', $this->id)->get()->toArray();
        if (!$allOffices) {
            $allOffices = self::whereId($this->id)->first();
            $allOffices = self::with('office_settings', 'logo', 'coontact_address')->whereId($allOffices->parent_id)->get()->toArray();
        }
        $parentOffice = self::with('office_settings', 'logo', 'coontact_address')->whereId($this->id)->first()->toArray();
        $allOffices[] = $parentOffice;

        return $allOffices;
    }

    public function getEmailDataAttribute()
    {
        return @unserialize($this->email) ? unserialize($this->email) : array();
    }
}
