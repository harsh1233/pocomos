<?php

namespace App\Models\Orkestra;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Orkestra\OrkestraAccount;

class OrkestraTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'account_id',
        'credentials_id',
        'amount',
        'type',
        'network',
        'status',
        'active',
        'description',
        'referenceNumber',
        'date_modified',
        'date_created'
    ];

    public $appends = ['refund_status'];

    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $record->date_created = date("Y-m-d");
        });

        // create a event to happen on updating
        static::updating(function ($record) {
            $record->date_modified = date("Y-m-d H:i:s");
        });
    }

    public function account_detail()
    {
        return $this->belongsTo(OrkestraAccount::class, 'account_id');
    }

    /**Get voucher applied amount */
    public function getRefundStatusAttribute()
    {
        $refund_status = 0;
        $profile = OrkestraTransaction::where('parent_id', $this->id)->where('type', 'Refund')->where('status', 'Approved')->first();
        if ($profile) {
            $refund_status = 1;
        }
        return $refund_status;
    }
}
