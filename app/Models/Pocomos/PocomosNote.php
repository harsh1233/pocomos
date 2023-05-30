<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosLeadNote;
use App\Models\Orkestra\OrkestraUser;

class PocomosNote extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'summary',
        'body',
        'interaction_type',
        'active',
        'favorite',
        'display_on_load',
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

        static::deleting(function ($user) {
            // before delete() method call this
            $user->leadNotes()->delete();
        });
    }

    public function leadNotes()
    {
        return $this->hasOne(PocomosLeadNote::class, 'note_id');
    }

    public function note_details()
    {
        return $this->hasMany(PocomosLeadNote::class, 'note_id');
    }

    public function user_details()
    {
        return $this->belongsTo(OrkestraUser::class, 'user_id');
    }
}
