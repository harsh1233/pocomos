<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosInvoice;

class PocomosBillInvoice extends Model
{
    protected $table = "pocomos_bill_invoices";
    public $timestamps = false;

    protected $fillable = [
        'bill_group_id',
        'invoice_id'
    ];

    public function invoice_detail()
    {
        return $this->belongsTo(PocomosInvoice::class, 'invoice_id')->select('id', 'date_due', 'status', 'balance');
    }
}
