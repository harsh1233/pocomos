<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocomosPestContractsInvoice extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'pest_contract_id',
        'invoice_id'
    ];

    public function invoice()
    {
        return $this->belongsTo(PocomosInvoice::class, 'invoice_id');
    }
}
