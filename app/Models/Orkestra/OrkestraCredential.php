<?php

namespace App\Models\Orkestra;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrkestraCredential extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $appends = ['credentials_array'];

    protected $fillable = [
        'credentials',
        'transactor',
        'active',
        'date_created',
        'date_modified'
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

    /**Get voucher applied amount */
    public function getCredentialsArrayAttribute()
    {
        if (@unserialize($this->credentials) == false) {
            return array();
        }
        return unserialize($this->credentials);
    }
}
