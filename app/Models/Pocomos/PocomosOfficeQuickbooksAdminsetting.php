<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocomosOfficeQuickbooksAdminsetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'active',
        'date_modified',
        'date_created',
        'office_id',
        'enabled',
        'desktop_version_enabled',
        'online_version_enabled',
        'sync_to_qb_enabled',
        'sync_from_qb_enabled',
        'sync_customers_enabled',
        'sync_invoices_enabled',
        'sync_payments_enabled'
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
