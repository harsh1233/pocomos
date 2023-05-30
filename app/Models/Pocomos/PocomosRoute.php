<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosRouteSlots;
use App\Models\Pocomos\PocomosTechnician;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class PocomosRoute extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'technician_id',
        'office_id',
        'date_scheduled',
        'active',
        'date_modified',
        'date_created',
        'name',
        'locked',
        'created_by',
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

    /**Office detail */
    public function office_detail()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }

    /**Technican detail */
    public function technician_detail()
    {
        return $this->belongsTo(PocomosTechnician::class, 'technician_id');
    }

    /**Slots detail */
    public function slots()
    {
        return $this->hasMany(PocomosRouteSlots::class, 'route_id');
    }

    /**Slots detail */
    public function team_assignments()
    {
        return $this->hasMany(PocomosTeamRouteAssignment::class, 'route_id', 'id');
    }

    /**
     * Returns true if the Route contains a slot at the given time
     *
     * @param \DateTime|string $time Either a DateTime or a valid datetime string
     *
     * @return bool
     */
    public function hasSlotAt($time)
    {
        return null !== $this->getSlotAt($time);
    }

    /**
     * Gets the slot at the given time
     *
     * @param \DateTime|string $time Either a DateTime or a datetime string formatted g:iA
     */
    public function getSlotAt($time)
    {
        if (!($time)) {
            $time = new \DateTime($time);
        }

        foreach ($this->slots as $slot) {
            $cmpResult = $this->cmpTime(new \DateTime($time), new \DateTime($slot->time_begin));
            if ($cmpResult >= 0) {
                $cmpResult = $this->cmpTime(new \DateTime($time), new \DateTime($slot->getEndTime()));
                if ($cmpResult < 0) {
                    return $slot;
                }
            }
        }

        return null;
    }

    /**
     * @param \DateTime $a
     * @param \DateTime $b
     * @return int
     */
    private function cmpTime($a, $b)
    {
        // -1 if $a < $b, 0 if ==, 1 if $a > $b
        $aHour = $a->format('H');
        $bHour = $b->format('H');
        if ($aHour === $bHour) {
            $aMin = $a->format('i');
            $bMin = $b->format('i');
            if ($aMin === $bMin) {
                return 0;
            }

            return $aMin > $bMin ? 1 : -1;
        } else {
            return $aHour > $bHour ? 1 : -1;
        }
    }
}
