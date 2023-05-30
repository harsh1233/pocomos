<?php

namespace App\Models\Pocomos\Recruitement;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosRecruitingOfficeConfiguration;

class PocomosRecruitOffice extends Model
{
    protected $table = 'pocomos_recruiting_offices';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $hidden = ['date_modified', 'date_created'];

    protected $fillable = [
        'office_configuration_id',
        'name',
        'description',
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

    public function office_configuration()
    {
        return $this->belongsTo(PocomosRecruitingOfficeConfiguration::class, 'office_configuration_id')->with('office_detail');
    }
}
