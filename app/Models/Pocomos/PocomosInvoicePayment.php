<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosInvoicePayment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $hidden = ['date_modified', 'date_created'];

    protected $fillable = [
        'date_scheduled',
        'amount_in_cents',
        'status',
        'active',
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

    /**
     * @return bool
     */
    public function isPaid()
    {
        return $this->status == config('constants.PAID');
    }
}
