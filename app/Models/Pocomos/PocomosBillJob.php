<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosJob;

class PocomosBillJob extends Model
{
    protected $table = "pocomos_bill_jobs";
    public $timestamps = false;

    protected $fillable = [
        'bill_group_id',
        'job_id'
    ];

    public function job_details()
    {
        return $this->belongsTo(PocomosJob::class, 'job_id')->select('id', 'invoice_id', 'date_scheduled', 'status');
    }
}
