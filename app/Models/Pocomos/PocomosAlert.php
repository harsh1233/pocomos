<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosOfficeAlert;

class PocomosAlert extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'status',
        'priority',
        'type',
        'date_due',
        'active',
        'date_modified',
        'date_created',
        'notified',

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
    // public function alert_details(){
    //     return $this->hasOne(PocomosOfficeAlert::class,'alert_id')->with('assigned_by_details','assigned_to_details');
    // }
}
