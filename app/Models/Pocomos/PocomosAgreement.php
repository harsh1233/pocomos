<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosCustomAgreementTemplate;
use App\Models\Pocomos\PocomosPestContractServiceType;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class PocomosAgreement extends Model
{
    protected $table = 'pocomos_agreements';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public $appends = ['billing_frequencies_array', 'contract_terms_array', 'autopay_terms_array'];

    protected $fillable = [
        'office_id',
        'name',
        'description',
        'enableBillingFrequencies',
        'billing_frequencies',
        'length',
        'agreement_body',
        'invoice_intro',
        'active',
        'date_modified',
        'date_created',
        'signature_agreement_text',
        'contract_terms',
        'autopay_terms',
        'auto_renew',
        'auto_renew_lock',
        'auto_renew_initial',
        'initial_job_lock',
        'auto_renew_installments',
        'auto_renew_installments_lock',
        'variable_length',
        'bill_immediately',
        'specifyNumberOfJobs',
        'regular_initial_price',
        'initial_price',
        'recurring_price',
        'installment_default_price',
        'installment_default_number_payments',
        'installment_default_frequency',
        'monthly_default_normal_initial',
        'monthly_default_initial_price',
        'monthly_default_price',
        'due_at_signup_default_price',
        'two_payment_default_first_payment_price',
        'two_payment_default_second_payment_price',
        'enable_default_price',
        'custom_agreement_id',
        'enable_new_pdf_layout',
        'position',
        'default_service_type',
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

    /**Get office details */
    public function office_details()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }

    /**Get service type */
    public function service_type_detail()
    {
        return $this->belongsTo(PocomosPestContractServiceType::class, 'default_service_type');
    }

    /**Get custom agreement template */
    public function custom_agreement_template()
    {
        return $this->belongsTo(PocomosCustomAgreementTemplate::class, 'custom_agreement_id');
    }

    /**Get service type */
    public function pest_agreement_detail()
    {
        return $this->hasOne(PocomosPestAgreement::class, 'agreement_id');
    }

    public function getBillingFrequenciesArrayAttribute()
    {
        if (!$this->billing_frequencies) {
            return array();
        }
        return unserialize($this->billing_frequencies);
    }

    public function getContractTermsArrayAttribute()
    {
        if (!$this->contract_terms) {
            return array();
        }
        return unserialize($this->contract_terms);
    }

    public function getAutopayTermsArrayAttribute()
    {
        if (!$this->autopay_terms) {
            return array();
        }
        return unserialize($this->autopay_terms);
    }
}
