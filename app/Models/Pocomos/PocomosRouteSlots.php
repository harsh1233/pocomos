<?php

namespace App\Models\Pocomos;

use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosRoute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosRouteSlots extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'route_id',
        'time_begin',
        'duration',
        'type',
        'type_reason',
        'schedule_type',
        'active',
        'anytime',
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

    /**Route detail */
    public function route_detail()
    {
        return $this->belongsTo(PocomosRoute::class, 'route_id');
    }

    /**Job detail */
    public function job_detail()
    {
        return $this->hasOne(PocomosJob::class, 'slot_id');
    }

    /**
     * Returns a DateTime object
     *
     * @return \DateTime
     */
    public function getEndTime()
    {
        $endTime = new \DateTime($this->time_begin);

        $endTime->modify(sprintf('+%s minutes', $this->duration));

        $endTime = $endTime->format('H:i:s');
        return $endTime;
    }

    /**
     * Returns true if the Slot has been confirmed with the Customer
     *
     * @return bool
     */
    public function isConfirmed()
    {
        return config('constants.CONFIRMED') == $this->schedule_type || config('constants.HARD_CONFIRMED') == $this->schedule_type;
    }

    public function isLunch()
    {
        return config('constants.LUNCH') == $this->type;
    }
}
