<?php

namespace App\Http\Controllers\API\Pocomos\Reports\AccountStatus;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosReportSummerTotalConfiguration;
use App\Models\Pocomos\PocomosReportSummerTotalConfigurationStatus;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosPest;
use App\Models\Pocomos\PocomosTeam;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use Excel;
use App\Exports\ExportAccountStatusTotals;

class AccountStatusTotalsController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'group_by'  => 'in:team,salespeople-by-branches,salespeople-by-company,marketing-types-by-branches,marketing-types-by-company,branches-by-company'
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

        if ($request->group_by == 'team') {
            $teams = PocomosTeam::whereOfficeId($officeId)->whereActive(true)->get(['id', 'office_id', 'name']);
        } elseif ($request->group_by == 'salespeople-by-branches') {
            $officeIdsArr = $request->office_ids ? $request->office_ids : $branches->pluck('id')->toArray();
            $officeIds    = implode(',', $officeIdsArr);

            $sql = 'SELECT DISTINCT ou.profile_id as id, CONCAT(u.first_name, \' \', u.last_name) as name
                                FROM pocomos_salespeople s
                                JOIN pocomos_company_office_users ou ON s.user_id = ou.id
                                JOIN orkestra_users u ON ou.user_id = u.id ';

            $sql .= 'WHERE ou.office_id IN (' . $officeIds . ')';

            if ($request->search_term) {
                $searchTerm = "'" . $request->search_term . "'";
                $sql .= ' AND (u.first_name LIKE ' . $searchTerm . ' OR u.last_name LIKE ' . $searchTerm . ' OR u.username LIKE ' . $searchTerm . ' OR CONCAT(u.first_name, \' \', u.last_name) LIKE ' . $searchTerm . ')';
            }

            $sql .= ' ORDER BY name';

            $salesPeople = DB::select(DB::raw($sql));
        } elseif ($request->group_by == 'salespeople-by-company') {
            $officeIdsArr = $branches->pluck('id')->toArray();
            $officeIds    = implode(',', $officeIdsArr);

            $sql = 'SELECT DISTINCT ou.profile_id as id, CONCAT(u.first_name, \' \', u.last_name) as name
                                FROM pocomos_salespeople s
                                JOIN pocomos_company_office_users ou ON s.user_id = ou.id
                                JOIN orkestra_users u ON ou.user_id = u.id ';

            $sql .= 'WHERE ou.office_id IN (' . $officeIds . ')';

            if ($request->search_term) {
                $searchTerm = "'" . $request->search_term . "'";
                $sql .= ' AND (u.first_name LIKE ' . $searchTerm . ' OR u.last_name LIKE ' . $searchTerm . ' OR u.username LIKE ' . $searchTerm . ' OR CONCAT(u.first_name, \' \', u.last_name) LIKE ' . $searchTerm . ')';
            }

            $sql .= ' ORDER BY name';

            $salesPeople = DB::select(DB::raw($sql));
        } elseif ($request->group_by == 'marketing-types-by-branches') {
            $officeIdsArr = $request->office_ids ? $request->office_ids : $branches->pluck('id')->toArray();
            $officeIds    = implode(',', $officeIdsArr);

            $sql = 'SELECT DISTINCT mt.id, mt.name FROM pocomos_marketing_types as mt';

            $sql .= ' WHERE office_id IN (' . $officeIds . ')';

            $sql .= ' AND active = true';

            $params = array();
            if ($request->search_term) {
                $searchTerm = "'" . $request->search_term . "'";
                $sql .= ' AND (name LIKE ' . $searchTerm . ')';
            }

            $sql .= ' GROUP BY name';
            $sql .= ' ORDER BY name';

            $marketingTypes = DB::select(DB::raw($sql));
        } elseif ($request->group_by == 'marketing-types-by-company') {
            $officeIdsArr = $branches->pluck('id')->toArray();
            $officeIds    = implode(',', $officeIdsArr);

            $sql = 'SELECT DISTINCT mt.id, mt.name FROM pocomos_marketing_types as mt';

            $sql .= ' WHERE office_id IN (' . $officeIds . ')';

            $sql .= ' AND active = true';

            $params = array();
            if ($request->search_term) {
                $searchTerm = "'" . $request->search_term . "'";
                $sql .= ' AND (name LIKE ' . $searchTerm . ')';
            }

            $sql .= ' GROUP BY name';
            $sql .= ' ORDER BY name';

            $marketingTypes = DB::select(DB::raw($sql));
        }

        return $this->sendResponse(
            true,
            'Account Status Totals',
            [
                'branches'          => $branches,
                'teams'             => isset($teams) ? $teams : null,
                // 'sales_people'      => isset($salesPeople) ? $salesPeople : null,
                // 'marketing_types'   => isset($marketingTypes) ? $marketingTypes : null,
            ]
        );
    }

    public function salespeopleByBranchesAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_ids' => 'required|array',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
        ]);

        $page = $request->page;
        $perPage = $request->perPage;

        $officeIdsArr = $request->office_ids;
        $officeIds    = implode(',', $officeIdsArr);

        $sql = 'SELECT DISTINCT ou.profile_id as id, CONCAT(u.first_name, \' \', u.last_name) as name, u.username
                            FROM pocomos_salespeople s
                            JOIN pocomos_company_office_users ou ON s.user_id = ou.id
                            JOIN orkestra_users u ON ou.user_id = u.id ';

        $sql .= 'WHERE ou.office_id IN (' . $officeIds . ')';

        if ($request->search) {
            $searchTerm = "'%" . $request->search . "%'";
            $sql .= ' AND (u.first_name LIKE ' . $searchTerm . ' OR u.last_name LIKE ' . $searchTerm . ' OR u.username LIKE ' . $searchTerm . ' OR CONCAT(u.first_name, \' \', u.last_name) LIKE ' . $searchTerm . ')';
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

    public function maketingTypeByBranchesAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_ids' => 'required|array',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
        ]);

        $page = $request->page;
        $perPage = $request->perPage;

        $officeIdsArr = $request->office_ids;
        $officeIds    = implode(',', $officeIdsArr);

        $sql = 'SELECT DISTINCT mt.id, mt.name FROM pocomos_marketing_types as mt';

        $sql .= ' WHERE office_id IN (' . $officeIds . ')';

        $sql .= ' AND active = true';

        if ($request->search) {
            $searchTerm = "'%" . $request->search . "%'";
            $sql .= ' AND (name LIKE ' . $searchTerm . ')';
        }

        $sql .= ' GROUP BY name';
        $sql .= ' ORDER BY name';

        $count = count(DB::select(DB::raw($sql)));
        $paginateDetails = $this->getPaginationDetails($page, $perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $marketingTypes = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'marketing types by offices', [
            'marketing_types'   => $marketingTypes,
            'count'   => $count,
        ]);
    }

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'office_ids' => 'nullable|array',
            'group_by' => 'required|in:team,salespeople-by-branches,salespeople-by-company,branches-by-company,marketing-types-by-company,marketing-types-by-branches',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $officeId = $pocomosCompanyOffice->id;
        $parentId = PocomosCompanyOffice::whereId($officeId)->first()->parent_id;
        $officeId = $parentId ? $parentId : $officeId;

        $branchIds = $request->office_ids ? implode(',', $request->office_ids) : $officeId;

        $dateType = $request->date_type;

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $groupBy = $request->group_by;

        $showRookies = $request->show_rookies;
        $showVeterans = $request->show_veterans;

        $searchTerm = $request->search;

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];

        $teamId = $request->team_id ? $request->team_id : null;
        $salesPeopleIds = $request->sales_people_ids ? implode(',', $request->sales_people_ids) : null;
        $marketingTypeIds = $request->marketing_type_ids ? implode(',', $request->marketing_type_ids) : null;

        $data = $this->getData(
            $dateType,
            $startDate,
            $endDate,
            $officeId,
            $branchIds,
            $groupBy,
            $showRookies,
            $showVeterans,
            $salesPeopleIds,
            $teamId,
            $marketingTypeIds,
            $searchTerm,
            $page,
            $perPage
        );

        $salesStatuses = PocomosSalesStatus::whereOfficeId($officeId)->whereActive(true)->get();

        $q = [
            'sales_status' => $salesStatuses,
            'data' => $data,
        ];

        if ($request->download) {
            return Excel::download(new ExportAccountStatusTotals($q), 'ExportAccountStatusTotals.csv');
        }

        return $this->sendResponse(
            true,
            'Account Status Totals Results',
            [
                'data' => $data,
                'sales_status' => $salesStatuses,
            ]
        );
    }

    public function getData(
        $dateType,
        $startDate,
        $endDate,
        $officeId,
        $branchIds,
        $groupBy,
        $showRookies,
        $showVeterans,
        $salesPeopleIds,
        $teamId,
        $marketingTypeIds,
        $searchTerm,
        $page,
        $perPage
    )
    {
        if ($dateType === 'initial') {
            $dateConstraint = 'COALESCE(j.date_completed, j.date_scheduled) BETWEEN "' . $startDate . '" AND "' . $endDate . '"';
        } elseif ($dateType === 'contract-creation') {
            $endDate = date("Y-m-d 23:59:59", strtotime($endDate));
            $dateConstraint = 'c.date_start BETWEEN "' . $startDate . '" AND "' . $endDate . '"';
        } else {
            $endDate = date("Y-m-d 23:59:59", strtotime($endDate));
            $dateConstraint = 'csp.date_signed_up BETWEEN "' . $startDate . '" AND "' . $endDate . '"';
        }

        if ($groupBy === 'marketing-types-by-company' || $groupBy === 'marketing-types-by-branches') {
            $sql = 'SELECT ou.profile_id as id, pmt.name as name,';
        } else {
            $sql = 'SELECT ou.profile_id as id, CONCAT(u.first_name, " ", u.last_name) as name,';
        }

        $salesStatuses = PocomosSalesStatus::whereOfficeId($officeId)->whereActive(true)->get();

        foreach ($salesStatuses as $salesStatus) {
            $sql .= " CONCAT(SUM(CASE WHEN ss.id = " . $salesStatus->id . " THEN 1 ELSE 0 END), CONCAT(' (',CONCAT(ROUND(SUM(CASE WHEN ss.id = " . $salesStatus->id . " THEN 1 ELSE 0 END) / COUNT(c.id) * 100, 0), '%'),')')) as stat_" . $salesStatus->id . ",";
        }

        $sql .= '
                COUNT(c.id) as count,
                SUM(ss.paid) as paid,
                SUM(ss.serviced) as serviced,
                SUM(csp.autopay) as apayCount,
                CONCAT(ROUND((SUM(CASE WHEN ss.paid = 1 THEN csp.autopay ELSE 0 END) / SUM(ss.serviced)) * 100, 2), "%") as apay,
                CONCAT(SUM(csp.autopay),CONCAT(" (", CONCAT(ROUND((SUM(csp.autopay) / COUNT(c.id)) * 100, 2), "%)"))) as autopay
                FROM pocomos_contracts c
                LEFT JOIN pocomos_sales_status ss ON c.sales_status_id = ss.id
                LEFT JOIN pocomos_reports_contract_states cs ON cs.contract_id = c.id
                JOIN pocomos_salespeople s ON c.salesperson_id = s.id
                JOIN pocomos_company_office_users ou ON s.user_id = ou.id AND ou.office_id IN (' . $branchIds . ')
                JOIN orkestra_users u ON ou.user_id = u.id
                JOIN pocomos_company_offices o ON ou.office_id = o.id
                JOIN pocomos_marketing_types pmt on c.found_by_type_id = pmt.id
                ';

        if ($groupBy != 'branches-by-company') {
            $sql .= ' JOIN pocomos_company_office_user_profiles oup ON ou.profile_id = oup.id
                        JOIN pocomos_salesperson_profiles sp ON sp.office_user_profile_id = oup.id'
                . ($teamId ? ' LEFT JOIN pocomos_memberships m on sp.id = m.salesperson_profile_id
                        LEFT JOIN pocomos_teams t on m.team_id = t.id' : '');
        }

        $sql .= ' JOIN pocomos_customer_sales_profiles csp on c.profile_id = csp.id
                        JOIN pocomos_pest_contracts pcc ON pcc.contract_id = c.id
                        JOIN pocomos_jobs j ON j.contract_id = pcc.id AND j.type = "Initial"
                    WHERE ' . $dateConstraint . ' AND s.active = 1 AND ou.active = 1 
                    AND u.active = 1 AND c.active = 1
                ';

        if ($teamId !== null) {
            $sql .= ' AND t.id = ' . $teamId . '';
        }

        if (isset($salesPeopleIds) && count(explode(',', $salesPeopleIds)) > 0) {
            $sql .= ' AND ou.profile_id IN (' . $salesPeopleIds . ')';
        }

        if (isset($marketingTypeIds) && count(explode(',', $marketingTypeIds)) > 0) {
            $sql .= ' AND pmt.id IN (' . $marketingTypeIds . ')';
        }

        if ($showRookies && $groupBy != 'branches-by-company') {
            if (!$showVeterans) {
                $sql .= ' AND sp.experience <= 1';
            } else {
                $sql .= ' AND sp.experience > 1';
            }
        }

        if ($searchTerm) {
            if ($groupBy === 'branches-by-company') {
                $sql .= " AND (o.name LIKE '%" . $searchTerm . "%') ";
            } elseif ($groupBy === 'marketing-types-by-company' || $groupBy === 'marketing-types-by-branches') {
                $sql .= " AND (pmt.name LIKE '%" . $searchTerm . "%') ";
            } else {
                $sql .= " AND (u.first_name LIKE '%" . $searchTerm . "%' OR u.last_name LIKE '%" . $searchTerm . "%' OR u.username LIKE '%" . $searchTerm . "%') ";
            }

            $sql .= ($groupBy === 'branches-by-company') ? " AND (o.name LIKE '%" . $searchTerm . "%')" : " AND (u.first_name LIKE '%" . $searchTerm . "%' OR u.last_name LIKE '%" . $searchTerm . "%' OR u.username LIKE '%" . $searchTerm . "%')";
        }

        if ($groupBy === 'branches-by-company') {
            $sql .= ' GROUP BY o.id ORDER BY serviced DESC, ss.paid DESC';
        } elseif ($groupBy === 'marketing-types-by-company' || $groupBy === 'marketing-types-by-branches') {
            $sql .= ' GROUP BY c.found_by_type_id ORDER BY count DESC';
        } else {
            $sql .= ' GROUP BY ou.profile_id ORDER BY serviced DESC, ss.paid DESC';
        }

        $count = count(DB::select(DB::raw($sql)));

        $sql .= " LIMIT $perPage offset $page";

        $results = DB::select(DB::raw($sql));

        array_walk($results, function ($result, $index) use (&$results) {
            $results[$index]->rank = $index + 1;
        });

        // $totalRecords = count($results);
        // $start = 1;
        // $limit = 2;
        // $results = array_slice($results, $start, $limit);

        return array(
            'data'  => array_values($results),
            'count' => $count,
        );
    }
}
