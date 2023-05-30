<?php

namespace App\Models\Pocomos;

use Exception;
use App\Models\Pocomos\PocomosLead;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\PocomosSalesAreaPivotTeams;
use App\Models\Pocomos\PocomosSalesAreaPivotManager;
use App\Models\Pocomos\PocomosSalesAreaPivotSalesPerson;

class PocomosSalesArea extends Model
{
    public $timestamps = false;

    protected $table = "pocomos_sales_area";

    protected $fillable = [
        'name',
        'color',
        'blocked',
        'area_borders',
        'active',
        'office_id',
        'enabled',
        'created_by',
        'date_created',
        'date_modified',
    ];


    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $record->created_by = auth()->user()->id;
            $record->date_created = date("Y-m-d H:i:s");
        });

        // create a event to happen on updating
        static::updating(function ($record) {
            $record->date_modified = date("Y-m-d H:i:s");
        });
    }

    /**Get lead details */
    public function lead_details()
    {
        return $this->hasMany(PocomosLead::class, 'sales_area_id');
    }

    /**Get customer details */
    public function customer_details()
    {
        return $this->hasMany(PocomosCustomer::class, 'sales_area_id');
    }

    /**Get sales area team details */
    public function teams_details()
    {
        return $this->hasMany(PocomosSalesAreaPivotTeams::class, 'sales_area_id');
    }

    /**Get sales area sales people details */
    public function salespeople_details()
    {
        return $this->hasMany(PocomosSalesAreaPivotSalesPerson::class, 'sales_area_id');
    }

    /**Get sales area managers details */
    public function managers_details()
    {
        return $this->hasMany(PocomosSalesAreaPivotManager::class, 'sales_area_id');
    }

    /**Get created by user detail */
    public function created_by_user()
    {
        return $this->belongsTo(OrkestraUser::class, 'created_by');
    }
}
