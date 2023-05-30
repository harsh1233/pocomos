<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Orkestra\OrkestraCountryRegion;

class PocomosZipCode extends Model
{
    public $timestamps = false;

    protected $table = 'pocomos_zip_code';

    protected $primaryKey = 'id';

    protected $fillable = [
        'zip_code',
        'city',
        'state_id',
        'office_id',
        'tax_code_id',
        'active',
        'deleted',
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

    /**Get office details */
    public function office_details()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id')->select('id', 'name');
        ;
    }

    /**Get office details */
    public function tax_code_details()
    {
        return $this->belongsTo(PocomosTaxCode::class, 'tax_code_id');
    }

    /**Get office details */
    public function region_details()
    {
        return $this->belongsTo(OrkestraCountryRegion::class, 'state_id');
    }
}
