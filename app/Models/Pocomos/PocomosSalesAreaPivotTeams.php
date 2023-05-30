<?php

namespace App\Models\Pocomos;

use App\Models\Pocomos\PocomosTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PocomosSalesAreaPivotTeams extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'team_id',
        'sales_area_id'
    ];

    /**Get team details */
    public function team()
    {
        return $this->belongsTo(PocomosTeam::class, 'team_id');
    }
}
