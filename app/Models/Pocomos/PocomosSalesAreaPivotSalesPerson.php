<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosSalesPeople;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosSalesAreaPivotSalesPerson extends Model
{
    use HasFactory;

    protected $table = "pocomos_sales_area_pivot_salesperson";

    public $timestamps = false;

    protected $fillable = [
        'salesperson_id',
        'sales_area_id'
    ];

    /**Get sales person details */
    public function sales_person()
    {
        return $this->belongsTo(PocomosSalesPeople::class, 'salesperson_id');
    }
}
