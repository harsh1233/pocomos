<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosReportSummerTotalConfiguration;
use App\Models\Pocomos\PocomosReportSummerTotalConfigurationStatus;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use App\Exports\ExportMarketingReport;
use Excel;

class MarketingReportController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required'
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

        $pocomosSalesStatus = PocomosSalesStatus::whereActive(true)->whereOfficeId($officeId)->get(['id','name']);

        return $this->sendResponse(true, 'Marketing report filters', [
            'branches'      => $branches,
            'sales_status'  => $pocomosSalesStatus,
        ]);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'office_ids' => 'required|array',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
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

        $officeIds      = $this->convertArrayInStrings($request->office_ids);

        $salesStatusIds = $request->sales_status_ids ? implode(',', $request->sales_status_ids) : null;

        $customerStatuses = $request->customer_statuses ? implode(',', $request->customer_statuses) : null;

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $search = $request->search;

        $returnArray = $this->getTotalContractByMarketingType(
            $officeIds,
            $startDate,
            $endDate,
            $salesStatusIds,
            $customerStatuses,
            $page,
            $perPage,
            $search
        );

        $total_entries = $returnArray['count'];
        $returnArray = $returnArray['results'];

        $results = [];
        $totalAvgInitial = [];
        $totalAvgRecurring = [];
        $totalContractValue = [];
        $totalAvgContractValue = [];
        $data = [];
        $totals = [];
        $chartResults = [];
        $total_no_sales = [];
        $total_avg_initial = [];
        $total_avg_recurring = [];
        $total_contract_value = [];
        $total_avg_contract_value = [];

        if (!empty($returnArray)) {
            foreach ($returnArray as $mt) {
                $results[$mt->name] = $mt->sales;
                $totalAvgInitial[$mt->name] = $mt->initial_price;
                $totalAvgRecurring[$mt->name] = $mt->recurring_price;
                $totalContactValue[$mt->name] = $mt->contract_value;
                $totalAvgContractValue[$mt->name] = $mt->avg_contract_value;
            }
            $labels = array_keys($results);
            $data = array_values($results);
            $total_no_sales = array_sum(array_values($results));
            $total_avg_initial = array_sum(array_values($totalAvgInitial))/$total_entries;
            $total_avg_recurring = array_sum(array_values($totalAvgRecurring))/$total_entries;
            $total_contract_value = array_sum(array_values($totalContactValue))/$total_entries;
            $total_avg_contract_value = array_sum(array_values($totalAvgContractValue));

            foreach ($data as $new) {
                $backgroundColorArray[] = $this->randomColor();
            }

            $totals = array();
            $chartResults = array(
                "labels" => $labels,
                "datasets" =>
                    array(
                        "data" => $data,
                        "backgroundColor" => $backgroundColorArray
                    ),
            );
        }

        if ($request->download) {
            foreach ($returnArray as $q) {
                $i=0;
                foreach ($request->perc_sales as $ps) {
                    $returnArray[$i]->perc_sale = $ps;
                    $i++;
                }

                $i=0;
                foreach ($request->values as $val) {
                    $returnArray[$i]->value = $val;
                    $i++;
                }

                $i=0;
                foreach ($request->perc_values as $val) {
                    $returnArray[$i]->perc_value = $val;
                    $i++;
                }
            }

        //    return $returnArray;

            $excelData = [
                'results' => $returnArray,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            return Excel::download(new ExportMarketingReport($excelData), 'ExportMarketingReport.csv');
        }

        return $this->sendResponse(true, 'Marketing Report results', [
            // 'totals' => $totals,
            'chartResults' => $chartResults,
            'results' => $returnArray,
            'total_no_sales' => $total_no_sales,
            'total_no_sales_per' => '100',
            'all_total_avg_initial' =>  $total_avg_initial,
            'all_total_recurring_price' =>  $total_avg_recurring,
            'all_total_contract_value' =>  $total_contract_value,
            'all_total_avg_contract_value' =>  $total_avg_contract_value,
            'count' => $total_entries
        ]);
    }

    public function getTotalContractByMarketingType($officeIds, $startDate, $endDate, $salesStatusIds, $customerStatuses, $page, $perPage, $search)
    {
        $sql = "SELECT pmt.name, 
            COUNT(pi.id) as sales, 
            ROUND(AVG(pcc.initial_price), 2) as initial_price, 
            ROUND(AVG(pcc.recurring_price), 2) as recurring_price, 
            ROUND(AVG(pcc.modifiable_original_value),2) as contract_value, 
            COUNT(pi.id) * ROUND(AVG(pcc.modifiable_original_value),2) as avg_contract_value, 
            pcsp.office_id as office_id 
            FROM pocomos_contracts pc 
            JOIN pocomos_customer_sales_profiles pcsp on pc.profile_id = pcsp.id 
            JOIN pocomos_customers customer ON pcsp.customer_id = customer.id 
            JOIN pocomos_invoices pi on pc.id = pi.contract_id 
            JOIN pocomos_pest_contracts pcc ON pc.id = pcc.contract_id 
            JOIN pocomos_pest_agreements ppa on pcc.agreement_id = ppa.id 
            JOIN pocomos_marketing_types pmt on pc.found_by_type_id = pmt.id 
            LEFT JOIN pocomos_sales_status ss on pc.sales_status_id = ss.id ";

    //    return $officeIds;

        $sql .= " WHERE pcsp.office_id IN ($officeIds) ";

        $sql .= " AND pc.date_start BETWEEN '".$startDate."' AND '".$endDate."'";

        $sql .= " AND pc.status = 'active'";

        if ($customerStatuses) {
            $sql .= " AND customer.status IN (".$customerStatuses.")";
        }

        // return DB::raw($sql);

        if ($salesStatusIds) {
            // return 88;
            $sql .= 'AND ss.id IN ('.$salesStatusIds.')';
        }

        if ($search) {
            $sql .= ' AND (pmt.name LIKE "%'.$search.'%")';
        }

        $sql .= " GROUP BY pmt.name ORDER BY pmt.name ASC";

        $count = count(DB::select(DB::raw($sql)));
        $sql .= " LIMIT $perPage offset $page";

        $results = DB::select(DB::raw($sql));

        return array('results' => $results, 'count' => $count);
    }

    public function randomColor()
    {
        $hex = '#';
        //Create a loop.
        foreach (array('r', 'g', 'b') as $color) {
            //Random number between 0 and 255.
            $val = mt_rand(0, 255);
            //Convert the random number into a Hex value.
            $dechex = dechex($val);
            //Pad with a 0 if length is less than 2.
            if (strlen($dechex) < 2) {
                $dechex = "0" . $dechex;
            }
            //Concatenate
            $hex .= $dechex;
        }
        return $hex;
    }
}
