<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosTermiteInspection extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'lead_id',
        'tech_id',
        'reason',
        'concern',
        'scheduled_at',
        'completed_at',
        'address_id',
        'office_id',
        'created_at',
        'note_id',
        'active',
        'state',
        'pest_control_contract_id',
        'termite_inspection_form_id',
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

    public function customer()
    {
        return $this->belongsTo(PocomosCustomer::class, 'customer_id');
    }
}
