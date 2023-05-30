<?php

namespace App\Models\Pocomos;

use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Database\Eloquent\Model;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Pocomos\PocomosCustomerSalesProfile;

class PocomosActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'type',
        'office_user_id',
        'customer_sales_profile_id',
        'description',
        'context',
        'date_created',
    ];


    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $record->date_created = date("Y-m-d H:i:s");
        });
    }

    /**Get office user details */
    public function office_user_detail()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'office_user_id')->select('id', 'user_id');
    }

    /**Get office user details */
    public function sales_profile_detail()
    {
        return $this->belongsTo(PocomosCustomerSalesProfile::class, 'customer_sales_profile_id');
    }

    // public function getDescriptionAttribute($val)
    // {
    //     return json_decode($val, true);
    // }
}
