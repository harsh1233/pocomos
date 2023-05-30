<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;

class PocomosSmsReceivedMessageLog extends Model
{
    protected $table = 'pocomos_sms_received_message_log';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'sender_phone',
        'receiver_phone',
        'message',
        'error',
        'active',
        'date_created',
        'date_modified',
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

    /* relations */

    public function pocomosuserprofilesphones()
    {
        return $this->hasMany(PocomosCompanyOfficeUserProfile::class, 'phone_id');
    }
}
