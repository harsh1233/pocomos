<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosSalesPeople;

class PocomosReportContractSnapshot extends Model
{
    public $timestamps = false;

    protected $table = 'pocomos_reports_contract_snapshots';

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

    public function contract()
    {
        return $this->belongsTo(PocomosContract::class, 'contract_id');
    }
}
