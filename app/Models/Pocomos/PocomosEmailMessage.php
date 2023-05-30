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

class PocomosEmailMessage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'email_id',
        'recipient',
        'recipient_name',
        'date_status_changed',
        'status',
        'external_id',
        'active',
        'date_modified',
        'date_created',
        'office_user_id',
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

    /**Get office user details */
    public function office_user_detail()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'office_user_id')->select('id', 'user_id');
    }
}
