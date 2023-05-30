<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class PocomosTaxCode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'code',
        'description',
        'tax_rate',
        'active',
        'date_modified',
        'date_created',
        'default_taxcode',
        'enabled'
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

    public function office()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }
}
