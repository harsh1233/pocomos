<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosTechnician;

class PocomosRouteTemplate extends Model
{
    protected $table = 'pocomos_route_templates';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public $appends = ['frequency_days_data', 'template_data'];

    protected $fillable = [
        'name',
        'office_id',
        'technician_id',
        'frequency_days',
        'template',
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

    /**Get arrays base data */
    public function getFrequencyDaysDataAttribute()
    {
        $res = array();
        if ($this->frequency_days) {
            $res = unserialize($this->frequency_days);
        }
        return $res;
    }

    /**Get technician data */
    public function technician_detail()
    {
        return $this->belongsTo(PocomosTechnician::class, 'technician_id');
    }

    /**Get arrays base data */
    public function getTemplateDataAttribute()
    {
        $res = array();
        if ($this->template) {
            $res = json_decode($this->template, true);
        }
        return $res;
    }
}
