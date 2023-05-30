<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosAcsEvent extends Model
{
    protected $table = 'pocomos_acs_events';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'office_id',
        'form_letter_id',
        'voice_form_letter_id',
        'sms_form_letter_id',
        'job_type',
        'service_type_id',
        'agreement_id',
        'tag_id',
        'amount_of_time',
        'unit_of_time',
        'before_after',
        'enabled',
        'autopay',
        'active',
        'customer_autopay',
        'date_created',
        'date_modified',
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

    public function exception_tags()
    {
        return $this->hasMany(PocomosAcsJobEventsException::class, 'acs_event_id');
    }

    public function acs_agreements()
    {
        return $this->hasMany(PocomosAcsJobEventsAgreement::class, 'acs_event_id');
    }

    public function acs_serice_types()
    {
        return $this->hasMany(PocomosAcsJobEventsServiceType::class, 'acs_event_id');
    }

    public function acs_tags()
    {
        return $this->hasMany(PocomosAcsJobEventsTag::class, 'acs_event_id');
    }
}
