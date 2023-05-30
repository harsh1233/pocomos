<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosLeadQuoteTag extends Model
{
    protected $table = 'pocomos_lead_quotes_tags';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'lead_quote_id',
        'tag_id',
    ];

    public function tag_detail()
    {
        return $this->belongsTo(PocomosTag::class, 'tag_id');
    }

    public function lead_quote_detail()
    {
        return $this->belongsTo(PocomosLeadQuote::class, 'lead_quote_id');
    }
}
