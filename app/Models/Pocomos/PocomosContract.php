<?php

namespace App\Models\Pocomos;

use App\Models\Pocomos\PocomosJob;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosTaxCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosStatusReason;
use App\Models\Pocomos\PocomosReportsSearchState;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosReportsContractState;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosContract extends Model
{
    use HasFactory;

    public $timestamps = false;

    // protected $appends = ['is_default_selected'];

    protected $fillable = [
        'profile_id',
        'agreement_id',
        'signature_id',
        'billing_frequency',
        'two_payments_days_limit',
        'status',
        'date_start',
        'date_end',
        'active',
        'salesperson_id',
        'auto_renew',
        'renewal_date',
        'found_by_type_id',
        'tax_code_id',
        'sales_status_id',
        'sales_status_modified',
        'date_cancelled',
        'signed',
        'autopay_signature_id',
        'sales_tax',
        'status_reason_id',
        'original_value',
        'date_original_value_updated',
        'purchase_order_number',
        'renewal_disabled',
        'number_of_payments',
        'renew_installment_initial_price',
        'renew_installment_start_date',
        'renew_number_of_payment',
        'renew_installment_frequency',
        'renew_installment_price'
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

    /**Get greement details */
    public function agreement_details()
    {
        return $this->belongsTo(PocomosAgreement::class, 'agreement_id');
    }

    /**Get tax details */
    public function tax_details()
    {
        return $this->belongsTo(PocomosTaxCode::class, 'tax_code_id');
    }

    /**Get contract details */
    public function pest_contract_details()
    {
        return $this->hasOne(PocomosPestContract::class, 'contract_id');
    }

    /**Get agreement details */
    public function agreement_detail()
    {
        return $this->belongsTo(PocomosAgreement::class, 'agreement_id');
    }

    /**Get signature details */
    public function signature_details()
    {
        return $this->belongsTo(OrkestraFile::class, 'signature_id');
    }

    /**Get profile details */
    public function profile_details()
    {
        return $this->belongsTo(PocomosCustomerSalesProfile::class, 'profile_id');
    }

    /**Get auto pay signature details */
    public function autopay_signature_details()
    {
        return $this->belongsTo(OrkestraFile::class, 'autopay_signature_id');
    }

    /**Get job details */
    public function status_reason()
    {
        return $this->hasOne(PocomosStatusReason::class, 'id', 'status_reason_id');
    }

    /**Get salespeople details */
    public function salespeople()
    {
        return $this->belongsTo(PocomosSalesPeople::class, 'salesperson_id');
    }

    /**Get sales_status details */
    public function sales_status()
    {
        return $this->belongsTo(PocomosSalesStatus::class, 'sales_status_id');
    }

    /**Get marketing type details */
    public function marketing_type()
    {
        return $this->belongsTo(PocomosMarketingType::class, 'found_by_type_id');
    }

    /**Get sales status details */
    public function state_report()
    {
        return $this->hasMany(PocomosReportsContractState::class, 'contract_id');
    }

    /**Get search report details */
    public function search_report_state()
    {
        return $this->belongsTo(PocomosReportsSearchState::class, 'contract_id');
    }

    // public function getIsDefaultSelectedAttribute()
    // {
    //     $session = Session::get('current_contract_context');

    //     $is_default_selected = false;
    //     if(isset($session['contract']) && $session['contract'] == $this->id){
    //         $is_default_selected = true;
    //     }
    //     return $is_default_selected;
    // }

    /**Get jobs details */
    public function jobs_details()
    {
        return $this->hasMany(PocomosJob::class, 'contract_id');
    }

    public function discount_types()
    {
        return $this->hasMany(PocomosPestDiscountTypeItem::class, 'contract_id');
    }

    public function invoices()
    {
        return $this->hasMany(PocomosInvoice::class, 'contract_id', 'id');
    }
}
