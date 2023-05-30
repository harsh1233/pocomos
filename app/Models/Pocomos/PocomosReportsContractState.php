<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosContract;

class PocomosReportsContractState extends Model
{
    use HasFactory;

    protected $fillable = [
        'salesperson_id',
        'contract_id',
        'value',
        'active',
        'date_modified',
        'date_created',
        'past_due',
        'account_status',
        'manual_value',
        'initial_service_date',
        'original_value',
        'actual_original_value',
        'completed_jobs_last_twelve_months',
        'completed_jobs_last_six_months',
        'completed_jobs_all_time'
    ];

    public function contract()
    {
        return $this->belongsTo(PocomosContract::class, 'contract_id');
    }
}
