<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosCustomAgreementToOffice extends Model
{
    protected $table = 'pocomos_custom_agreements_to_offices';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'custom_agreement_id',
        'office_id',
    ];
}
