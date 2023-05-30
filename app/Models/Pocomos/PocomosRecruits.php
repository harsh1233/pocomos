<?php

namespace App\Models\Pocomos;

use App\Models\Orkestra\OrkestraFile;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\PocomosAddress;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosRecruiter;
use App\Models\Pocomos\PocomosRecruitNote;
use App\Models\Pocomos\PocomosRecruitsFile;
use App\Models\Pocomos\PocomosRecruitContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Pocomos\Recruitement\PocomosRecruitOffice;
use App\Models\Pocomos\Recruitement\PocomosRecruitStatus;

class PocomosRecruits extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $hidden = ['date_modified', 'date_created'];

    protected $fillable = [
        'recruit_status_id',
        'recruit_contract_id',
        'recruiter_id',
        'recruiting_office_id',
        'current_address_id',
        'primary_address_id',
        'legal_name',
        'first_name',
        'last_name',
        'date_of_birth',
        'email',
        'active',
        'date_modified',
        'date_created',
        'user_id',
        'remote_user_id',
        'linked',
        'profile_pic_id',
        'recruiting_region_id',
        'desired_username',
        'desired_password',
        'legal_first_name',
        'legal_last_name'
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

    public function status_detail()
    {
        return $this->belongsTo(PocomosRecruitStatus::class, 'recruit_status_id');
    }

    public function contract_detail()
    {
        return $this->belongsTo(PocomosRecruitContract::class, 'recruit_contract_id');
    }

    public function recruiter_detail()
    {
        return $this->belongsTo(PocomosRecruiter::class, 'recruiter_id');
    }

    public function office_detail()
    {
        return $this->belongsTo(PocomosRecruitOffice::class, 'recruiting_office_id');
    }

    public function current_address()
    {
        return $this->belongsTo(PocomosAddress::class, 'current_address_id');
    }

    public function primary_address()
    {
        return $this->belongsTo(PocomosAddress::class, 'primary_address_id');
    }

    public function profile_details()
    {
        return $this->belongsTo(OrkestraFile::class, 'profile_pic_id');
    }

    public function attachment_details()
    {
        return $this->hasMany(PocomosRecruitsFile::class, 'recruit_id');
    }

    public function note_details()
    {
        return $this->hasMany(PocomosRecruitNote::class, 'recruit_id');
    }

    public function user_detail()
    {
        return $this->belongsTo(OrkestraUser::class, 'user_id');
    }
}
