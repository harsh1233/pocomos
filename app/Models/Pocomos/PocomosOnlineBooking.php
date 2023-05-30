<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosOnlineBookingAgreements;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\PocomosTechnician;

class PocomosOnlineBooking extends Model
{
    public $timestamps = false;

    protected $table = 'pocomos_online_booking';

    protected $primaryKey = 'id';

    protected $appends = ['agreement'];

    protected $fillable = [
        'active',
        'date_modified',
        'date_created',
        'office_id',
        'name',
        'marketing_type_id',
        'referring_url',
        'salesperson_id',
        'technician_id',
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

    /**Get office details */
    public function office_details()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id')->select('id', 'name');
        ;
    }

    /**Get office details */
    public function marketing_type_details()
    {
        return $this->belongsTo(PocomosMarketingType::class, 'marketing_type_id');
    }

    /**Get office details */
    public function salesperson_details()
    {
        return $this->belongsTo(PocomosSalesPeople::class, 'salesperson_id');
    }

    /**Get office details */
    public function technician_details()
    {
        return $this->belongsTo(PocomosTechnician::class, 'technician_id');
    }

    public function getagreementAttribute()
    {
        $agreements_data = array();

        $PocomosOnlineBookingAgreements = PocomosOnlineBookingAgreements::where('booking_id', $this->id)->get();

        if ($PocomosOnlineBookingAgreements) {
            foreach ($PocomosOnlineBookingAgreements as $nhw) {
                $agreements_data[] = $nhw->agreement_id;
            }
        }

        return $agreements_data;
    }
}
