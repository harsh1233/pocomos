<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosOnlineBookingAgreements;

class PocomosPestAgreement extends Model
{
    public $timestamps = false;

    public $appends = ['service_frequencies_array'];

    protected $fillable = [
        'agreement_id',
        'service_frequencies',
        'specify_exception',
        'exceptions',
        'active',
        'date_modified',
        'date_created',
        'initial_duration',
        'regular_duration',
        'one_month_followup',
        'max_jobs',
        'delay_welcome_email',
        'default_agreement',
        'allow_dates_in_the_past',
        'allow_addendum',
        'enabled',
        'hideSalesRepo',
        'allow_online_booking',
        'allow_pronexis_booking',
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

    public function agreement_detail()
    {
        return $this->belongsTo(PocomosAgreement::class, 'agreement_id');
    }

    public function getServiceFrequenciesArrayAttribute()
    {
        if (!$this->service_frequencies) {
            return array();
        }
        return unserialize($this->service_frequencies);
    }
}
