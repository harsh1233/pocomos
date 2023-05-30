<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosAcsEvent;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Pocomos\PocomosJob;

class PocomosAcsNotification extends Model
{
    protected $table = 'pocomos_acs_notifications';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'acs_event_id',
        'form_letter_id',
        'sms_form_letter_id',
        'event_type',
        'customer_id',
        'job_id',
        'invoice_id',
        'notification_time',
        'sent',
        'active',
        'date_created',
        'date_modified',
        'office_id',
        'pest_control_agreement_id',
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
    public function ace_event()
    {
        return $this->belongsTo(PocomosAcsEvent::class, 'acs_event_id');
    }

    public function customers()
    {
        return $this->belongsTo(PocomosCustomer::class, 'customer_id');
    }

    public function jobs()
    {
        return $this->belongsTo(PocomosJob::class, 'job_id');
    }
    public function pest_agreement_detail()
    {
        return $this->belongsTo(PocomosPestAgreement::class, 'pest_control_agreement_id');
    }
}
