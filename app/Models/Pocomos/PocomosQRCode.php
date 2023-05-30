<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosQRCode extends Model
{
    protected $table = 'pocomos_qr_codes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $appends = ['google_qr_link'];

    protected $fillable = [
        'batch_id',
        'group_id',
        'data',
        'image_data',
        'active',
        'identifier'
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
    public function getGoogleQrLinkAttribute()
    {
        $link = 'https://sandbox.pocomos.com/qr-code/scan/'.$this->data;
        return "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . $link . "&choe=UTF-8";
        // dd($this->data);
    }
    public function scanDetails()
    {
        return $this->hasOne(PocomosQRCodeScanSessionScan::class, 'code_id')->orderBy('date_created', 'DESC');
    }
}
