<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosPestEstimateProducts;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosLead;

class PocomosPestEstimates extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'po_number',
        'subtotal',
        'discount',
        'total',
        'status',
        'terms',
        'note',
        'search_for',
        'sent_on',
        'lead_id',
        'customer_id',
        'office_id',
        'active'
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

        static::deleting(function ($user) {
            // before delete() method call this
            $user->pestEstimatesProducts()->delete();
        });
    }
    public function pestEstimatesProducts()
    {
        return $this->hasMany(PocomosPestEstimateProducts::class, 'estimate_id')->with('product_data', 'service_data');
    }

    public function customer()
    {
        return $this->belongsTo(PocomosCustomer::class, 'customer_id');
    }

    public function lead()
    {
        return $this->belongsTo(PocomosLead::class, 'lead_id');
    }
}
