<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosEmergencyNews extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'note',
        'expire_at',
        'created_by_user_id',
        'expire_at_minute',
        'active',
        'date_modified',
        'date_created',
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

    public function office_user()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'created_by_user_id');
    }
}
