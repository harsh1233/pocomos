<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocomosCustomersPhone extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'profile_id',
        'phone_id'
    ];

    /**Get phone details */
    public function phone()
    {
        return $this->belongsTo(PocomosPhoneNumber::class, 'phone_id');
    }
}
