<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use Illuminate\Notifications\Notifiable;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosLeadQuote;
use App\Models\Pocomos\PocomosLeadNotInterestedReason;
use App\Models\Pocomos\PocomosLeadAction;

class PocomosLead extends Model
{
    use Notifiable;
    public $timestamps = false;

    public $appends = ['secondary_emails_array'];

    protected $fillable = [
        'customer_id',
        'contact_address_id',
        'billing_address_id',
        'quote_id',
        'company_name',
        'first_name',
        'last_name',
        'email',
        'secondary_emails',
        'status',
        'not_interested_reason_id',
        'subscribed',
        'active',
        'date_modified',
        'date_created',
        'initial_job_note_id',
        'external_account_id',
        'lead_reminder_id',
        'sales_area_id',
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

    public function addresses()
    {
        return $this->belongsTo(PocomosAddress::class, 'contact_address_id')->with('primaryPhone', 'altPhone', 'region');
    }

    public function billing_addresses()
    {
        return $this->belongsTo(PocomosAddress::class, 'billing_address_id')->with('primaryPhone', 'altPhone', 'region');
    }

    public function contact_address()
    {
        return $this->belongsTo(PocomosAddress::class, 'contact_address_id');
    }

    public function quote_id_detail()
    {
        return $this->belongsTo(PocomosLeadQuote::class, 'quote_id');
    }

    public function not_interested_reason()
    {
        return $this->belongsTo(PocomosLeadNotInterestedReason::class, 'not_interested_reason_id');
    }

    public function initial_job()
    {
        return $this->belongsTo(PocomosNote::class, 'initial_job_note_id');
    }

    public function lead_reminder()
    {
        return $this->belongsTo(PocomosLeadReminder::class, 'lead_reminder_id');
    }

    public function permanent_note()
    {
        return $this->hasMany(PocomosLeadNote::class, 'lead_id');
    }

    public function customer()
    {
        return $this->belongsTo(PocomosCustomer::class, 'customer_id');
    }

    public function getSecondaryEmailsArrayAttribute()
    {
        $secondary_emails = array();
        if ($this->secondary_emails) {
            $secondary_emails =  explode(",", $this->secondary_emails);
        }
        return $secondary_emails;
    }
    
    public function get_actions()
    {
        return $this->hasMany(PocomosLeadAction::class, 'lead_id');
    }
}
