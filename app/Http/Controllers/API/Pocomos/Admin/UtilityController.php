<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use DB;
use Excel;
use App\Jobs\AutopayJob;
use App\Jobs\OfficeStateJob;
use Illuminate\Http\Request;
use App\Jobs\OfficeSnapshotJob;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosAddress;
use Illuminate\Support\Facades\Session;
use App\Models\Orkestra\OrkestraCountry;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Orkestra\OrkestraCountryRegion;
use Illuminate\Support\Facades\DB as FacadesDB;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosCustomAgreementTemplate;
use App\Models\Pocomos\PocomosCustomAgreementToOffice;
use App\Models\Pocomos\PocomosSalestrackerOfficeSetting;
use App\Models\Pocomos\PocomosRecruitingOfficeConfiguration;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosContract;

class UtilityController extends Controller
{
    use Functions;

    /**
     * API for Load a customer, regardless of the office it is in.
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function customerLookupAction($customer_id)
    {
        $customer = PocomosCustomer::find($customer_id);

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to locate Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to locate Profile Entity.');
        }

        $this->switchOffice($profile->office_id);

        return $this->sendResponse(true, 'Customer Show.', $customer);
    }

    /**
     * API for Load an invoice, regardless of the office it is in.
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function invoiceLookupAction($invoice_id)
    {
        $invoice = PocomosInvoice::find($invoice_id);

        if (!$invoice) {
            return $this->sendResponse(false, 'Unable to locate Invoice.');
        }

        $contract = PocomosContract::find($invoice->contract_id);

        if (!$contract) {
            return $this->sendResponse(false, 'Unable to find the contract.');
        }

        $profile = PocomosCustomerSalesProfile::find($contract->profile_id);

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to locate Profile Entity.');
        }

        $this->switchOffice($profile->office_id);

        return $this->sendResponse(true, 'Invoice Show.', $customer);
    }
}
