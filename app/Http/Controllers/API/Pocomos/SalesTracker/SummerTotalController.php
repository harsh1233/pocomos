<?php

namespace App\Http\Controllers\API\Pocomos\SalesTracker;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosReportSummerTotalConfiguration;
use App\Models\Pocomos\PocomosReportSummerTotalConfigurationStatus;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use App\Exports\ExportSummerTotal;
use Excel;

class SummerTotalController extends Controller
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

        return $this->sendResponse(true, 'Summer totals', $branches);
    }

    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'year'      => 'in:last-year,this-year',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $groupBy = $request->group_by;

        $year = $request->year == 'last-year' ? (date('Y') - 1) : date('Y');

        $startDate = $year . '-01-01';
        $endDate = $year . '-12-31';

        // $childOfficeIdsArr = PocomosCompanyOffice::whereParentId($officeId)->pluck('id')->toArray();
        // $childOfficeIds    = $childOfficeIdsArr ? implode(',', $childOfficeIdsArr) : $officeId;

        $OfficeIds    = $request->office_ids ? implode(',', $request->office_ids) : $officeId;

        // $parentId = $pocomosCompanyOffice->parent_id;
        $summerTotalConfig = PocomosReportSummerTotalConfiguration::whereOfficeId($officeId)->firstorfail();

        $minimum = ($groupBy == 'branch-salesperson') ? $summerTotalConfig->salesperson_minimum : $summerTotalConfig->branch_minimum;

        $salesStatusIdsArr = PocomosReportSummerTotalConfigurationStatus::whereConfigurationId($summerTotalConfig->id)->pluck('sales_status_id')->toArray();
        $salesStatusIds = implode(',', $salesStatusIdsArr);

        if ($groupBy == 'branch-salesperson') { //group by salesperson
            $sql = 'Select CONCAT(u.first_name, \' \', u.last_name) as name, COUNT(cs.id) as total
                        from pocomos_contracts c
                        join pocomos_reports_contract_states cs on c.id = cs.contract_id
                        join pocomos_sales_status ss on c.sales_status_id = ss.id
                        join pocomos_customer_sales_profiles csp on c.profile_id = csp.id
                        join pocomos_company_offices o on o.id = csp.office_id
                        join pocomos_salespeople s on c.salesperson_id = s.id
                        join pocomos_company_office_users ou on s.user_id = ou.id
                        join pocomos_company_office_user_profiles oup on ou.profile_id = oup.id
                        join orkestra_users u on ou.user_id = u.id
                        WHERE ss.id in (' . $salesStatusIds . ')
                        AND c.date_created BETWEEN "'.$startDate.'" AND "'.$endDate.'"
                        AND o.id in (' . $OfficeIds . ')';

            if ($request->search) {
                $search = '"%'.$request->search.'%"';

                $sql .= ' AND (
                                CONCAT(u.first_name, \' \', u.last_name) LIKE '.$search.')';
            }

            $sql .= ' GROUP BY oup.id HAVING total >= ' . $minimum . '';
        } else { //group by branch
            $sql = 'Select o.name as name, COUNT(cs.id) as total
                        from pocomos_contracts c
                        join pocomos_reports_contract_states cs on c.id = cs.contract_id
                        join pocomos_sales_status ss on c.sales_status_id = ss.id
                        join pocomos_customer_sales_profiles csp on c.profile_id = csp.id
                        join pocomos_company_offices o on o.id = csp.office_id
                        WHERE ss.id in (' . $salesStatusIds . ')
                        AND c.date_created BETWEEN "'.$startDate.'" AND "'.$endDate.'"
                        AND o.id in (' . $OfficeIds . ')';

            if ($request->search) {
                $search = '"%'.$request->search.'%"';

                $sql .= ' AND o.name LIKE '.$search.'';
            }

            $sql .= ' GROUP BY o.id HAVING total >= ' . $minimum . '';
        }

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $results = DB::select(DB::raw($sql));

        $total = 0;
        foreach ($results as $result) {
            $total += $result->total;
        }

        $results = array('data' => $results);
        $results['total'] = $total;

        // return $results;

        if ($request->download) {
            return Excel::download(new ExportSummerTotal($results), 'ExportSummerTotal.csv');
        }

        return $this->sendResponse(true, 'List', [
            'Summer_totals_result' => $results,
            'count' => $count,
        ]);
    }
}
