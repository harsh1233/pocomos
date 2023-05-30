<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosQRCodeScanSession extends Model
{
    protected $table = 'pocomos_qr_code_scan_sessions';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'date',
        'active',
        'office_user_id',
        'date_modified',
        'date_created'
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

    public function scan_details()
    {
        return $this->hasMany(PocomosQRCodeScanSessionScan::class, 'session_id')->groupBy('address_id');
    }
    public function user_details()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'office_user_id')->with('user_details');
    }
}
