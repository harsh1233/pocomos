<?php

namespace App\Models\Orkestra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Pocomos\PocomosCustomersFile;
use App\Models\Orkestra\OrkestraUser;

class OrkestraFile extends Model
{
    public $timestamps = false;

    public $appends = ['full_path'];

    protected $fillable = [
        'user_id',
        'path',
        'filename',
        'mime_type',
        'file_size',
        'active',
        'date_modified',
        'date_created',
        'md5_hash',
        'show_to_customer',
        'file_description',
        'job_id',
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

    /* relations */

    public function pocomosuserprofilesphotos()
    {
        return $this->hasMany(PocomosCompanyOfficeUserProfile::class, 'photo_id');
    }

        public function customer_file_details()
        {
            return $this->hasMany(PocomosCustomersFile::class, 'file_id');
        }

    public function user_details_name()
    {
        return $this->belongsTo(OrkestraUser::class, 'user_id')->select('id', 'first_name', 'last_name');
    }

    public function getFullPathAttribute()
    {
        $url = $this->path;
        //CHECK IF PATH IS S3 URL THEN KEEP GO OTHER WISE GENERATE STORAGE URL
        if (filter_var($this->path, FILTER_VALIDATE_URL) === false) {
            $url = env('ASSET_URL').  $this->path;
        }
        return $url;
    }
}
