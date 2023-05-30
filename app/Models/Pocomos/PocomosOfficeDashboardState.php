<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosOfficeDashboardState extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'admin_widget_id',
        'yesterday',
        'this_week',
        'last_week',
        'this_month',
        'last_month',
        'last_six_months',
        'this_year',
        'all_time',
        'active',
        'date_created',
        'date_modified',
        'settings',

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

    public function adminWidget()
    {
        return $this->hasOne(PocomosAdminWidget::class, 'id', 'admin_widget_id');
    }
}
