<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class PocomosRecruitCustomFieldConfiguration extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $appends = ['options_data'];

    protected $fillable = [
        'office_configuration_id',
        'label',
        'name',
        'description',
        'required',
        'type',
        'options',
        'active',
        'legally_binding',
        'date_modified',
        'date_created'
    ];

    /**Get options decoded data */
    public function getOptionsDataAttribute()
    {
        $res = array();
        if ($this->options) {
            $res = unserialize($this->options);
        }
        return $res;
    }
}
