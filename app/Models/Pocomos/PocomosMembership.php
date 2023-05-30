<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\PocomosSalesPeople;

class PocomosMembership extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'salesperson_id',
        'team_id',
        'active',
        'salesperson_profile_id',
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

    public function ork_user_details()
    {
        return $this->belongsTo(PocomosSalesPeople::class, 'salesperson_id')->with('office_user_details.user_details');
    }
}
