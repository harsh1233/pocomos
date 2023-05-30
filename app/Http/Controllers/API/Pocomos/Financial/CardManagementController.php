<?php

namespace App\Http\Controllers\API\Pocomos\Financial;

use DB;
use PDF;
use Excel;
use Illuminate\Http\Request;
use App\Jobs\BulkCardChargeJob;
use App\Exports\ExportCardDetails;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\PocomosSmsFormLetter;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomerSalesProfile;

class CardManagementController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $marketingTypes = PocomosMarketingType::whereOfficeId($officeId)->whereActive(1)->get();

        $serviceTypes = PocomosService::whereOfficeId($officeId)->whereActive(1)->get();

        $tags = PocomosTag::whereOfficeId($officeId)->whereActive(true)->orderBy('name')->get();

        return $this->sendResponse(true, 'Unpaid Invoices filters', [
            'marketing_types' => $marketingTypes,
            'service_types'   => $serviceTypes,
            'tags'   => $tags,
        ]);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $yearStart = date("Y", strtotime($request->start_date));
        $yearEnd = date("Y", strtotime($request->end_date));

        $monthStart = date("n", strtotime($request->start_date));
        $monthEnd = date("n", strtotime($request->end_date));

        $sql = "SELECT
                        c.id as contract_id,
                        a.id as account_id,
                        cu.id as customer_id,
                        cu.first_name as first_name,
                        cu.last_name as last_name,
                        p.autopay_account_id,
                        j.date_scheduled,
                        p.customer_id,
                        a.last_four,
                        a.card_exp_month,
                        a.card_exp_year,
                        cu.email,
                        phoneNumber,
                        last_service_date,
                        next_service_date,
                        balance_outstanding,
                        ppcst.name as service_type,
                        cba.street,
                        cba.suite,
                        cba.city,
                        cba.postal_code,
                        a.type as account_type
                FROM pocomos_customer_sales_profiles p
                 JOIN pocomos_customers cu ON cu.id = p.customer_id
                 JOIN pocomos_customer_state cs ON cs.id = p.customer_id
                 JOIN pocomos_contracts c ON c.profile_id = p.id
                 JOIN pocomos_pest_contracts pcc ON pcc.contract_id = c.id
                 JOIN pocomos_pest_contract_service_types ppcst ON pcc.service_type_id = ppcst.id
                 JOIN pocomos_customers_accounts pa ON pa.profile_id = p.id
                 JOIN orkestra_accounts a ON
                    a.id = pa.account_id AND
                    a.type = 'CardAccount' AND
                    a.active = 1
                 JOIN pocomos_jobs j ON j.contract_id = c.id
                 JOIN pocomos_addresses ca ON ca.id = cu.contact_address_id
                 JOIN pocomos_addresses cba ON cba.id = cu.billing_address_id
            --    where 1=1
                WHERE
                    p.office_id = " . $officeId . " AND
                    p.active = 1 AND
                (
                    CONVERT(a.card_exp_year, UNSIGNED) > " . $yearStart . "
                    OR
                    (
                        CONVERT(a.card_exp_year, UNSIGNED) = " . $yearStart . "
                        AND
                        CONVERT(a.card_exp_month, UNSIGNED) >= " . $monthStart . "
                    )
                )
                AND
                (
                    CONVERT(a.card_exp_year, UNSIGNED) < " . $yearEnd . "
                    OR
                    (
                        CONVERT(a.card_exp_year, UNSIGNED) = " . $yearEnd . "
                        AND
                        CONVERT(a.card_exp_month, UNSIGNED) <= " . $monthEnd . "
                    )
                )
                ";


        // Add our customer status filter
        if ($request->customer_status) {
            $status = $this->convertArrayInStrings($request->customer_status);
            $sql .= ' AND cu.status IN (' . $status . ')';
        }

        // Add our future jobs filter
        if ($request->has_future_jobs == 1) {
            $sql .= ' AND ( j.status = \'PENDING\' OR j.status = \'RESCHEDULED\' ) AND j.type IN ( \'INITIAL\', \'REGULAR\' ) AND j.date_scheduled >= NOW()';
        }

        if ($request->service_type !== null) {
            $sql .= ' AND pcc.service_type_id = ' . $request->service_type . '';
        }

        if ($request->service_frequency) {
            $sql .= ' AND pcc.service_frequency = "' . $request->service_frequency . '"';
        }

        if ($request->job_type !== null) {
            $sql .= ' AND j.type = "' . $request->job_type . '"';
        }

        if ($request->marketing_type != null) {
            $sql .= ' AND c.found_by_type_id = ' . $request->marketing_type . '';
        }

        if ($request->autopay_configured == 1) {
            $sql .= ' AND p.autopay_account_id IS NOT NULL';
        }

        // Only show THE autopay account for each account
        if ($request->only_show_autopay_card == 1) {
            $sql .= ' AND p.autopay_account_id = a.id';
        }

        // in filter
        if ($request->search_terms) {
            $search = '"%' . $request->search_terms . '%"';
            $sql .= ' AND (
                            cu.first_name LIKE ' . $search . ' OR
                            cu.last_name LIKE ' . $search . ' OR
                            cu.email LIKE ' . $search . ' OR
                            ca.street LIKE ' . $search . ' OR
                            ca.suite LIKE ' . $search . ' OR
                            ca.city LIKE ' . $search . ' OR
                            a.name LIKE ' . $search . ' OR
                            a.alias LIKE ' . $search . ' )';
        }

        if ($request->search) {
            $search = '"%' . $request->search . '%"';

            $formatDate = date('Y/m/d', strtotime($request->search));
            $date = '"%' . str_replace("/", "-", $formatDate) . '%"';

            $sql .= ' AND (
                            CONCAT(cu.first_name," ",cu.last_name) LIKE ' . $search . ' OR
                            phoneNumber LIKE ' . $search . ' OR
                            cu.email LIKE ' . $search . ' OR
                            last_four LIKE ' . $search . ' OR
                            CONCAT(card_exp_month,"/",card_exp_year) LIKE ' . $search . ' OR
                            balance_outstanding LIKE ' . $search . ' OR
                            ppcst.name LIKE ' . $search . ' OR
                            last_service_date LIKE ' . $date . ' OR
                            next_service_date LIKE ' . $date . '
                            )';
        }

        $sql .= ' GROUP BY a.id';

        if(!$request->all_ids){
            /**For pagination */
            $count = count(DB::select(DB::raw($sql)));
            /**If result data are from DB::row query then `true` else `false` normal laravel get listing */
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $sql .= " LIMIT $perPage offset $page";
        }

        $results = DB::select(DB::raw($sql));

        $res = [];

        $now = date('Y-m');

        $i = 0;
        foreach ($results as $q) {
            // return $q;

            // Tag Filtering
            if (count($request->tags) || count($request->not_tags)) {
                // return $pestContract;

                $pestContractId = PocomosPestContract::whereContractId($q->contract_id)->first()->id;
                $tags = PocomosPestContractsTag::whereContractId($pestContractId)->pluck('tag_id')->toArray();
                $tagCount = count($tags);

                // Filter contracts based on tags selected to include
                if (count($request->tags)) {
                    $filterTags = $request->tags;
                    if (count(array_intersect($tags, $filterTags)) == 0) {
                        // Skip adding this item if we don't match the positive tag filter
                        continue;
                    }
                }

                // Filter contracts based on tags selected to reject
                if (count($request->not_tags)) {
                    $filterTags = $request->not_tags;
                    if (count(array_intersect($tags, $filterTags)) > 0) {
                        // Skip adding this item if we match the negative tag filter
                        continue;
                    }
                }
            }

            // Setup our return if we don't have it already
            // if (empty($res[$q->customer_id]) ){
            //     $res[$q->customer_id] = array();
            // }

            $res[$i]['service_type'] = $q->service_type;

            $res[$i]['first_name'] = $q->first_name;
            $res[$i]['last_name'] = $q->last_name;

            if ($q->account_id == $q->autopay_account_id) {
                $res[$i]['isAutopayCard'] = true;
            }

            $res[$i]['contract_id'] = $q->contract_id;
            $res[$i]['next_service_date'] = $q->date_scheduled;
            $res[$i]['customer_id'] = $q->customer_id;
            $res[$i]['account_id'] = $q->account_id;
            $res[$i]['last_four'] = $q->last_four;
            $res[$i]['card_exp_month'] = $q->card_exp_month;
            $res[$i]['card_exp_year'] = $q->card_exp_year;

            $expiryDate = $q->card_exp_year . '-' . $q->card_exp_month;

            if ($expiryDate < $now) {
                $res[$i]['card_expired'] = 1;
            } else {
                $res[$i]['card_expired'] = 0;
            }

            $res[$i]['email'] = $q->email;
            $res[$i]['phoneNumber'] = $q->phoneNumber;
            $res[$i]['balance_outstanding'] = $q->balance_outstanding;
            $res[$i]['last_service_date'] = $q->last_service_date;
            $res[$i]['street'] = $q->street;
            $res[$i]['suite'] = $q->suite;
            $res[$i]['city'] = $q->city;
            $res[$i]['postal_code'] = $q->postal_code;
            $res[$i]['account_type'] = $q->account_type;

            $i++;
        }

        // return $results;

        // return $res;

        if ($request->download) {
            return Excel::download(new ExportCardDetails($res), 'ExportCardDetails.csv');
        }

        $allIds = [];

        if ($request->all_ids) {

            $contractIds = collect($res)->pluck('contract_id');
            $custIds = collect($res)->pluck('customer_id');
            $accIds = collect($res)->pluck('account_id');

            $i=0;
            foreach($accIds as $aId){
                $allIds[$i]['contract_id'] = $contractIds[$i];
                $allIds[$i]['customer_id'] = $custIds[$i];
                $allIds[$i]['account_id'] = $aId;
                $i++;
            }
            // return $allIds;
        }

        return $this->sendResponse(true, 'Cards list ', [
            'results' => $request->all_ids ? [] : $res,
            'count' => $count ?? null,
            'all_ids' => $request->all_ids ? $allIds : [],
        ]);
    }

    public function editCard($customerId)
    {
        // Account Alias or Note = alias
        //Payment Method = static CC

        $profile = PocomosCustomerSalesProfile::where('customer_id', $customerId)->firstOrFail();
        $profileId = $profile->id;
        $autopay = $profile->autopay;

        $account = PocomosCustomersAccount::with('account_detail')->whereProfileId($profileId)->firstOrFail();
        $account->autopay = $autopay;

        return $this->sendResponse(true, 'Card detail', $account);
    }

    public function updateCard(Request $request, $accountId)
    {
        $v = validator($request->all(), [
            'alias' => 'required',
            'autopay' => 'required|boolean',
            'profile_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $account = OrkestraAccount::whereId($accountId)->update([
            'alias' => $request->alias
        ]);

        $autopay_account_id = $request->autopay ? $accountId : null;

        $profile = PocomosCustomerSalesProfile::whereId($request->profile_id)->firstOrFail();
        $profile->autopay = $request->autopay;
        $profile->autopay_account_id = $autopay_account_id;
        $profile->save();

        return $this->sendResponse(true, 'Account updated successfully.');
    }


    public function enqueueCustomersAction(Request $request)
    {
        $v = validator($request->all(), [
            'contract_ids' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $invoiceIds =  [];

        $jobs = PocomosJob::join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_company_offices as pco', 'pcsp.office_id', 'pco.id')
            ->join('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
            ->whereIn('pc.id', $request->contract_ids)
            ->whereIn('pi.status', ['Due','Past due','Collections','In collections'])
            ->get();

        foreach ($jobs as $job) {
            $invoiceIds[] =  $job->invoice_id;
        }

        // return $invoiceIds;

        BulkCardChargeJob::dispatch($invoiceIds);

        return $this->sendResponse(true, 'The server is processing these transactions, you will be notified when it finishes.');
    }

    /**
     * Sends a Form Letter.
     *
     * @param Request $request
     */
    public function sendCustomerSms(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'customers' => 'required|array',
            'form_letter' => 'nullable',
            'message' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office_id = $request->office_id;
        $customerIds = $request->customers;
        $letterId = $request->form_letter ?? null;

        if ($letterId) {
            $letter = PocomosSmsFormLetter::whereActive(true)->whereOfficeId($office_id)->findOrFail($letterId);
        } else {
            $message = $request->message;

            $letter = new PocomosSmsFormLetter();
            $letter->office_id = $office_id;
            $letter->category = 0;
            $letter->title = '';
            $letter->message = $message;
            $letter->description = '';
            $letter->confirm_job = 1;
            $letter->require_job = $message;
            $letter->active = $message;
            $letter->save();
        }

        $count = 0;
        foreach ($customerIds as $customerId) {
            $customer = PocomosCustomer::with('contact_address')->findOrFail($customerId);

            $count += $this->sendSmsFormLetter($letter, $customer);
        }

        return $this->sendResponse(true, __('strings.one_params_office_message', ['param1' => $count]));
    }
}
