<?php

namespace App\Models\Pocomos;

use Exception;
use App\Models\Orkestra\OrkestraFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PocomosSalespersonProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'profile_pic_id',
        'office_user_profile_id',
        'experience',
        'pay_level',
        'tagline',
        'active',
        'date_modified',
        'date_created',
        'certification_level_id'
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

    public function certificateLevel()
    {
        return $this->hasOne(PocomosVtpCertificationLevel::class, 'id', 'certification_level_id');
    }

    public function profile_pic()
    {
        return $this->belongsTo(OrkestraFile::class, 'profile_pic_id');
    }
}
