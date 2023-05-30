<?php

namespace App\Models\Pocomos\Recruitement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosRecruitAgreement extends Model
{
    protected $table = 'pocomos_recruit_agreements';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $hidden = ['date_modified', 'date_created'];

    protected $fillable = [
        'recruiting_office_configuration_id',
        'name',
        'description',
        'agreement_body',
        'initials',
        'default_agreement',
        'active',
        'date_modified',
        'date_created',
        'email_pdf',
        'address_for_pdf',
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
