<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;

class PocomosRecruitingOfficeConfiguration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'recruiting_enabled',
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

    /* relations */

    public function pocomosuserprofilesphotos()
    {
        return $this->hasMany(PocomosCompanyOfficeUserProfile::class, 'photo_id');
    }

    public function custom_fields_configuration()
    {
        return $this->hasMany(PocomosRecruitCustomFieldConfiguration::class, 'office_configuration_id');
    }

    public function office_detail()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }
}
