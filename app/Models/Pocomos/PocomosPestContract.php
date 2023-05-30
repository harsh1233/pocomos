<?php

namespace App\Models\Pocomos;

use Exception;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosCounty;
use App\Models\Pocomos\PocomosContract;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosCustomField;
use App\Models\Pocomos\PocomosPestAgreement;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\PocomosPestContractsPest;
use App\Models\Pocomos\PocomosPestpacExportCustomer;
use App\Models\Pocomos\PocomosPestContractServiceType;

class PocomosPestContract extends Model
{
    public $timestamps = false;

    protected $appends = ['exceptions_data'];

    protected $fillable = [
        'contract_id',
        'agreement_id',
        'service_frequency',
        'initial_price',
        'recurring_price',
        'active',
        'frequency_changed',
        'custom_color',
        'contract_color',
        'date_modified',
        'date_created',
        'map_code',
         'service_type_id',
        'regular_initial_price',
        'initial_discount',
        'service_schedule',
        'week_of_the_month',
        'day_of_the_week',
        'date_renewal_end',
         'preferred_time',
        'county_id',
        'parent_contract_id',
        'technician_id',
        'renew_initial_job',
        'number_of_jobs',
        'sent_welcome_email',
        'recurring_discount',
         'remotely_completed',
        'original_value',
        'modifiable_original_value',
        'date_original_value_updated',
        'first_year_contract_value',
        'addendum',
        'exceptions',
        'installment_frequency',
        'installment_start_date',
        'installment_end_date'
    ];

    // public function getServiceFrequencyAttribute($value)
    // {
    //     return unserialize($value);
    // }


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

    public function contract()
    {
        return $this->belongsTo(PocomosContract::class, 'contract_id');
    }

    /**Get agreement details */
    public function pest_agreement_details()
    {
        return $this->belongsTo(PocomosPestAgreement::class, 'agreement_id');
    }

    /**Get service type details */
    public function service_type_details()
    {
        return $this->belongsTo(PocomosPestContractServiceType::class, 'service_type_id');
    }

    /**Get contract tags details */
    public function contract_tags()
    {
        return $this->hasMany(PocomosPestContractsTag::class, 'contract_id');
    }

    /**Get contract all pests details */
    public function all_pests()
    {
        return $this->hasMany(PocomosPestContractsPest::class, 'contract_id')->with('pest');
    }

    /**Get contract targeted pests details */
    public function targeted_pests()
    {
        return $this->hasMany(PocomosPestContractsPest::class, 'contract_id')->with('pest');
    }

    /**Get contract details */
    public function contract_details()
    {
        return $this->belongsTo(PocomosContract::class, 'contract_id')->with('marketing_type');
    }

    /**Get job details */
    public function jobs_details()
    {
        return $this->hasMany(PocomosJob::class, 'contract_id');
    }

    /**Get technician details */
    public function technician_details()
    {
        return $this->belongsTo(PocomosTechnician::class, 'technician_id');
    }

    /**Get contract custom fields details */
    public function custom_fields()
    {
        return $this->hasMany(PocomosCustomField::class, 'pest_control_contract_id');
    }

    /**Get county details */
    public function county()
    {
        return $this->belongsTo(PocomosCounty::class, 'county_id');
    }

    /**Get parent contract details */
    public function parent_contract()
    {
        return $this->belongsTo(self::class);
    }

    public function misc_invoices()
    {
        return $this->hasMany(PocomosPestContractsInvoice::class, 'pest_contract_id')->with('invoice');
    }

    /**
     *
     * @return \Doctrine\Common\Collections\Collection|Job
     */
    public function getInitialJob()
    {
        return $this->jobs_details->filter(function ($job) {
            return $job->type == config('constants.INITIAL');
        })->first();
    }

    public function pest_pac_export_detail()
    {
        return $this->hasOne(PocomosPestpacExportCustomer::class, 'pest_contract_id', 'contract_id');
    }

    public function mission_export_detail()
    {
        return $this->hasOne(PocomosMissionExportContract::class, 'pest_contract_id', 'contract_id');
    }

    /**Get contract specialty pests details */
    public function specialty_pests()
    {
        return $this->hasMany(PocomosPestContractsSpecialtyPest::class, 'contract_id')->with('pest');
    }

    public function getExceptionsDataAttribute()
    {
        return @unserialize($this->exceptions) ? unserialize($this->exceptions) : array();
    }
}
