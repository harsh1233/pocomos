<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosPestEstimates;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Pocomos\PocomosPestContractServiceType;
use App\Models\Pocomos\PocomosTaxCode;

class PocomosPestEstimateProducts extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'estimate_id',
        'product_id',
        'service_type_id',
        'cost',
        'quantity',
        'tax',
        'tax_code_id',
        'calculate_amount',
        'amount',
        'description',
        'active'
    ];

    protected $appends = ['name'];

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

    public function getNameAttribute()
    {
        if ($this->product_id != null) {
            $data = array();
            $pestproduct = PocomosPestProduct::where('id', $this->product_id)->first();

            $data['name'] =   $pestproduct->name ?? '';
            $data['description'] =   $pestproduct->description ?? '';
            return $data;
        }

        if ($this->service_type_id != null) {
            $data = array();
            $pestproduct = PocomosPestContractServiceType::where('id', $this->service_type_id)->first();

            $data['name'] =   $pestproduct->name;
            $data['description'] =   $pestproduct->description;
            return $data;
        }
    }


    public function pestEstimatesProducts()
    {
        return $this->belongsTo(PocomosPestEstimates::class, 'estimate_id');
    }
    public function product_data()
    {
        return $this->belongsTo(PocomosPestProduct::class, 'product_id');
    }
    public function service_data()
    {
        return $this->belongsTo(PocomosPestContractServiceType::class, 'service_type_id');
    }

    public function tax_details()
    {
        return $this->belongsTo(PocomosTaxCode::class, 'tax_code_id');
    }
}
