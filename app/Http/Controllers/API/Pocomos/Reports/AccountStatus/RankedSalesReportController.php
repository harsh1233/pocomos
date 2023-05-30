<?php

namespace App\Http\Controllers\API\Pocomos\Reports\AccountStatus;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosMembership;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use App\Exports\ExportRankedSalesReport;
use Excel;

class RankedSalesReportController extends Controller
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

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id','name']);

        return $this->sendResponse(true, 'Ranked Sales Report', $branches);
    }


    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'office_ids' => 'required',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search_term' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $branchIdsArr = $request->office_ids ? $request->office_ids : null;
        $search = $request->search ? '%'.$request->search.'%' : null;

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];

        $result = $this->getData($branchIdsArr, $search, $page, $perPage);

        if ($request->download) {
            return Excel::download(new ExportRankedSalesReport($result['data']), 'ExportRankedSalesReport.csv');
        }

        return $this->sendResponse(true, 'Ranked Sales Report result', $result);
    }

    public function getData($branchIdsArr, $search, $page, $perPage)
    {
        $salesPeopleIds = PocomosSalesPeople::join('pocomos_company_office_users as pcou', 'pocomos_salespeople.user_id', 'pcou.id')
                ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->where('pocomos_salespeople.active', 1)
                ->where('pcou.active', 1)
                ->where('ou.active', 1)
                ->whereIn('pcou.office_id', $branchIdsArr)
                ->pluck('pocomos_salespeople.id')
                ->toArray();

        // return $salesPeopleIds;

        if ($salesPeopleIds) {
            $salesPeopleIds = $this->convertArrayInStrings($salesPeopleIds);
        }

        $sql = 'SELECT ou.profile_id as id, ss.id as qqq,
                CONCAT(u.first_name, " ", u.last_name) as name,
                SUM(ss.services_scheduled_yesterday) as yesterday_sales,
                SUM(ss.services_scheduled_week) as week_sales,
                SUM(ss.services_scheduled_month) as month_sales,
                SUM(ss.services_scheduled_year) as year_sales,
                SUM(ss.serviced_this_month) as month_services,
                SUM(ss.serviced_this_year) as year_services,
                ROUND((AVG(ss.autopay_account_percentage)*100), 2) as autopay_ratio,
                ROUND((SUM(ss.serviced_this_year)/SUM(ss.services_scheduled_year))*100, 2) as service_ratio
                FROM pocomos_reports_salesperson_states ss
                JOIN pocomos_salespeople s ON ss.salesperson_id = s.id
                JOIN pocomos_company_office_users ou ON s.user_id = ou.id
                JOIN orkestra_users u ON ou.user_id = u.id
                WHERE s.id IN ('.$salesPeopleIds.') 
                AND ss.type = "State"
            ';

        if ($search) {
            $sql .= ' AND (u.first_name LIKE "'.$search.'" OR u.last_name LIKE "'.$search.'" 
            OR u.username LIKE "'.$search.'" OR CONCAT(u.first_name, \' \', u.last_name) LIKE "'.$search.'")';
        }

        $sql .= ' GROUP BY ou.profile_id ORDER BY year_services DESC';

        $count = count(DB::select(DB::raw($sql)));

        $sql .= " LIMIT $perPage offset $page";

        $results = DB::select(DB::raw($sql));

        $totals = array();

        array_walk($results, function ($result, $index) use (&$results, &$totals) {
            $results[$index]->rank = $index + 1;
            $results[$index]->autopay_ratio = ($results[$index]->autopay_ratio ?: 0.00);
            $results[$index]->service_ratio = ($results[$index]->service_ratio ?: 0.00);

            foreach ($results[$index] as $key => $value) {
                if (!isset($totals[$key])) {
                    $totals[$key] = 0;
                }

                if ($key === 'autopay_ratio' || $key === 'service_ratio') {
                    // Keep a running average
                    $n = $index + 1;
                    $totals[$key] = ((($n - 1) * $totals[$key]) + $value) / $n;
                } else {
                    $totals[$key] += (int)$value;
                }
            }
        });

        // $start = 1;
        // $limit = 50;
        // // $results = array_slice($results, $start, $limit);
        // $totalDisplayRecords = $totalRecords;

        return array(
            'count' => $count,
            'data' => array_values($results),
            'totals' => $totals
        );
    }
}
