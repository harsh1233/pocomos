<?php

namespace App\Http\Controllers\API\Pocomos\SalesTracker;

use DB;
use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Orkestra\OrkestraUser;
use App\Exports\ExportSalesStatusReport;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosSalesStatusKanbanViewSetting;
use App\Models\Pocomos\PocomosReportSummerTotalConfiguration;
use App\Models\Pocomos\PocomosReportSummerTotalConfigurationStatus;

class SalesStatusModifierController extends Controller
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

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id', 'name']);

        $PocomosSalesStatus = PocomosSalesStatus::whereActive(true)->whereOfficeId($officeId)->get(['id', 'name']);

        return $this->sendResponse(
            true,
            'Sales Status Modifier',
            [
                'branches' => $branches,
                'sales_status' => $PocomosSalesStatus,
            ]
        );
    }

    public function findTechnicianByOfficeAction(Request $request)
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

        $officeIds      = implode(',', $request->office_ids);

        $sql = 'SELECT DISTINCT ou.profile_id as id, CONCAT(u.first_name, \' \', u.last_name) as name, t.id as technician_id
                    FROM pocomos_technicians t
                    JOIN pocomos_company_office_users ou ON t.user_id = ou.id
                    JOIN orkestra_users u ON ou.user_id = u.id ';

        $sql .= 'WHERE ou.office_id IN (' . $officeIds . ') AND t.active = 1 AND ou.active = 1 AND ou.deleted = 0 AND u.active = 1';

        if ($request->search) {
            $search = "'%" . $request->search . "%'";
            $sql .= ' AND (u.first_name LIKE ' . $search . ' OR u.last_name LIKE ' . $search . '
                 OR u.username LIKE ' . $search . ' 
                 OR CONCAT(u.first_name, \' \', u.last_name) LIKE ' . $search . ')';
        }

        $sql .= ' ORDER BY name';

        $count = count(DB::select(DB::raw($sql)));

        if($request->page && $request->perPage){
            $page = $request->page;
            $perPage = $request->perPage;
            $paginateDetails = $this->getPaginationDetails($page, $perPage, true);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $sql .= " LIMIT $perPage offset $page";
        }

        $technicians = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'Technicians by offices', [
            'technicians'   => $technicians,
            'count'   => $count,
        ]);
    }


    // public function search($officeIds, $dateType, $startDate, $endDate, $salesPeopleIds, $salesStatusIds, $jobStatus,  $techniciansIds)
    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            // 'office_ids' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeIds = $request->office_ids ? implode(',', $request->office_ids) : "''";

        $dateType = $request->date_type;

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $salesPeopleIds = $request->salespeople_ids ? implode(',', $request->salespeople_ids) : null;

        $salesStatusIds = $request->sales_status_ids ? implode(',', $request->sales_status_ids) : null;

        $techniciansIds = $request->technicians_ids ? implode(',', $request->technicians_ids) : null;

        $jobStatus = $request->job_status ? implode(',', $request->job_status) : null;

        if ($dateType == 'initial-job') {
            $dateConstraint = 'COALESCE(j.date_completed, j.date_scheduled) BETWEEN "' . $startDate . '" AND "' . $endDate . '" ';
        } else {
            $dateConstraint = 'co.date_start BETWEEN "'.$startDate.'" AND "'.$endDate.'" ';
        }

        $sql = 'SELECT
                    pcc.id                                           as pcc_id,
                    c.id                                             as customer_id,
                    c.first_name                                     as customer_first_name,
                    c.last_name                                      as customer_last_name,
                    c.company_name                                   as customer_company_name,
                    c.email                                          as customer_email_address,
                    CONCAT(c.first_name, " ",c.last_name)            as customer_name,
                    pn.number                                        as customer_phone,
                    pmt.name                                         as marketing_type,
                    CONCAT(ca.street, " ",  ca.city, ", ",reg.code, " ", ca.postal_code) as customer_contact_address,
                    DATE_FORMAT(co.date_start,"%c/%d/%Y")            as date_start,
                    COALESCE(ss.name, "No status")                   as sales_status,
                    pcc.initial_price,
                    COALESCE(j.date_completed, j.date_scheduled)                               as job_date,
                    DATE_FORMAT(COALESCE(j.date_completed, j.date_scheduled),"%c/%d/%Y")       as initial_date,
                    CONCAT(u.first_name, " ",u.last_name)            as salesperson_name,
                    j.status                                         as job_status,
                    i.status                                         as invoice_status,
                    co.id                                            AS contract_id,
                    pcc.recurring_price                              AS recurring_price,
                    pcc.initial_price                                AS contract_value,
                    pcc.modifiable_original_value                    AS original_value,
                    o.list_name                                      AS branch_name,
                    IF(csp.autopay = 1,\'Y\',\'N\')                  AS autopay,
                    c.external_account_id                            AS customer_external_account_id,
                    sp.pay_level,
                    ppcst.name                                       AS service_type,
                    a.name

                    from pocomos_pest_contracts pcc
                     join pocomos_contracts co                        on pcc.contract_id = co.id
                     JOIN pocomos_marketing_types pmt                 on co.found_by_type_id = pmt.id
                     join pocomos_pest_agreements pca                 on pcc.agreement_id = pca.id
                     join pocomos_agreements a                        on a.id = pca.agreement_id
                     join pocomos_company_offices o                   on o.id = a.office_id
                     join pocomos_customer_sales_profiles csp         on csp.id = co.profile_id
                     join pocomos_customers_phones AS cph        ON csp.id = cph.profile_id
                     join pocomos_phone_numbers AS pn            ON cph.phone_id = pn.id
                     join pocomos_customers c                    on csp.customer_id = c.id
                     join pocomos_addresses ca                   on c.contact_address_id = ca.id
                     join orkestra_countries_regions reg		 on ca.region_id = reg.id
                    left join pocomos_sales_status ss                on co.sales_status_id = ss.id
                    left join pocomos_jobs j                         on j.contract_id = pcc.id AND j.type = "Initial"
                    left join pocomos_invoices i                     on j.invoice_id = i.id
                     join pocomos_reports_contract_states cs          on cs.contract_id = co.id
                     join pocomos_salespeople s                       on s.id = co.salesperson_id
                     join pocomos_company_office_users ou             on s.user_id = ou.id
                     join pocomos_company_office_user_profiles oup    on ou.profile_id = oup.id
                     join orkestra_users u                            on ou.user_id = u.id
                     JOIN pocomos_salesperson_profiles sp             ON sp.office_user_profile_id = oup.id
                     JOIN pocomos_pest_contract_service_types ppcst   ON pcc.service_type_id = ppcst.id
                      WHERE ' . $dateConstraint . '
                    AND o.id in (' . $officeIds . ') AND c.active = 1';

        if ($salesPeopleIds) {
            $sql .= ' AND oup.id IN (' . $salesPeopleIds . ')';
        }

        if ($salesStatusIds) {
            $sql .= ' AND ss.id IN (' . $salesStatusIds . ')';
        }

        if ($jobStatus) {
            $sql .= ' AND j.status IN ('.$jobStatus.')';
        }

        if ($techniciansIds) {
            $techSql = 'SELECT t.id
                    FROM pocomos_technicians t
                    join pocomos_company_office_users ou on t.user_id = ou.id
                    join pocomos_company_office_user_profiles oup on ou.profile_id = oup.id
                    WHERE oup.id in (' . $techniciansIds . ')';

            $ids = DB::select(DB::raw($techSql));

            if (count($ids) > 0) {
                foreach ($ids as $id) {
                    $techIdsArr[] = $id->id;
                }
                $techIds = implode(',', $techIdsArr);
                // return $techIds;
                $sql .= 'AND (pcc.technician_id in (' . $techIds . ') OR j.technician_id in (' . $techIds . '))';
            }
        }

        if ($request->search) {
            $search = '"%' . $request->search . '%"';

            $sql .= ' AND (
                CONCAT(c.first_name, " ",c.last_name) LIKE ' . $search . '
                OR CONCAT(ca.street, " ",  ca.city, ", ",reg.code, " ", ca.postal_code) LIKE ' . $search . '
                OR j.status LIKE ' . $search . '
                OR i.status LIKE ' . $search . '
                OR CONCAT(u.first_name, " ",u.last_name) LIKE ' . $search . '
                OR pcc.initial_price LIKE ' . $search . '
                OR COALESCE(ss.name, "No status") LIKE ' . $search . '
            )';
        }

        $sql .= ' GROUP BY pcc_id
                 HAVING min(job_date) = job_date OR job_date is NULL';

        if($request->all_ids){
            $results = DB::select(DB::raw($sql));
            $allIds = collect($results)->pluck('contract_id');
            $results = [];
        }else{
            /**For pagination */
            $count = count(DB::select(DB::raw($sql)));
                        
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $sql .= " LIMIT $perPage offset $page";
            $results = DB::select(DB::raw($sql));
        }
        

        if ($request->download) {
            return Excel::download(new ExportSalesStatusReport($results), 'ExportSalesStatusReport.csv');
        }

        return $this->sendResponse(true, 'Sales Status Modifier Search Results', [
            'list' => $results,
            'count' => $count ?? null,
            'all_ids' => $allIds ?? [],
        ]);
    }

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'contract_ids'    => 'required',
            'sales_status_id' => 'required|exists:pocomos_sales_status,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pocomosSalesStatus = PocomosSalesStatus::whereId($request->sales_status_id)->first();

        if (!$pocomosSalesStatus) {
            return $this->sendResponse(false, 'Invalid sales status');
        }

        $contractIds = implode(',', $request->contract_ids);

        $sql = 'UPDATE pocomos_contracts c
                LEFt JOIN pocomos_sales_status ss ON c.sales_status_id = ss.id
                SET c.sales_status_id = ' . $request->sales_status_id . '
                WHERE c.id IN (' . $contractIds . ')';

        DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'Status updated successfully');
    }

    public function getViewSetting(Request $request, $officeId)
    {
        $viewSetting = PocomosSalesStatusKanbanViewSetting::whereOfficeId($officeId)->whereActive(true)->first();

        if (!$viewSetting) {
            $viewSetting = PocomosSalesStatusKanbanViewSetting::create([
                'office_id' => $officeId,
                'first_name' => 1,
                'last_name' => 1,
                'company_name' => 1,
                'phone_number' => 0,
                'email_address' => 0,
                'active' => 1,
            ]);

            $viewSetting = PocomosSalesStatusKanbanViewSetting::find($viewSetting->id);
        }

        return $this->sendResponse(true, 'View setting', $viewSetting);
    }

    public function updateViewSetting(Request $request, $id)
    {
        $v = validator($request->all(), [
            'phone_number' => 'required|in:1,0',
            'email_address' => 'required|in:1,0',
            'address' => 'required|in:1,0',
            'marketing_type' => 'required|in:1,0',
            'initial_job_status' => 'required|in:1,0',
            'initial_invoice_status' => 'required|in:1,0',
            'initial_price' => 'required|in:1,0',
            'sales_repo' => 'required|in:1,0',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $viewSetting = PocomosSalesStatusKanbanViewSetting::whereId($id)->update([
            'phone_number' => $request->phone_number,
            'email_address' => $request->email_address,
            'address' => $request->address,
            'marketing_type' => $request->marketing_type,
            'initial_job_status' => $request->initial_job_status,
            'initial_invoice_status' => $request->initial_invoice_status,
            'initial_price' => $request->initial_price,
            'sales_repo' => $request->sales_repo,
        ]);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Kanban View Settings has been']));
    }
}
