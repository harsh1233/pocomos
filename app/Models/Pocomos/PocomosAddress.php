<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosDistributor;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Orkestra\OrkestraCountryRegion;

class PocomosAddress extends Model
{
    protected $table = 'pocomos_addresses';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $hidden = ['date_modified', 'date_created'];

    protected $fillable = [
        'region_id',
        'street',
        'suite',
        'city',
        'postal_code',
        'active',
        'date_created',
        'date_modified',
        'latitude',
        'longitude',
        'validated',
        'valid',
        'phone_id',
        'alt_phone_id',
        'override_geocode',
    ];

    /* helper functions */

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

    public function address_details()
    {
        return $this->hasOne(PocomosCompanyOffice::class, 'contact_address_id')->with('companyOfficeUser');
    }
    public function primaryPhone()
    {
        return $this->belongsTo(PocomosPhoneNumber::class, 'phone_id');
    }
    public function altPhone()
    {
        return $this->belongsTo(PocomosPhoneNumber::class, 'alt_phone_id');
    }
    public function region()
    {
        return $this->belongsTo(OrkestraCountryRegion::class, 'region_id')->with('country_detail');
    }
}
