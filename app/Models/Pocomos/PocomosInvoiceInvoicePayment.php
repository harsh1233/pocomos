<?php

namespace App\Models\Pocomos;

use Exception;
use App\Models\Pocomos\PocomosInvoice;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosInvoicePayment;
use Illuminate\Database\Eloquent\SoftDeletes;

class PocomosInvoiceInvoicePayment extends Model
{
    protected $table = 'pocomos_invoices_invoice_payments';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'payment_id',
    ];

    public function payment()
    {
        return $this->belongsTo(PocomosInvoicePayment::class, 'payment_id');
    }

    public function invoice()
    {
        return $this->belongsTo(PocomosInvoice::class, 'invoice_id');
    }
}
