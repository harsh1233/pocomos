<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Pocomos\PocomosOnlineBooking;

class PocomosOnlineBookingAgreements extends Model
{
    protected $table = "pocomos_online_booking_agreements";
    public $timestamps = false;

    protected $fillable = [
        'agreement_id',
        'booking_id'
    ];
}
