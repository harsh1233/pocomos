<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosLeadQuotPest extends Model
{
    protected $table = 'pocomos_lead_quotes_pests';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'lead_quote_id',
        'pest_id',
    ];

    public function pest_detail()
    {
        return $this->belongsTo(PocomosPest::class, 'pest_id');
    }

    public function lead_quote_detail()
    {
        return $this->belongsTo(PocomosLeadQuote::class, 'lead_quote_id');
    }
}
