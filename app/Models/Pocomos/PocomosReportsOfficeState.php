<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use Illuminate\Notifications\Notifiable;
use App\Models\Pocomos\PocomosAddress;

class PocomosReportsOfficeState extends Model
{
    // protected $table = 'pocomos_reports_salesperson_states';

    // protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_id ',
        'users',
        'salespeople',
        'customers',
        'active',
        'date_modified',
        'date_created',
        'type'
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
