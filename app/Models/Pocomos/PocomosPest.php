<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

use Exception;

class PocomosPest extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'name',
        'description',
        'type',
        'position',
        'active',
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


    public function pest_details()
    {
        return $this->hasMany(PocomosLeadQuotPest::class, 'pest_id');
    }

    public function specialty_pest_details()
    {
        return $this->hasMany(PocomosLeadQuoteSpecialtyPest::class, 'pest_id');
    }

    public function company_details()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }
}
