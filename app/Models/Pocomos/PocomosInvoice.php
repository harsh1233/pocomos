<?php

namespace App\Models\Pocomos;

use Exception;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosContract;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosInvoiceItems;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosInvoiceTransaction;
use App\Models\Pocomos\PocomosInvoiceInvoicePayment;

class PocomosInvoice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'date_due',
        'amount_due',
        'status',
        'balance',
        'active',
        'sales_tax',
        'tax_code_id',
        'closed',
        'date_modified',
        'date_created'

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

    public function contract()
    {
        return $this->belongsTo(PocomosContract::class, 'contract_id');
    }

    public function invoice_items()
    {
        return $this->hasMany(PocomosInvoiceItems::class, 'invoice_id');
    }


    public function job()
    {
        return $this->belongsTo(PocomosJob::class, 'id', 'invoice_id')->with('technician');
    }

    public function invoice_transactions()
    {
        return $this->hasMany(PocomosInvoiceTransaction::class, 'invoice_id');
    }

    public function paymentsDetails()
    {
        return $this->hasMany(PocomosInvoiceInvoicePayment::class, 'invoice_id')->with('payment');
    }

    /**
     * Returns true if the invoice has any payments
     *
     * @return bool
     */
    public function hasPayments()
    {
        if ($this->status === config('constants.CANCELLED')) {
            return false;
        }

        return abs($this->balance - $this->amount_due) > 0.001;
    }

    /**
     * Returns true if the invoice has been cancelled
     *
     * @return bool
     */
    public function isCancelled()
    {
        return config('constants.CANCELLED') === $this->status;
    }

    public function transactions_details()
    {
        return $this->hasMany(PocomosInvoiceTransaction::class, 'invoice_id')->with('transactions');
    }

    public function tax_code()
    {
        return $this->belongsTo(PocomosTaxCode::class, 'tax_code_id');
    }
}
