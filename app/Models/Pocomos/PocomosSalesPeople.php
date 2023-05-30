<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosCompanyOfficeUser;

class PocomosSalesPeople extends Model
{
    public $timestamps = false;

    protected $table = 'pocomos_salespeople';

    protected $fillable = [
        'user_id',
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

    public function office_user_details()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'user_id');
    }

    public function commission_setting()
    {
        return $this->hasOne(PocomosCommissionSetting::class, 'salesperson_id');
    }
}
