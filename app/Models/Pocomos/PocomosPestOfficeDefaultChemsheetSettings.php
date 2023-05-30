<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Pocomos\PocomosPestOfficeDefaultChemsheetsProducts;

class PocomosPestOfficeDefaultChemsheetSettings extends Model
{
    use HasFactory;

    protected $table = 'pocomos_pest_office_default_chemsheet_settings';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_config_id',
        'name',
        'active',
        'master_service',
        'amount',
        'unit',
        'date_modified',
        'date_created'
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

    public function product_detail()
    {
        return $this->hasMany(PocomosPestOfficeDefaultChemsheetsProducts::class, 'configuration_id');
    }
}
