<?php

namespace App\Models\Orkestra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Orkestra\OrkestraCountry;
use App\Models\Orkestra\PocomosAddress;

class OrkestraCountryRegion extends Model
{
    protected $table = 'orkestra_countries_regions';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'country_id',
        'name',
        'code',
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

    public function country_detail()
    {
        return $this->belongsTo(OrkestraCountry::class, 'country_id');
    }

    /* relations */

    public function pocomosuserprofilesregions()
    {
        return $this->hasMany(PocomosCompanyOfficeUserProfile::class, 'region_id');
    }
}
