<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosFormVariable extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'description',
        'long_description',
        'variable_name',
        'require_job',
        'type',
        'enabled',
        'active',
    ];

    public $appends = ['type_data'];

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

    public function getTypeDataAttribute()
    {
        $data = array();
        if(@unserialize($this->type)){
            $data = unserialize($this->type);
        }
        return $data;
    }
}
