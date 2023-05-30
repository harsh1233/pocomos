<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use DB;
use Excel;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosJob;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Orkestra\OrkestraUser;
use App\Exports\ExportCancelledReport;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosFormLetter;
use App\Models\Pocomos\PocomosJobProduct;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosSmsFormLetter;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class DelinquentReportController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id','name']);

        /* $sql = 'SELECT s.id, CONCAT(u.first_name, \' \', u.last_name) as name
                                FROM pocomos_salespeople s
                                JOIN pocomos_company_office_users ou ON s.user_id = ou.id AND ou.office_id = ' . $request->office_id . '
                                JOIN orkestra_users u ON ou.user_id = u.id WHERE 1 = 1 ';

        if ($request->search_term) {
            $searchTerm = "'".$request->search_term."'";
            $sql .= ' AND (u.first_name LIKE '.$searchTerm.' OR u.last_name LIKE '.$searchTerm.' OR u.username LIKE '.$searchTerm.' OR CONCAT(u.first_name, \' \', u.last_name) LIKE '.$searchTerm.')';
        } */

        $ids = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereActive(true)->pluck('id');
        $technicians = PocomosTechnician::with('user_detail.user_details:id,first_name,last_name')->whereIn('user_id', $ids)->whereActive(true)->get();

        return $this->sendResponse(true, 'Usage Report filters', [
            'branches'      => $branches,
            'technicians'   => $technicians,
        ]);
    }

    // findSalespeopleByOfficeAction - salesController
    public function getSalesPeopleByOffices(Request $request)
    {
        $v = validator($request->all(), [
            'office_ids' => 'required|array',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;
        $officeIds      = implode(',', $request->office_ids);

        $sql = 'SELECT DISTINCT ou.profile_id as id, CONCAT(u.first_name, \' \', u.last_name) as name, s.id as salespeople_id
                                FROM pocomos_salespeople s
                                JOIN pocomos_company_office_users ou ON s.user_id = ou.id
                                JOIN orkestra_users u ON ou.user_id = u.id ';

        $sql .= 'WHERE ou.office_id IN (' . $officeIds . ')';

        $sql .= ' AND ou.active = 1 AND s.active = 1';

        if ($request->search) {
            $search = "'%" . $request->search . "%'";
            $sql .= ' AND (u.first_name LIKE ' . $search . ' OR u.last_name LIKE ' . $search . '
                 OR u.username LIKE ' . $search . ' 
                 OR CONCAT(u.first_name, \' \', u.last_name) LIKE ' . $search . ')';
        }

        $sql .= ' ORDER BY name';

        $count = count(DB::select(DB::raw($sql)));
        $paginateDetails = $this->getPaginationDetails($page, $perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $salesPeople = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'Salespeople by offices', [
            'salespeople'   => $salesPeople,
            'count'   => $count,
        ]);
    }

    public function findTechniciansWithOfficeAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_ids' => 'required|array',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;
        $officeIds      = implode(',', $request->office_ids);

        $sql = 'SELECT DISTINCT t.id, CONCAT(u.first_name, \' \', u.last_name) as name
                    FROM pocomos_technicians t
                    JOIN pocomos_company_office_users ou ON t.user_id = ou.id
                    JOIN orkestra_users u ON ou.user_id = u.id ';

        $sql .= 'WHERE ou.office_id IN (' . $officeIds . ') AND (u.active = true AND ou.active = true AND t.active = true)';

        if ($request->search) {
            $search = "'%" . $request->search . "%'";
            $sql .= ' AND (u.first_name LIKE ' . $search . ' OR u.last_name LIKE ' . $search . '
                 OR u.username LIKE ' . $search . ' 
                 OR CONCAT(u.first_name, \' \', u.last_name) LIKE ' . $search . ')';
        }

        $sql .= ' ORDER BY name';

        $count = count(DB::select(DB::raw($sql)));
        $paginateDetails = $this->getPaginationDetails($page, $perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $technicians = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'Technicians by offices', [
            'technicians'   => $technicians,
            'count'   => $count,
        ]);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'date_type' => 'required',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $dateType = $request->date_type;

        $status = $dateType == 'deactivated' ? 'inactive' : 'on-hold';

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $pendingCreditHold = $request->pending_credit_hold;

        $contractStartDate = $request->contract_start_date;
        $contractEndDate = $request->contract_end_date;

        $officeIds      = $request->office_ids ? $request->office_ids : [];
        $salesPeopleIds      = $request->salespeople_ids ? $request->salespeople_ids : [];
        $technicianIds      = $request->technician_ids ? $request->technician_ids : [];

        $query = PocomosPestContract::select(
            '*',
            'pocomos_pest_contracts.id',
            'pc.status as contract_status',
            'pcug.status as customer_status',
            'pc_psr.description as contract_status_reason',
            'pcu_psr.description as cust_status_reason',
            'pa.name as agreement_name',
            'ppcst.name as service_type',
            'out.first_name as tech_fname',
            'out.last_name as tech_lname',
            'ous.first_name as salesp_fname',
            'ous.last_name as salesp_lname',
            'pss.name as contract_sales_status',
            'pcug.first_name as cust_fname',
            'pcug.last_name as cust_lname'
        )
        ->join('pocomos_pest_agreements as ppa', 'pocomos_pest_contracts.agreement_id', 'ppa.id')
        ->join('pocomos_agreements as pa', 'ppa.agreement_id', 'pa.id')
        ->join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')

        // added
        ->leftJoin('pocomos_customer_sales_profiles as pcspg', 'pc.profile_id', 'pcspg.id')
        ->leftJoin('pocomos_customers as pcug', 'pcspg.customer_id', 'pcug.id')
        ->leftJoin('pocomos_addresses as pca', 'pcug.contact_address_id', 'pca.id')
        ->leftJoin('pocomos_status_reasons as pc_psr', 'pc.status_reason_id', 'pc_psr.id')
        ->leftJoin('pocomos_status_reasons as pcu_psr', 'pcug.status_reason_id', 'pcu_psr.id')
        ->leftJoin('pocomos_customer_state as pcsg', 'pcug.id', 'pcsg.customer_id')
        ->leftJoin('pocomos_pest_contract_service_types as ppcst', 'pocomos_pest_contracts.service_type_id', 'ppcst.id')
        ->leftJoin('pocomos_technicians as pt', 'pocomos_pest_contracts.technician_id', 'pt.id')
        ->leftJoin('pocomos_company_office_users as pcout', 'pt.user_id', 'pcout.id')
        ->leftJoin('orkestra_users as out', 'pcout.user_id', 'out.id')
        ->leftJoin('pocomos_salespeople as psg', 'pc.salesperson_id', 'psg.id')
        ->leftJoin('pocomos_company_office_users as pcous', 'psg.user_id', 'pcous.id')
        ->leftJoin('orkestra_users as ous', 'pcous.user_id', 'ous.id')
        ->leftJoin('pocomos_sales_status as pss', 'pc.sales_status_id', 'pss.id')
        ->leftJoin('pocomos_customers_notes as pcn', 'pcspg.customer_id', 'pcn.customer_id')
        ->leftJoin('pocomos_notes as pn', 'pcn.note_id', 'pn.id')

        ->whereIn('pa.office_id', $officeIds)
        ->orderBy('pc.date_start', 'ASC');

        if (!empty($salesPeopleIds)) {
            $query->join('pocomos_salespeople as ps', 'pc.salesperson_id', 'ps.id')
                ->join('pocomos_company_office_users as pcou', 'ps.user_id', 'pcou.id')
                ->join('pocomos_company_office_user_profiles as pcoup', 'pcou.profile_id', 'pcoup.id')
                ->whereIn('pcoup.id', $salesPeopleIds);
        }

        if (!empty($technicianIds)) {
            $query->whereIn('pocomos_pest_contracts.technician_id', $technicianIds);
        }

        if ($contractStartDate && $contractEndDate) {
            $query->whereBetween('pc.date_start', [$contractStartDate, $contractEndDate]);
        }

        if ($dateType == 'cancelled') {
            $query->whereBetween('pc.date_cancelled', [$startDate, $endDate])
            ->where('pc.status', 'Cancelled');
        } else {
            $query->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
                ->whereBetween('pcu.date_deactivated', [$startDate, $endDate])
                ->where('pcu.status', $status);
            if ($pendingCreditHold) {
                $query->join('pocomos_invoices as pi', 'pc.id', 'pi.contract_id')
                ->join('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
                ->where('pcs.balance_overall', '>', 0)
                ->whereIn('pi.status', ['Due','Past due'])
                ->where('pi.balance', '>', 0)
                ->whereNotNull('pi.date_due')
                ->whereBetween('pi.date_due', [$startDate, $endDate]);
            }
        }

        if ($request->search) {
            $search = '%'.$request->search.'%';

            $formatDate = date('Y/m/d', strtotime($request->search));
            $date = '%'.str_replace("/", "-", $formatDate).'%';

            $query->where(function ($query) use ($search, $date) {
                $query->where('pcug.first_name', 'like', $search)
                ->orWhere('pcug.last_name', 'like', $search)
                ->orWhere('pc.status', 'like', $search)
                ->orWhere('pcug.status', 'like', $search)
                ->orWhere('pc_psr.description', 'like', $search)
                ->orWhere('pcu_psr.description', 'like', $search)
                ->orWhere('balance_overall', 'like', $search)
                ->orWhere('pc.date_cancelled', 'like', $date)
                ->orWhere('pcug.date_deactivated', 'like', $date)
                ->orWhere('pa.name', 'like', $search)
                ->orWhere('ppcst.name', 'like', $search)
                ->orWhere('out.first_name', 'like', $search)
                ->orWhere('out.last_name', 'like', $search)
                ->orWhere('ous.first_name', 'like', $search)
                ->orWhere('ous.last_name', 'like', $search)
                ->orWhere('pss.name', 'like', $search)
                ->orWhere('pocomos_pest_contracts.recurring_price', 'like', $search)
                ->orWhere('pn.body', 'like', $search)
                ;
            });
        }

        if(!$request->all_ids){
            /**For pagination */
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $count = $query->count();
            $query->skip($perPage * ($page - 1))->take($perPage);
        }
        
        $results = $query->get()->makeHidden('agreement_body')->toArray();

        if ($dateType == 'deactivated' || $dateType == 'on-hold') {
            $seenCustomers = array();
            $results = array_filter($results, function ($res) use ($seenCustomers) {
                if (isset($seenCustomers[$res['profile_id']])) {
                    return false;
                }

                $seenCustomers[$res['profile_id']] = true;

                return true;
            });
        }

        if ($request->initial_service_completed) {
            foreach ($results as $key => $contract) {
                $numberOfJobsCompleted = DB::select(DB::raw('SELECT id FROM pocomos_jobs WHERE 
                contract_id = '.$contract['id'].' AND status = "Complete" LIMIT 1'));

                if (!$numberOfJobsCompleted) {
                    unset($results[$key]);
                }
            }
        }

        $results=array_values($results);

        $defaultSalesStatus = PocomosSalesStatus::whereOfficeId($officeId)->whereDefaultStatus(1)->whereActive(1)->first();
        $defaultSalesStatus = $defaultSalesStatus ? $defaultSalesStatus->name : null;

        $data = array_map(function ($pcc) use ($dateType, $defaultSalesStatus) {
            $balance = 0;
            if ($dateType == 'cancelled') {
                $status = $pcc['contract_status'];
                $date = $pcc['date_cancelled'];
                $reason = $pcc['contract_status_reason'];
            } else {
                $status = $pcc['customer_status'];
                $date = $pcc['date_deactivated'];
                $reason = $pcc['cust_status_reason'];
                $balance = $pcc['balance_overall'];
            }

            $result = array(
                'ppcid' => $pcc['id'],
                'customer_id' => $pcc['customer_id'],
                'first_name' => $pcc['cust_fname'],
                'last_name' => $pcc['cust_lname'],
                'contact_address' => $pcc['street'],
                'status' => $status,
                'reason' => $reason,
                'balance' => $balance,
                'contract_type' => $pcc['agreement_name'],
                'service_type' => $pcc['service_type'],
                'recurring_price' => $pcc['recurring_price'],
                'date' => $date,
                'technician' => $pcc['tech_fname'].' '.$pcc['tech_lname'],
                'salesperson' => $pcc['salesp_fname'].' '.$pcc['salesp_lname'],
                'sales_status' => $pcc['contract_sales_status'] ?? $defaultSalesStatus,
                'last_note' => $pcc['body'],
            );

            return $result;
        }, $results);

        // return $results;

        // return $data;

        // dd($query);

        if($request->all_ids){
            $allIds = collect($data)->pluck('customer_id');
            $data = [];
        }

        if ($request->download) {
            return Excel::download(new ExportCancelledReport($data), 'ExportCancelledReport.csv');
        }

        return $this->sendResponse(true, 'Cancelled Report results', [
            'results' => $data,
            'count' => $count ?? null,
            'type' => $dateType,
            'all_ids' => $allIds ?? []
        ]);
    }

    /**
     * Sends a Form Letter.
     *
     * @param Request $request
     */
    public function sendCustomerEmail(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'customers' => 'required|array',
            'form_letter' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office_id = $request->office_id;
        $customerIds = $request->customers;
        $letterId = $request->form_letter;

        $letter = PocomosFormLetter::whereActive(true)->whereOfficeId($office_id)->findOrFail($letterId);

        $result = $this->sendFormLetterFromCustomers($customerIds, $letterId, $office_id);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Email sent']));
    }

    /**
     * Sends a Form Letter.
     *
     * @param Request $request
     */
    public function sendCustomerSms(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
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
            $letter->require_job = false;
            $letter->active = true;
            $letter->save();
        }

        $count = 0;
        foreach ($customerIds as $customerId) {
            // return $customerId;
            $customer = PocomosCustomer::with('contact_address')->findOrFail($customerId);

            $count += $this->sendSmsFormLetter($letter, $customer);
        }

        return $this->sendResponse(true, __('strings.one_params_office_message', ['param1' => $count]));
    }
}
