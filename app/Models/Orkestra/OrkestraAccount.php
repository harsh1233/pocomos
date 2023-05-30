<?php

namespace App\Models\Orkestra;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosCustomerSalesProfile;

class OrkestraAccount extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $appends = ['autopay'];

    protected $fillable = [
        'account_number',
        'ip_address',
        'alias',
        'name',
        'address',
        'city',
        'region',
        'country',
        'postal_code',
        'phoneNumber',
        'active',
        'date_modified',
        'date_created',
        'type',
        'ach_routing_number',
        'account_type',
        'card_exp_month',
        'card_exp_year',
        'card_cvv',
        'balance',
        'track_one',
        'track_two',
        'track_three',
        'email_address',
        'key_serial_number',
        'encryption_format',
        'credentials_id',
        'external_person_id',
        'external_account_id',
        'last_four',
        'account_token',
        'date_tokenized'
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
            $record->date_modified = date("Y-m-d");
        });
    }

    /**Get voucher applied amount */
    public function getautopayAttribute()
    {
        $autopay = 0;
        $profile = PocomosCustomerSalesProfile::where('autopay_account_id', $this->id)->first();
        if ($profile) {
            $autopay = 1;
        }
        return $autopay;
    }
}
