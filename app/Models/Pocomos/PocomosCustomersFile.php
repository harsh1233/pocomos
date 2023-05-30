<?php

namespace App\Models\Pocomos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosCustomer;

class PocomosCustomersFile extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'file_id'
    ];

    public function customer_file_detail()
    {
        return $this->belongsTo(OrkestraFile::class, 'file_id');
    }

    public function customer_detail()
    {
        return $this->belongsTo(PocomosCustomer::class, 'customer_id');
    }
}
