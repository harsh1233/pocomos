<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PocomosSubCustomer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'child_id',
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

    /**Get child detail */
    public function child_detail()
    {
        return $this->belongsTo(PocomosCustomer::class, 'child_id')->with('contact_address.primaryPhone', 'contact_address.altPhone', 'notes_details.note');
    }

    /**Get parent detail */
    public function parent_detail()
    {
        return $this->belongsTo(PocomosCustomer::class, 'parent_id')->with('contact_address.primaryPhone', 'contact_address.altPhone', 'notes_details.note');
    }

    public function getParent()
    {
        if (!$this->parent_id) {
            return null;
        }
        $parent = PocomosCustomer::find($this->parent_id);
        return ($parent->first_name??"").' '.($parent->last_name??"");
    }

    public function getParentNew()
    {
        if (!$this->parent_id) {
            return null;
        }
        $parentCust = PocomosCustomer::find($this->parent_id);
        return $parentCust;
    }
}
