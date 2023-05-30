<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocomosCustomerState extends Model
{
    use HasFactory;

    protected $table = 'pocomos_customer_state';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'last_service_date',
        'last_regular_service_date',
        'next_service_date',
        'active',
        'balance_overall',
        'balance_outstanding',
        'balance_credit',
        'days_past_due',
        'card_on_file'
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
