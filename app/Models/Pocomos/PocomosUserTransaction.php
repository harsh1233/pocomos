<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Orkestra\OrkestraTransaction;
use App\Models\Orkestra\OrkestraUser;

class PocomosUserTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $hidden = ['date_modified', 'date_created'];

    protected $fillable = [
        'invoice_id',
        'transaction_id',
        'user_id',
        'past_due',
        'active',
        'memo',
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

    public function transactions()
    {
        return $this->belongsTo(OrkestraTransaction::class, 'transaction_id')->where('status', 'Approved')->where('type', '!=', 'Refund');
    }

    public function transaction()
    {
        return $this->belongsTo(OrkestraTransaction::class, 'transaction_id');
    }

    public function user_details_name()
    {
        return $this->belongsTo(OrkestraUser::class, 'user_id')->select('id', 'first_name', 'last_name');
    }

    public function invoice()
    {
        return $this->belongsTo(PocomosInvoice::class, 'invoice_id');
    }
}
