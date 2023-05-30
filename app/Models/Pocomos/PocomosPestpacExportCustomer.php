<?php

namespace App\Models\Pocomos;

use Exception;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosPestpacSetting;
use Illuminate\Database\Eloquent\SoftDeletes;

class PocomosPestpacExportCustomer extends Model
{
    use Functions;

    protected $table = 'pocomos_pestpac_export_customers';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'office_id',
        'pest_contract_id',
        'location_id',
        'bill_to_id',
        'contract_file_id',
        'service_setup_id',
        'service_order_id',
        'contract_file_uploaded',
        'active',
        'status',
        'errors',
        'server_errors',
        'date_modified',
        'date_created',
        'card_token',
        'card_brand',
        'card_reference',
        'card_id',
        'vantive_request',
        'vantive_response',
        'vantive_status_code',
        'notes_added',
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

    public function pest_contract()
    {
        return $this->belongsTo(PocomosPestContract::class, 'pest_contract_id');
    }

    public function getPestpacSettings()
    {
        return $this->hasOne(PocomosPestpacSetting::class, 'office_id');
    }

    public function customerDetail()
    {
        return $this->belongsTo(PocomosCustomer::class, 'customer_id');
    }

    public function LocationCreation($pestpacExportCustomer)
    {
        $existLocation = PocomosPestpacExportCustomer::where('customer_id', $pestpacExportCustomer->customer_id)->whereNotNull('location_id')->first();

        if ($existLocation) {
            $pestpacExportCustomer->location_id = $existLocation->location_id;
            $pestpacExportCustomer->bill_to_id = $existLocation->bill_to_id;
            $pestpacExportCustomer->save();
            return;
        }

        $validateAndGeocode = 'false';
        if ($this->getPestpacSettings && $this->getPestpacSettings->validate_and_geocode) {
            $validateAndGeocode = 'true';
        }
        $uri = 'locations?validateAndGeocode=' . $validateAndGeocode;
        $method = 'POST';

        // try {
        //     $customerData = $this->getCustomerData($pestpacExportCustomer);

        //     $result = $this->attemptRequest($uri, $method, /*Headers*/array(), $customerData);
        //     if ($this->handleError($result, $pestpacExportCustomer)) {
        //         $pestpacExportCustomer->location_id = $result['message']->location_id ?? null;
        //         $pestpacExportCustomer->bill_to_id = $result['message']->bill_to_id ?? null;
        //     }
        // } catch (\Exception $e) {
        //     $this->handleError(
        //     /* result */
        //         array('error' => true, 'errorMessage' => $e->getMessage(), 'URI' => $uri),
        //         $pestpacExportCustomer
        //     );
        // }
    }
}
