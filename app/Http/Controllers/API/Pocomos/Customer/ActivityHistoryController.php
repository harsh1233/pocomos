<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosNote;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosCustomersNote;
use App\Models\Pocomos\PocomosCustomerState;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosInvoice;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosTaxCode;
use Carbon\Carbon;
use App\Models\Pocomos\PocomosPestContractsPest;
use App\Models\Pocomos\PocomosCustomersFile;
use App\Models\Pocomos\PocomosActivityLog;
use App\Models\Pocomos\PocomosLead;

class ActivityHistoryController extends Controller
{
    use Functions;

    /**
     * Lists all Activity_history related to this customers account
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function indexAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find Customer entity.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $results = PocomosActivityLog::where('customer_sales_profile_id', $profile->id)->orderBy('date_created', 'DESC')
                    ->with('office_user_detail.user_details_name');

        if ($request->search) {
            $search = $request->search;

            $tempTechIds = DB::select(DB::raw("SELECT pt.office_user_id
            FROM pocomos_activity_logs AS pt
            JOIN pocomos_company_office_users AS cou ON pt.office_user_id = cou.id
            JOIN orkestra_users AS ou ON cou.user_id = ou.id
            WHERE (ou.first_name LIKE '%$search%' OR ou.last_name LIKE '%$search%')"));

            $techIds = array_map(function ($value) {
                return $value->office_user_id;
            }, $tempTechIds);

            $results = $results->where(function ($q) use ($search, $techIds) {
                $q->where('date_created', 'like', '%' . $search . '%');
                $q->orWhere('type', 'like', '%' . $search . '%');
                $q->orWhere('description', 'like', '%' . $search . '%');
                $q->orWhereIn('office_user_id', $techIds);
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $results->count();
        $results->skip($perPage * ($page - 1))->take($perPage);

        $results = $results->get();

        return $this->sendResponse(true, 'List', [
            'Activity_History' => $results,
            'count' => $count,
        ]);
    }


    /**
     * Lists all Activity_history related to this lead account
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function leadindexAction(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosLead::findOrFail($request->lead_id);

        if (!$PocomosLead) {
            return $this->sendResponse(false, 'Unable to locate Lead');
        }

        $results = PocomosActivityLog::orderBy('date_created', 'DESC')->with('office_user_detail.user_details_name');

        if ($request->search) {
            $search = $request->search;

            $tempTechIds = DB::select(DB::raw("SELECT pt.office_user_id
            FROM pocomos_activity_logs AS pt
            JOIN pocomos_company_office_users AS cou ON pt.office_user_id = cou.id
            JOIN orkestra_users AS ou ON cou.user_id = ou.id
            WHERE (ou.first_name LIKE '%$search%' OR ou.last_name LIKE '%$search%')"));

            $techIds = array_map(function ($value) {
                return $value->office_user_id;
            }, $tempTechIds);

            $results = $results->where(function ($q) use ($search, $techIds) {
                $q->where('date_created', 'like', '%' . $search . '%');
                $q->orWhere('type', 'like', '%' . $search . '%');
                $q->orWhere('description', 'like', '%' . $search . '%');
                $q->orWhereIn('office_user_id', $techIds);
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $results->count();
        $results->skip($perPage * ($page - 1))->take($perPage);

        $results = $results->get();
        $results = array();//AS PER THE SYMFONY LOGIC DECLARED BLANK ARRAY
        return $this->sendResponse(true, 'List', [
            'Activity_History' => $results,
            'count' => $count,
        ]);
    }
}
