<?php

namespace App\Models\Pocomos;

use Exception;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosJobPest;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosJobProduct;
use App\Models\Pocomos\PocomosRouteSlots;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosPestContract;
use Illuminate\Database\Eloquent\SoftDeletes;

class PocomosJob extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'technician_id',
        'invoice_id',
        'date_scheduled',
        'date_completed',
        'status',
        'type',
        'time_begin',
        'time_end',
        'active',
        'at_fault',
        'date_modified',
        'date_created',
        'time_scheduled',
        'original_date_scheduled',
        'technician_note',
        'signature_id',
        'slot_id',
        'note',
        'weather',
        'color',
        'date_cancelled',
        'commission_type',
        'commission_value',
        'commission_edited',
        'treatmentNote',
        'force_completed',
        'wdo_inspection_id',
    ];

    // public $appends = ['total_amount_due'];

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
    public function invoice_detail()
    {
        return $this->belongsTo(PocomosInvoice::class, 'invoice_id');
    }

    public function invoice_detail_bill()
    {
        return $this->belongsTo(PocomosInvoice::class, 'invoice_id')->select('id', 'balance');
    }

    public function technician_detail()
    {
        return $this->belongsTo(PocomosTechnician::class, 'technician_id');
    }

    public function service_details()
    {
        return $this->hasMany(PocomosJobService::class, 'job_id');
    }
    public function invoice()
    {
        return $this->belongsTo(PocomosInvoice::class, 'invoice_id');
    }

    public function getTotalAmountDueAttribute()
    {
        // dd($this->invoice_items);
        $prices = $this->invoice->invoice_items->map(function ($item) {
//            Not important for removal
            if ($item->type == 'Adjustment') {
                return 0;
            }

            return round($item->price + ($item->price * $item->sales_tax), 2);
        })->toArray();

        return round(array_sum($prices), 2);
    }

    public function contract()
    {
        return $this->belongsTo(PocomosPestContract::class, 'contract_id');
    }

    public function route_detail()
    {
        return $this->belongsTo(PocomosRouteSlots::class, 'slot_id');
    }

    public function jobs_pests()
    {
        return $this->hasMany(PocomosJobPest::class, 'job_id')->has('pest')->with('pest');
    }

    public function get_job_products()
    {
        return $this->hasMany(PocomosJobProduct::class, 'job_id')->with('product', 'invoice_item');
    }

    public function technician()
    {
        return $this->belongsTo(PocomosTechnician::class, 'technician_id');
    }

    public function signature_detail()
    {
        return $this->belongsTo(OrkestraFile::class, 'signature_id');
    }

    public function attachments()
    {
        return $this->hasMany(OrkestraFile::class, 'job_id');
    }

    public function slot()
    {
        return $this->belongsTo(PocomosRouteSlots::class, 'slot_id');
    }

    public function pestContractTags()
    {
        return $this->hasMany(PocomosPestContractsTag::class, 'contract_id', 'contract_id')->with('tag_details');
    }

    public function pestContractSpecialPests()
    {
        return $this->hasMany(PocomosPestContractsSpecialtyPest::class, 'contract_id', 'contract_id')->with('pest');
    }

    public function customFields()
    {
        return $this->hasMany(PocomosCustomField::class, 'pest_control_contract_id', 'contract_id');
    }

    public function isFinished()
    {
        return $this->isComplete() || $this->isCancelled();
    }

    public function isComplete()
    {
        return $this->status && config('constants.COMPLETE') === $this->status;
    }

    public function isCancelled()
    {
        return $this->status && config('constants.CANCELLED') === $this->status;
    }

    public function isInitial()
    {
        return $this->type && config('constants.INITIAL') === $this->type;
    }

    public function job_checklists()
    {
        return $this->hasMany(PocomosJobChecklist::class, 'job_id');
    }

    public function pest_contract()
    {
        return $this->belongsTo(PocomosPestContract::class, 'contract_id');
    }

    public function termite_inspection()
    {
        return $this->belongsTo(PocomosTermiteInspections::class, 'wdo_inspection_id');
    }
}
