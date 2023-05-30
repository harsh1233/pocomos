<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Pocomos\PocomosAddress;

class PocomosDistributor extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'contact_address_id',
        'office_id',
        'name',
        'contact_name',
        'active',
        'date_modified',
        'date_created',
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

    public function pocomospestproducts()
    {
        return $this->hasMany(PocomosPestProduct::class, 'distributor_id');
    }

    public function coontact_address()
    {
        return $this->belongsTo(PocomosAddress::class, 'contact_address_id')->with('primaryPhone', 'altPhone', 'region');
    }
}
