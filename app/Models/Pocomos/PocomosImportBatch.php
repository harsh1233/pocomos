<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Pocomos\PocomosImportCustomer;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosImportBatch extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'import_type',
        'service_frequency',
        'office_id',
        'service_type_id',
        'salesperson_id',
        'technician_id',
        'contract_type',
        'marketing_type',
        'tax_code',
        'csv_file',
        'active',
        'service_schedule',
        'imported',
        'pest_agreement_id'
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

    public function import_details()
    {
        return $this->hasMany(PocomosImportCustomer::class, 'upload_batch_id');
    }

    public function office_detail()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }

    public function pest_agreement_detail()
    {
        return $this->belongsTo(PocomosPestAgreement::class, 'pest_agreement_id')->with('agreement_detail');
    }
}
