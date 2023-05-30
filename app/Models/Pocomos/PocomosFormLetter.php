<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class PocomosFormLetter extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'title',
        'require_job',
        'subject',
        'description',
        'body',
        'active',
        'date_modified',
        'date_created',
        'confirm_job',
        'category',
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
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }
}
