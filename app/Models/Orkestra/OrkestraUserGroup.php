<?php

namespace App\Models\Orkestra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Orkestra\OrkestraGroup;

class OrkestraUserGroup extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'group_id',
    ];
    public function permission()
    {
        return $this->belongsTo(OrkestraGroup::class, 'group_id');
    }
}
