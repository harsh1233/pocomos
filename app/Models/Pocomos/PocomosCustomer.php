<?php

namespace App\Models\Pocomos;

use Exception;
use App\Models\Pocomos\PocomosLead;
use App\Models\Pocomos\PocomosAddress;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosSubCustomer;
use App\Models\Pocomos\PocomosStatusReason;
use App\Models\Pocomos\PocomosCustomersFile;
use App\Models\Pocomos\PocomosCustomersNote;
use App\Models\Pocomos\PocomosCustomerState;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosCustomersWorkorderNote;
use Illuminate\Notifications\Notifiable;

class PocomosCustomer extends Model
{
    use Notifiable;

    public $timestamps = false;

    public $appends = ['secondary_emails_array'];

    protected $fillable = [
        'created_by_id',
        'modified_by_id',
        'contact_address_id',
        'billing_address_id',
        'first_name',
        'last_name',
        'email',
        'subscribed',
        'status',
        'active',
        'date_modified',
        'date_created',
        'email_verified',
        'company_name',
        'secondary_emails',
        'default_job_duration',
        'date_modified_aggregate',
        'billing_name',
        'deliver_email',
        'date_deactivated',
        'external_account_id',
        'status_reason_id',
        'unpaid_note',
        'account_type',
        'route_map_note',
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

    public function customer_files()
    {
        return $this->hasMany(PocomosCustomersFile::class, 'customer_id');
    }

    public function contact_address()
    {
        return $this->belongsTo(PocomosAddress::class, 'contact_address_id');
    }

    public function billing_address()
    {
        return $this->belongsTo(PocomosAddress::class, 'billing_address_id');
    }

    public function status_details()
    {
        return $this->belongsTo(PocomosAddress::class, 'billing_address_id');
    }

    public function sales_profile()
    {
        return $this->hasOne(PocomosCustomerSalesProfile::class, 'customer_id');
    }

    public function notes_details()
    {
        return $this->hasMany(PocomosCustomersNote::class, 'customer_id');
    }

    public function state_details()
    {
        return $this->hasOne(PocomosCustomerState::class, 'customer_id');
    }

    public function status_reason()
    {
        return $this->belongsTo(PocomosStatusReason::class, 'status_reason_id');
    }

    public function parent()
    {
        return $this->hasOne(PocomosSubCustomer::class, 'child_id');
    }

    public function child()
    {
        return $this->hasMany(PocomosSubCustomer::class, 'parent_id');
    }

    public function coontact_address()
    {
        return $this->belongsTo(PocomosAddress::class, 'contact_address_id')->with('primaryPhone', 'altPhone', 'region');
    }

    public function worker_notes_details()
    {
        return $this->hasMany(PocomosCustomersWorkorderNote::class, 'customer_id');
    }

    public function lead_detail()
    {
        return $this->hasOne(PocomosLead::class, 'customer_id');
    }

    public function getSecondaryEmailsArrayAttribute()
    {
        $secondary_emails = array();
        if ($this->secondary_emails) {
            $secondary_emails =  explode(",", $this->secondary_emails);
        }
        return $secondary_emails;
    }
}
