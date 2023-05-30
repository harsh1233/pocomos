<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosMembership;

class PocomosTeamRouteAssignment extends Model
{
    protected $table = 'pocomos_teams_route_assignments';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'route_id',
        'team_id' ,
        'time_begin',
        'duration',
        'active',
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

    public function team()
    {
        return $this->belongsTo(PocomosTeam::class, 'team_id');
    }
}
