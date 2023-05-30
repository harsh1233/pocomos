<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class PocomosSmsUsage extends Model
{
    protected $table = 'pocomos_sms_usage';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'phone_id',
        'message_part',
        'sender_phone_id',
        'office_user_id',
        'inbound',
        'answered',
        'seen',
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

    public function office_user()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'office_user_id');
    }
    public function office()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id')->select('id', 'name', 'contact_name', 'list_name');
    }
    public function office_user_detail()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'office_user_id')->select('id', 'user_id');
    }
}
