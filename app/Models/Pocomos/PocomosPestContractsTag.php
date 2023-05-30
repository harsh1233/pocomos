<?php

namespace App\Models\Pocomos;

use App\Models\Pocomos\PocomosTag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosPestContractsTag extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'tag_id'
    ];

    /**Get tag details */
    public function tag_details()
    {
        return $this->belongsTo(PocomosTag::class, 'tag_id');
    }
}
