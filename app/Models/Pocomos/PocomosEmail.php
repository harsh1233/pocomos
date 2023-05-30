<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class PocomosEmail extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'office_user_id',
        'office_id',
        'customer_sales_profile_id',
        'subject',
        'body',
        'reply_to',
        'reply_to_name',
        'sender',
        'sender_name',
        'type',
        'active',
        'date_modified',
        'date_created',
        'lead_id',
        'receiving_office_user_id'
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
    public function sales_profile_detail()
    {
        return $this->belongsTo(PocomosCustomerSalesProfile::class, 'customer_sales_profile_id');
    }


    /**Get office user details */
    public function office_user_detail()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'office_user_id')->select('id', 'user_id');
    }

    /**Get Receive office user details */
    public function receive_office_user_detail()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'receiving_office_user_id')->select('id', 'user_id');
    }

    public function office()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id')->select('id', 'name', 'contact_name', 'list_name');
    }
}
