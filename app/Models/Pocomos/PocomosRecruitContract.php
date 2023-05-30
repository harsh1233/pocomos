<?php

namespace App\Models\Pocomos;

use App\Models\Orkestra\OrkestraFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Pocomos\Recruitement\PocomosRecruitAgreement;

class PocomosRecruitContract extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $hidden = ['date_modified', 'date_created'];

    protected $fillable = [
        'agreement_id',
        'signature_id',
        'initials_id',
        'recruiter_signature_id',
        'pay_level',
        'addendum',
        'date_start',
        'date_end',
        'status',
        'active'
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

    public function agreement()
    {
        return $this->belongsTo(PocomosRecruitAgreement::class, 'agreement_id');
    }

    public function custome_fields()
    {
        return $this->hasMany(PocomosRecruitCustomFields::class, 'recruit_contract_id')->with('custom_field');
    }

    public function recruiter_signature()
    {
        return $this->belongsTo(OrkestraFile::class, 'recruiter_signature_id');
    }

    public function signature()
    {
        return $this->belongsTo(OrkestraFile::class, 'signature_id');
    }

    public function recruit_initial()
    {
        return $this->belongsTo(OrkestraFile::class, 'initials_id');
    }
}
