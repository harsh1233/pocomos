<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosJobsProducts;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosPestOfficeDefaultChemsheetsProducts extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'pocomos_pest_office_default_chemsheets_products';

    protected $fillable = [
        'configuration_id',
        'product_id'
    ];

    public function job_product_detail()
    {
        return $this->belongsTo(PocomosJobsProducts::class, 'product_id');
    }
}
