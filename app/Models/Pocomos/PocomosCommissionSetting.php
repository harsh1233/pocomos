<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosCommissionDeduction;

class PocomosCommissionSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'salesperson_id',
        'last_day_summer',
        'goal',
        'commission_percentage',
        'active',
        'date_modified',
        'date_created',
        'daily_goal',
        'weekly_goal',
        'monthly_goal'
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

    public function bonuse_details()
    {
        return $this->hasMany(PocomosCommissionBonuse::class, 'commission_settings_id');
    }

    public function deduction_details()
    {
        return $this->hasMany(PocomosCommissionDeduction::class, 'commission_settings_id');
    }
}
