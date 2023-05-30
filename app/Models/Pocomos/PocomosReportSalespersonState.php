<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use Illuminate\Notifications\Notifiable;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosLeadQuote;
use App\Models\Pocomos\PocomosLeadNotInterestedReason;

class PocomosReportSalespersonState extends Model
{
    protected $table = 'pocomos_reports_salesperson_states';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'salesperson_id',
        'count_active_contracts',
        'average_initial_price',
        'average_contract_value',
        'total_sold',
        'total_active',
        'active',
        'date_modified',
        'date_created',
        'type',
        'services_scheduled_today',
        'services_scheduled_week',
        'services_scheduled_month',
        'services_scheduled_summer',
        'serviced_today',
        'serviced_this_week',
        'serviced_this_month',
        'serviced_this_summer',
        'autopay_account_percentage',
        'value_per_door',
        'total_paid',
        'sales_status_summary',
        'total_serviced',
        'services_scheduled_year',
        'serviced_this_year',
        'services_scheduled_yesterday',
        'serviced_yesterday',
    ];

    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $record->date_created = date("Y-m-d H:i:s");
        });

        // create a event to happen on updating
        static::updating(function ($record) {
            $record->date_modified = date("Y-m-d H:i:s");
        });
    }
}
