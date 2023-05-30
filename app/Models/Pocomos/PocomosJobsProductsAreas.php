<?php

namespace App\Models\Pocomos;

use App\Models\Pocomos\PocomosArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosJobsProductsAreas extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'pocomos_jobs_products_areas';

    protected $fillable = [
        'applied_product_id',
        'area_id'
    ];

    public function area()
    {
        return $this->belongsTo(PocomosArea::class, 'area_id');
    }
}
