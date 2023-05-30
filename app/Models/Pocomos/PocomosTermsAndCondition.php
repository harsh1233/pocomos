<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Orkestra\OrkestraFile;

class PocomosTermsAndCondition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'file_id',
        'note',
        'enabled',
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

    public function ork_file()
    {
        return $this->belongsTo(OrkestraFile::class, 'file_id');
    }
}
