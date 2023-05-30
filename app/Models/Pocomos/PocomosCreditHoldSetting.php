<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocomosCreditHoldSetting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'due_days',
        'on_hold',
        'reactivate',
        'office_id',
        'active'
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
}
