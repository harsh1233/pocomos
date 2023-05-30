<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocomosRecruitAgreementTerm extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'agreement_id',
        'term_id'
    ];
}
