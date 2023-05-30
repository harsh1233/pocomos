<?php

namespace App\Models\Pocomos;

use Exception;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosMembership;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class PocomosTeam extends Model
{
    protected $table = 'pocomos_teams';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'office_id',
        'name',
        'color',
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

    public function member_details()
    {
        return $this->hasMany(PocomosMembership::class, 'team_id')->with('ork_user_details');
    }

    public function office_detail()
    {
        return $this->belongsTo(PocomosCompanyOffice::class, 'office_id');
    }
}
