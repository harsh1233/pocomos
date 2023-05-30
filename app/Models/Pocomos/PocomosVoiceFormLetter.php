<?php

namespace App\Models\Pocomos;

use App\Models\Orkestra\OrkestraFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosVoiceFormLetter extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'title',
        'message',
        'description',
        'type',
        'message_order',
        'confirm_job',
        'require_job',
        'office_id',
        'file_id',
        'active',
        'date_created',
        'date_modified',
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

    public function file_detail()
    {
        return $this->belongsTo(OrkestraFile::class, 'file_id');
    }
}
