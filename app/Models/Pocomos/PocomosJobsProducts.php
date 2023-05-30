<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Pocomos\PocomosJobsProductsAreas;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosJobsProducts extends Model
{
    use HasFactory;

    protected $table = 'pocomos_jobs_products';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'job_id',
        'product_id',
        'service_id',
        'dilution_rate',
        'application_rate',
        'amount',
        'active',
        'invoice_item_id',
        'dilution_unit',
        'dilution_quantity',
        'dilution_liquid_unit',
        'invoice_item_id'
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

    public function invoice_detail()
    {
        return $this->belongsTo(PocomosInvoiceItems::class, 'invoice_item_id');
    }

    public function product()
    {
        return $this->belongsTo(PocomosPestProduct::class, 'product_id');
    }

    public function area_detail()
    {
        return $this->hasMany(PocomosJobsProductsAreas::class, 'applied_product_id');
    }

    public function application_detail()
    {
        return $this->belongsTo(PocomosService::class, 'service_id');
    }
}
