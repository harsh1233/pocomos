<?php

namespace App\Models\Pocomos;

use App\Models\Pocomos\PocomosPest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosPestContractsPest extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'pest_id'
    ];

    /**Get pest details */
    public function pest()
    {
        return $this->belongsTo(PocomosPest::class, 'pest_id');
    }
}
