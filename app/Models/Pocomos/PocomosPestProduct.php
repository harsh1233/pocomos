<?php

namespace App\Models\Pocomos;

use Exception;
use App\Models\Orkestra\OrkestraFile;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosDistributor;
use Illuminate\Database\Eloquent\SoftDeletes;

class PocomosPestProduct extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'distributor_id',
        'office_id',
        'name',
        'description',
        'unit',
        'epa_code',
        'threshold',
        'position',
        'enabled',
        'active',
        'shows_on_invoices',
        'shows_on_estimates',
        'enable_dilution_rate',
        'default_dilution_rate',
        'default_dilution_unit',
        'default_dilution_quantity',
        'default_dilution_liquid_unit',
        'enable_application_rate',
        'default_application_rate',
        'date_modified',
        'date_created',
        'file_id'
    ];

    /* helper functions */

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

    /* relations */
    public function distributor_detail()
    {
        return $this->belongsTo(PocomosDistributor::class, 'distributor_id');
    }

    /* File details */
    public function file_detail()
    {
        return $this->belongsTo(OrkestraFile::class, 'file_id');
    }
}
