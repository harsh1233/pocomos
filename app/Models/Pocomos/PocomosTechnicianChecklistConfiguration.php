<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosTechnicianChecklistConfiguration extends Model
{
    protected $table = 'pocomos_technician_checklist_configuration';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'active',
        'enable_checklist',
        'enable_required',
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
}
