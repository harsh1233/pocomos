<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosPestContractsSpecialtyPest extends Model
{
    protected $table = 'pocomos_pest_contracts_specialty_pests';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'pest_id',
    ];

    /**Get pest details */
    public function pest()
    {
        return $this->belongsTo(PocomosPest::class, 'pest_id');
    }
}
