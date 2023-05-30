<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Orkestra\OrkestraUser;

class PocomosReportSummerTotalConfigurationStatus extends Model
{
    protected $table = 'pocomos_report_summer_total_configurations_statuses';

    public $timestamps = false;

    protected $fillable = [
        'configuration_id',
        'sales_status_id',
    ];
}
