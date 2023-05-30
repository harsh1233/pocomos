<?php

namespace App\Models\Pocomos\Recruitement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosCustomFieldConfiguration extends Model
{
    protected $table = 'pocomos_recruit_custom_field_configurations';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_configuration_id',
        'name',
        'label',
        'required',
        'description',
        'type',
        'options',
        'active',
        'legally_binding',
        'date_modified',
        'date_created',
    ];

    public $appends = ['options_data'];

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

    /**Get options decoded data */
    public function getOptionsDataAttribute()
    {
        $data = array();
        if(@unserialize($this->options)){
            $data = unserialize($this->options);
        }
        return $data;
    }
}
