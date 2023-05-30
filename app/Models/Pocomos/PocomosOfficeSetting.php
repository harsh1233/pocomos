<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Orkestra\OrkestraCredential;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosTimezone;

class PocomosOfficeSetting extends Model
{
    protected $table = 'pocomos_office_settings';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public $appends = ['theme_details'];

    protected $fillable = [
        'cash_credentials_id',
        'check_credentials_id',
        'ach_credentials_id',
        'card_credentials_id',
        'points_credentials_id',
        'office_id',
        'theme',
        'enable_points',
        'active',
        'date_modified',
        'date_created',
        'sales_tax',
        'tax_code_required',
        'send_agreement_copy',
        'deliver_email',
        'hide_cc',
        'sender_phone_id',
        'price_per_customer',
        'external_credentials_id',
        'sender_email_id',
        'enable_alert_priority',
        'enable_to_do_priority',
        'timezone_id',
        'vantage_dnc_registry',
        'vantage_dnc_uid',
        'vantage_dnc_username',
        'office_scheduling',
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

    public function phone_details()
    {
        return $this->belongsTo(PocomosPhoneNumber::class, 'phone_id');
    }

    public function sender_phone_details()
    {
        return $this->belongsTo(PocomosPhoneNumber::class, 'sender_phone_id');
    }

    public function cash_cred_details()
    {
        return $this->belongsTo(OrkestraCredential::class, 'cash_credentials_id');
    }

    public function check_cred_details()
    {
        return $this->belongsTo(OrkestraCredential::class, 'check_credentials_id');
    }

    public function ach_cred_details()
    {
        return $this->belongsTo(OrkestraCredential::class, 'ach_credentials_id');
    }

    public function card_cred_details()
    {
        return $this->belongsTo(OrkestraCredential::class, 'card_credentials_id');
    }

    public function points_cred_details()
    {
        return $this->belongsTo(OrkestraCredential::class, 'points_credentials_id');
    }

    public function external_cred_details()
    {
        return $this->belongsTo(OrkestraCredential::class, 'external_credentials_id');
    }

    public function timezone()
    {
        return $this->belongsTo(PocomosTimezone::class, 'timezone_id');
    }

    public function office()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }

    public function getThemeDetailsAttribute()
    {
        $themeDetails = null;
        $theme = $this->theme;
        /**Get themes */
        $themes = config('themes');
        foreach ($themes as $val) {
            if ($val['name'] == $theme) {
                $themeDetails = $val;
            }
        }
        return $themeDetails;
    }
}
