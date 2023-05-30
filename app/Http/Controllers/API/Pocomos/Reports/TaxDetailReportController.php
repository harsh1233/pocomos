<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosJobProduct;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use Excel;
use App\Exports\ExportTaxDetailReport;

class TaxDetailReportController extends Controller
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

        $query = PocomosTaxCode::with('office')->whereOfficeId($officeId);

        if ($request->search) {
            $query->where('code', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%')
                ->orWhere('tax_rate', 'like', '%' . $request->search . '%');
        }

        $PocomosTaxCodes = $query->get();

        return $this->sendResponse(true, 'Tax detail filters', [
            'branches'            => $branches,
            'tax_codes'   => $PocomosTaxCodes,
        ]);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'office_ids' => 'required',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'date_type' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $officeIds = $request->office_ids ? implode(',', $request->office_ids) : null;

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $dateType = $request->date_type;

        $taxCodes = $request->tax_codes ? implode(',', $request->tax_codes) : null;

        $sql = 'Select i.id AS invoice,
                        i.date_due as dateDue,
                        t.id,
                        COALESCE(t.code, "Custom") as code,
                        SUM(it.price) as revenue,
                        it.sales_tax as salesTax,
                        (it.sales_tax * SUM(it.price)) AS taxPayable,
                        p.customer_id AS customer,
                        it.tax_code_id

                        FROM pocomos_invoice_items it
                        LEFT JOIN pocomos_tax_codes t on it.tax_code_id = t.id
                        JOIN pocomos_invoices i on it.invoice_id = i.id
                        LEFT JOIN pocomos_jobs j on j.invoice_id = i.id
                        JOIN pocomos_contracts c on i.contract_id = c.id
                        JOIN pocomos_customer_sales_profiles p on c.profile_id = p.id
                        JOIN pocomos_customers cus on p.customer_id = cus.id
                        JOIN pocomos_addresses a on cus.contact_address_id = a.id
                        WHERE it.active = 1
                        AND it.type!="Adjustment"
                        AND p.office_id IN (' . $officeIds . ') ';

        if ($taxCodes) {
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

        // return $queryResults = DB::select(DB::raw(($sql)));


        // if ($request->search) {
        //     $search = "'%" . $request->search . "%'";
        //     $sql .= ' AND i.id LIKE ' . $search . ' OR i.date_due LIKE ' . $search . '
        //     OR t.id LIKE ' . $search . '   OR it.sales_tax LIKE ' . $search . '
        //     OR p.customer_id LIKE ' . $search . '';
        // }

        $sql .= ' GROUP BY it.tax_code_id, it.sales_tax, it.invoice_id';

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $queryResults = DB::select(DB::raw(($sql)));

        $formattedResults = array();
        $totalTaxDue = 0;

        $i = -1;
        $temp = '';
        foreach ($queryResults as $result) {
            // $result->code = $result->code ? $result->code : 'Custom';
            // $formattedResults[$result->code][] = $result;

            if ($temp != $result->code) {
                $i++;
            }

            $formattedResults[$i]['tax_code'] = $result->code;
            $formattedResults[$i]['list'][] = $result;

            $temp = $formattedResults[$i]['tax_code'];
        }

        // return $formattedResults;

        $temp = '';
        $i = -1;
        foreach ($formattedResults as $codes) {
            $totalSales = 0;
            $totalTaxPayable = 0;
            $taxRate = 0;

            foreach ($codes['list'] as $invoice) {
                $totalSales += $invoice->revenue;
                $totalTaxPayable += $invoice->taxPayable;
                $totalTaxDue += $invoice->taxPayable;
                $taxRate = $invoice->salesTax;
            }

            if ($temp != $codes['tax_code']) {
                $i++;
            }

            $formattedResults[$i]['total'] = array(
                'totalSales' => $totalSales,
                'totalTaxPayable' => $totalTaxPayable,
                'averageTax' => ($taxRate * 100),
            );
            $temp = $codes['tax_code'];
        }

        // $results = $formattedResults;

        if ($request->download) {
            return Excel::download(new ExportTaxDetailReport($queryResults), 'ExportTaxDetailReport.csv');
        }

        return $this->sendResponse(
            true,
            __('strings.list', ['name' => 'Tax detail report']),
            [
                'data'      =>  $formattedResults,
                'count' => $count,
                'total_tax_due' =>  $totalTaxDue,
            ]
        );
    }
}
