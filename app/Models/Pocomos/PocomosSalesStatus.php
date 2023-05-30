<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosSalesStatus extends Model
{
    protected $table = 'pocomos_sales_status';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'name',
        'description',
        'position',
        'paid',
        'default_status',
        'auto_update',
        'default_initial_job_completed',
        'default_initial_job_rescheduled',
        'default_initial_job_cancelled',
        'active',
        'date_modified',
        'date_created',
        'serviced',
        'display_banner',
        'apay',
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
