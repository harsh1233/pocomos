<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosMassNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'assigned_by_user_id',
        'offices',
        'roles',
        'alert_body',
        'alert_priority',
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

    public function getOfficesAttribute($value)
    {
        return unserialize($value);
    }

    public function getRolesAttribute($value)
    {
        return unserialize($value);
    }

    public function office_user()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'assigned_by_user_id');
    }
}
