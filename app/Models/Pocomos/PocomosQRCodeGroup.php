<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosQRCodeGroup extends Model
{
    protected $table = 'pocomos_qr_code_groups';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'profile_id',
        'name',
        'description',
        'active',
        'date_modified',
        'date_created',
        'autoinc_identifier'
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

    // public function scan_history(){
    //     return $this->belongsTo('');
    // }
}
