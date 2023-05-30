<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosCustomerSalesProfile;

class PocomosBill extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'profile_id',
        'name',
        'status',
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

    /**Get profile details */
    public function profile_details()
    {
        return $this->belongsTo(PocomosCustomerSalesProfile::class, 'profile_id');
    }
}
