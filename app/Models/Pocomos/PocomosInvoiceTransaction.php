<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use App\Models\Orkestra\OrkestraTransaction;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosUserTransaction;

class PocomosInvoiceTransaction extends Model
{
    protected $table = 'pocomos_invoice_transactions';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'transaction_id',
    ];

    public function transactions()
    {
        return $this->belongsTo(OrkestraTransaction::class, 'transaction_id');
    }

    public function user_transactions()
    {
        return $this->hasOne(PocomosUserTransaction::class, 'transaction_id', 'transaction_id');
    }
}
