<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosNotificationSetting;

class PocomosSalestrackerOfficeSetting extends Model
{
    protected $table = 'pocomos_salestracker_office_settings';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'initial_service_alert_config_id',
        'office_id',
        'active',
        'date_modified',
        'date_created',
        'bulletin',
        'play_sound',
        'vtp_enabled',
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

    public function notification_detail()
    {
        return $this->belongsTo(PocomosNotificationSetting::class, 'initial_service_alert_config_id');
    }
}
