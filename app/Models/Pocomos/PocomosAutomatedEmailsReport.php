<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Orkestra\OrkestraUser;

class PocomosAutomatedEmailsReport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'report_selected',
        'branch_selected',
        'date_range',
        'frequency',
        'sent_day',
        'week_of_month',
        'time_begin',
        'email_subject',
        'email_body',
        'email_address',
        'next_scheduled_date_time',
        'last_scheduled_date_time',
        'user_id',
        'active',
        'deleted',
        'date_modified',
        'date_created',
        'office_id',
        'secondary_emails',
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

    public function user_details_name()
    {
        return $this->belongsTo(OrkestraUser::class, 'user_id');
    }
}
