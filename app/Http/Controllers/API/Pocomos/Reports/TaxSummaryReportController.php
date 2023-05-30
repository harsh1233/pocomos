<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use Excel;
use App\Exports\ExportTaxSummaryReport;

class TaxSummaryReportController extends Controller
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

        $query = PocomosTaxCode::with('office')->whereOfficeId($officeId)->whereActive(1);

        if ($request->search) {
            $query->where('code', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%')
                ->orWhere('tax_rate', 'like', '%' . $request->search . '%');
        }

        $PocomosTaxCodes = $query->get();

        return $this->sendResponse(true, 'Tax summary Report filters', [
            'branches'    => $branches,
            'tax_codes'   => $PocomosTaxCodes,
        ]);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'office_ids' => 'required|array',
            'date_type' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeIds = $request->office_ids ? implode(',', $request->office_ids) : null;

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $dateType = $request->date_type;

        $taxCodes = $request->tax_codes ? implode(',', $request->tax_codes) : null;

        $sql1 = '';
        $havingSql='';
        $newSql = '';
        $groupBySql = '';

        $sql = 'Select t.id,
                    t.code,
                    SUM(it.price) as revenue,
                    it.sales_tax as salesTax,
                    (it.sales_tax * SUM(it.price)) AS taxPayable
                    FROM pocomos_invoice_items it
                    LEFT JOIN pocomos_tax_codes t on it.tax_code_id = t.id
                    JOIN pocomos_invoices i on it.invoice_id = i.id
                    LEFT JOIN pocomos_jobs j on j.invoice_id = i.id
                    JOIN pocomos_contracts c on i.contract_id = c.id
                    JOIN pocomos_customer_sales_profiles p on c.profile_id = p.id
                    WHERE it.active = 1
                    AND p.office_id IN (' . $officeIds . ')';

        if ($taxCodes && count(explode(',', $taxCodes)) > 0) {
            $sql .= 'AND it.tax_code_id IN ('.$taxCodes.')';
        }

        if ($dateType == 'complete') {
            $sql .= 'AND i.status <> "cancelled" ';
            $sql .= ' AND (j.status IS NULL OR j.status = "complete" )';
            $sql .= ' AND COALESCE(j.date_completed, i.date_due) BETWEEN "'.$startDate.'" AND "'.$endDate.'" ';
        } else {
            $sql .= 'AND i.status = "paid"';
            $sql .= ' AND EXISTS(Select 1
                                        FROM orkestra_transactions tr
                                        JOIN pocomos_invoice_transactions itr on itr.transaction_id = tr.id
                                        WHERE tr.date_created BETWEEN "'.$startDate.'" AND "'.$endDate.'"
                                        AND tr.status = "approved"
                                        AND itr.invoice_id = i.id)';
        }

        $search = "'%" . $request->search . "%'";
        if ($request->search) {
            $sql1 .= ' AND (t.code LIKE ' . $search . ' OR it.sales_tax LIKE ' . $search . ') ';
        }

        // dd($sql.''.$sql1);
        // dd($sql1);

        $groupBySql .=  ' GROUP BY it.tax_code_id, it.sales_tax ';

        $count = count(DB::select(DB::raw($sql.' '.$sql1.' '.$groupBySql)));

        if ($count == 0) {
            // dd(11);
            $havingSql .= ' having SUM(it.price) LIKE ' . $search . '
                            or (it.sales_tax * SUM(it.price)) LIKE ' . $search . '';
            $newSql = $sql.''.$groupBySql.''.$havingSql;
        } else {
            $newSql = $sql.''.$sql1.''.$groupBySql;
        }

        // if ($request->search) {
        //     $search = "'%" . $request->search . "%'";
        //     $sql .= ' having SUM(it.price) LIKE ' . $search . '';
        // }

        // dd($newSql);

        /**For pagination */
        $count = count(DB::select(DB::raw($newSql)));

        // return $count;


        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $newSql .= " LIMIT $perPage offset $page";

        $queryResults = DB::select(DB::raw(($newSql)));

        $totalTaxDue = 0;

        foreach ($queryResults as $result) {
            $result = (array)$result;
            $totalTaxDue += $result['taxPayable'];
        }

        // $queryResults['totalTaxDue'] = $totalTaxDue;

        $results = $queryResults;

        if ($request->download) {
            return Excel::download(new ExportTaxSummaryReport($results), 'ExportTaxSummaryReport.csv');
        }

        return $this->sendResponse(
            true,
            __('strings.list', ['name' => 'Tax summary report']),
            [
                'data'      =>  $results,
                'count' => $count,
                'total_tax' =>  $totalTaxDue,
            ]
        );
    }
}
