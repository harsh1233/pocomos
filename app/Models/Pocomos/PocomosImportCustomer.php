<?php

namespace App\Models\Pocomos;

use Exception;
use App\Models\Pocomos\PocomosCounty;
use App\Models\Pocomos\PocomosTaxCode;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosImportBatch;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosMarketingType;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Orkestra\OrkestraCountryRegion;
use App\Models\Pocomos\PocomosPestContractServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosImportCustomer extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'region_id',
        'billing_region_id',
        'upload_batch_id',
        'company_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'alt_phone',
        'street',
        'suite',
        'city',
        'region',
        'postal_code',
        'map_code',
        'name_on_card',
        'card_number',
        'exp_month',
        'exp_year',
        'billing_street',
        'billing_suite',
        'billing_city',
        'billing_region',
        'billing_postal_code',
        'date_last_service',
        'date_next_service',
        'price',
        'imported',
        'errors',
        'active',
        'country_id',
        'salesperson_id',
        'found_by_type_id',
        'original_country',
        'previous_balance',
        'original_service_frequency',
        'service_frequency',
        'original_salesperson',
        'original_found_by_type',
        'last_technician_id',
        'date_signed_up',
        'original_last_technician',
        'notes',
        'tax_code_id',
        'original_tax_code',
        'service_type_id',
        'day_of_the_week',
        'origial_day_of_the_week',
        'week_of_the_month',
        'original_week_of_the_month',
        'intial_service_price',
        'original_service_type',
        'external_identifier',
        'original_county',
        'original_day_of_the_week',
        'initial_service_price',

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

    public function batch_details()
    {
        return $this->belongsTo(PocomosImportBatch::class, 'upload_batch_id');
    }

    public function region_details()
    {
        return $this->belongsTo(OrkestraCountryRegion::class, 'region_id')->with('country_detail');
    }

    public function billing_region_details()
    {
        return $this->belongsTo(OrkestraCountryRegion::class, 'billing_region_id')->with('country_detail');
    }

    public function county_details()
    {
        return $this->belongsTo(PocomosCounty::class, 'county_id');
    }

    public function salesperson_details()
    {
        return $this->belongsTo(PocomosSalesPeople::class, 'salesperson_id')->with('office_user_details.user_details');
    }

    public function found_by_type_details()
    {
        return $this->belongsTo(PocomosMarketingType::class, 'found_by_type_id');
    }

    public function technician_details()
    {
        return $this->belongsTo(PocomosTechnician::class, 'last_technician_id')->with('user_detail.user_details');
    }

    public function tax_code_details()
    {
        return $this->belongsTo(PocomosTaxCode::class, 'tax_code_id');
    }

    public function service_type_details()
    {
        return $this->belongsTo(PocomosPestContractServiceType::class, 'service_type_id');
    }
}
