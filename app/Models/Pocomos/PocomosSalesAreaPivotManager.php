<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosSalesAreaPivotManager extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = "pocomos_sales_area_pivot_manager";

    protected $fillable = [
        'office_user_id',
        'sales_area_id'
    ];

    /**Get manager details */
    public function manager()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'office_user_id');
    }
}
