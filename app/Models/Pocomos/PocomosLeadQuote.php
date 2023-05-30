<?php

namespace App\Models\Pocomos;

use Exception;
use App\Models\Pocomos\PocomosTaxCode;
use Illuminate\Database\Eloquent\Model;
use App\Models\Orkestra\OrkestraAccount;
use Illuminate\Database\Eloquent\SoftDeletes;

class PocomosLeadQuote extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'service_type_id',
        'county_id',
        'found_by_type_id',
        'signature_id',
        'salesperson_id',
        'pest_agreement_id',
        'agreement_id',
        'account_id',
        'technician_id',
        'slot_id',
        'service_frequency',
        'service_schedule',
        'specific_recurring_schedule',
        'week_of_the_month',
        'day_of_the_week',
        'preferred_time',
        'regular_initial_price',
        'initial_discount',
        'initial_price',
        'recurring_price',
        'map_code',
        'time_slot',
        'initial_date',
        'autopay',
        'auto_renew',
        'active',
        'date_modified',
        'date_created',
        'make_tech_preferred',
        'date_signed_up',
        'date_last_serviced',
        'tax_code',
        'previous_balance',
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

    public function service_type()
    {
        return $this->belongsTo(PocomosPestContractServiceType::class, 'service_type_id');
    }

    public function found_by_type_detail()
    {
        return $this->belongsTo(PocomosMarketingType::class, 'found_by_type_id');
    }

    public function county_detail()
    {
        return $this->belongsTo(PocomosCounty::class, 'county_id');
    }

    public function sales_person_detail()
    {
        return $this->belongsTo(PocomosSalesPeople::class, 'salesperson_id');
    }

    public function pest_agreement_detail()
    {
        return $this->belongsTo(PocomosPestAgreement::class, 'pest_agreement_id');
    }

    public function technician_detail()
    {
        return $this->belongsTo(PocomosTechnician::class, 'technician_id');
    }

    public function tags()
    {
        return $this->hasMany(PocomosLeadQuoteTag::class, 'lead_quote_id');
    }

    public function pests()
    {
        return $this->hasMany(PocomosLeadQuotPest::class, 'lead_quote_id');
    }

    public function specialty_pests()
    {
        return $this->hasMany(PocomosLeadQuoteSpecialtyPest::class, 'lead_quote_id');
    }

    public function account_detail()
    {
        return $this->belongsTo(OrkestraAccount::class, 'account_id');
    }

    public function tax_code_detail()
    {
        return $this->hasOne(PocomosTaxCode::class, 'code', 'tax_code');
    }
}
