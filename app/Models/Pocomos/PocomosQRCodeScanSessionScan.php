<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosQRCodeScanSessionScan extends Model
{
    protected $table = 'pocomos_qr_code_scan_session_scans';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'code_id',
        'note',
        'time_scanned',
        'active',
        'address_id'
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
    public function session_details()
    {
        return $this->belongsTo(PocomosQRCodeScanSession::class, 'session_id');
    }
}
