<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosAlert;

class PocomosCustomerAlert extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'alert_id',
        'assigned_by_id',
        'assigned_to_id',
        'active',
        'date_modified',
        'date_created',
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
    public function alert_details()
    {
        return $this->belongsTo(PocomosAlert::class, 'alert_id')->where('type', 'Alert')->where('status', '!=', 'Completed');
    }
    public function completed_alert_details()
    {
        return $this->belongsTo(PocomosAlert::class, 'alert_id')->where('type', 'Alert')->where('status', '=', 'Completed');
    }
    public function todo_details()
    {
        return $this->belongsTo(PocomosAlert::class, 'alert_id')->where('type', 'ToDo')->where('status', '!=', 'Completed');
    }
    public function completed_todo_details()
    {
        return $this->belongsTo(PocomosAlert::class, 'alert_id')->where('type', 'ToDo')->where('status', '=', 'Completed');
    }
    public function assigned_by_details()
    {
        return $this->belongsTo(PocomosCompanyOfficeUser::class, 'assigned_by_id')->with('user_details');
    }
    public function assigned_to_details()
    {
        return $this->belongsTo(PocomosCustomerSalesProfile::class, 'assigned_to_id')->with('customer');
    }
    public function alert_history_details()
    {
        return $this->belongsTo(PocomosAlert::class, 'alert_id')->where('type', 'Alert')->where('status', '=', 'Completed');
    }
}
