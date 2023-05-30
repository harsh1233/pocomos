<?php

namespace App\Models\Pocomos;

use App\Models\Pocomos\PocomosTaxCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosInvoiceItems extends Model
{
    use HasFactory;

    protected $table = 'pocomos_invoice_items';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'description',
        'price',
        'active',
        'sales_tax',
        'tax_code_id',
        'type',
        'value_type'
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

    public function tax_code()
    {
        return $this->belongsTo(PocomosTaxCode::class, 'tax_code_id');
    }

    public function invoice()
    {
        return $this->belongsTo(PocomosInvoice::class, 'invoice_id');
    }
}
