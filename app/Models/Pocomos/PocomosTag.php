<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosTag extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'name',
        'description',
        'active',
        'date_created',
        'customer_visible',
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

    public function tag_details()
    {
        return $this->hasMany(PocomosLeadQuoteTag::class, 'tag_id');
    }
}
