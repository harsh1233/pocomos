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

class PocomosCustomerSalesProfile extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $hidden = ['date_modified', 'date_created'];

    protected $fillable = [
        'points_account_id',
        'autopay_account_id',
        'customer_id',
        'office_id',
        'salesperson_id',
        'autopay',
        'balance',
        'active',
        'date_modified',
        'date_created',
        'office_user_id',
        'date_signed_up',
        'external_account_id',
        'imported'
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

    /**Get points account details */
    public function points_account()
    {
        return $this->belongsTo(OrkestraAccount::class, 'points_account_id');
    }

    /**Get points account details */
    public function autopay_account()
    {
        return $this->belongsTo(OrkestraAccount::class, 'autopay_account_id');
    }

    /**Get points account details */
    public function external_account()
    {
        return $this->belongsTo(OrkestraAccount::class, 'external_account_id');
    }

    /**Get points account details */
    public function sales_people()
    {
        return $this->belongsTo(PocomosSalesPeople::class, 'salesperson_id');
    }

    /**Get points account details */
    public function contract_details()
    {
        return $this->hasMany(PocomosContract::class, 'profile_id');
    }

    /**Get customer details */
    public function customer_details()
    {
        return $this->belongsTo(PocomosCustomer::class, 'customer_id')->with('contact_address.primaryPhone', 'contact_address.altPhone', 'billing_address.primaryPhone', 'billing_address.altPhone', 'notes_details.note');
    }

    /**Get customer with contact address details */
    public function customer()
    {
        return $this->belongsTo(PocomosCustomer::class, 'customer_id');
    }

    /**Get office details */
    public function office_details()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }

    /**Get phone numbers details */
    public function phone_numbers()
    {
        return $this->hasMany(PocomosCustomersPhone::class, 'profile_id')->with('phone');
    }

    /**Get office user details */
    public function office_user_detail()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'office_user_id');
    }

    /**Get account details */
    public function account_details()
    {
        return $this->hasMany(PocomosCustomersAccount::class, 'profile_id')->with('account_detail');
    }

    /**
     */
    public function getBankAccounts()
    {
        return $this->account_details->filter(function ($account) {
            if (isset($account->account_detail) && $account->account_detail->type == 'BankAccount') {
                return $account;
            }
        });
    }

    /**
     */
    public function getCardAccounts()
    {
        return $this->account_details->filter(function ($account) {
            if (isset($account->account_detail) && $account->account_detail->type == 'CardAccount') {
                return $account;
            }
        });
    }

    public function notify_mobile_phones()
    {
        return $this->hasMany(PocomosCustomersNotifyMobilePhone::class, 'profile_id');
    }
}
