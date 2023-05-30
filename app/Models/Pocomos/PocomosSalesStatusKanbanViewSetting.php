<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosSalesStatusKanbanViewSetting extends Model
{
    // protected $table = 'pocomos_sales_status';

    // protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'company_name',
        'first_name',
        'last_name',
        'phone_number',
        'email_address',
        'address',
        'marketing_type',
        'initial_job_status',
        'initial_invoice_status',
        'initial_price',
        'sales_repo',
        'active',
        'office_id',
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
