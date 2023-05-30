<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Orkestra\OrkestraAccount;

class PocomosCustomersAccount extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'profile_id',
        'account_id'
    ];

    public function profile_detail()
    {
        return $this->belongsTo(PocomosCustomerSalesProfile::class, 'profile_id');
    }

    public function account_detail()
    {
        return $this->belongsTo(OrkestraAccount::class, 'account_id')->where('active', 1);
    }
}
