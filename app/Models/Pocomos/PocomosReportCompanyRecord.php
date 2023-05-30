<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosSalesPeople;

class PocomosReportCompanyRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'active',
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

    public function first_sales_person()
    {
        return $this->belongsTo(PocomosSalesPeople::class, 'first_salesperson_id');
    }

    public function second_sales_person()
    {
        return $this->belongsTo(PocomosSalesPeople::class, 'second_salesperson_id');
    }

    public function third_sales_person()
    {
        return $this->belongsTo(PocomosSalesPeople::class, 'third_salesperson_id');
    }
}
