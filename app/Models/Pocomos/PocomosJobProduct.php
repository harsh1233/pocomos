<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosPestProduct;
use Illuminate\Database\Eloquent\SoftDeletes;

class PocomosJobProduct extends Model
{
    public $timestamps = false;

    protected $table = 'pocomos_jobs_products';

    protected $fillable = [
        'job_id',
        'product_id',
        'service_id',
        'dilution_rate',
        'dilution_unit',
        'dilution_quantity',
        'dilution_liquid_unit',
        'application_rate',
        'amount',
        'active',
        'date_modified',
        'date_created',
        'invoice_item_id',
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

    public function product()
    {
        return $this->belongsTo(PocomosPestProduct::class, 'product_id');
    }

    public function invoice_item()
    {
        return $this->belongsTo(PocomosInvoiceItems::class, 'invoice_item_id');
    }

    public function areas()
    {
        return $this->hasMany(PocomosJobsProductsAreas::class, 'applied_product_id')->with('area');
    }

    public function service()
    {
        return $this->belongsTo(PocomosService::class, 'service_id');
    }
}
