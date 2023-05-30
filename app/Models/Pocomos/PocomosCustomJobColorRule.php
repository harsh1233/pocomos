<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosCustomJobColorRule extends Model
{
    // protected $table = 'pocomos_termite_state_forms';

    // protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'agreement_id',
        'service_type_id',
        'office_id',
        'job_type',
        'color',
        'priority',
        'tag_id',
        'active',
        'date_modified',
        'date_created',
    ];

    public function agreement()
    {
        return $this->belongsTo(PocomosAgreement::class, 'agreement_id');
    }

    public function pest_contract_service_type()
    {
        return $this->belongsTo(PocomosPestContractServiceType::class, 'service_type_id');
    }

    public function tag()
    {
        return $this->belongsTo(PocomosTag::class, 'tag_id');
    }

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
}
