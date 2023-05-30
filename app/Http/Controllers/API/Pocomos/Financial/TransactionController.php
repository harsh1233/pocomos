<?php

namespace App\Http\Controllers\API\Pocomos\Financial;

use DB;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosTeam;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosService;
use App\Jobs\TransactionReportExportJob;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class TransactionController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            // 'office_ids' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id','list_name']);

        $branchId = $request->branch_id;

        $teams = PocomosTeam::with('member_details')
                        // ->leftJoin('pocomos_memberships as pm','pocomos_teams.id','pm.team_id')
                        // ->leftJoin('pocomos_salespeople as ps', 'pm.salesperson_id', 'ps.id')
                        ->whereOfficeId($branchId)->where('pocomos_teams.active', 1)
                        ->orderBy('name')
                        ->get();

        $marketingTypes = PocomosMarketingType::whereOfficeId($branchId)->whereActive(1)->get();

        $serviceTypes = PocomosService::whereOfficeId($branchId)->whereActive(1)->get();

        $tags = PocomosTag::whereOfficeId($branchId)->whereActive(1)->get();

        return $this->sendResponse(true, 'Transactions filters', [
            'branches'        => $branches,
            'teams'           => $teams,
            'marketing_types' => $marketingTypes,
            'service_types'   => $serviceTypes,
            'tags'            => $tags,
        ]);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'branch_id' => 'required'
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

        $branchId = $request->branch_id;

        $tagsArr = $request->tags ?: [];

        $marketingTypesArr = $request->marketing_types ?: [];

        $serviceTypesArr = $request->service_types ?: [];

        $salespeopleArr = $request->salespeople ?: [];

        $networkType = $request->network_type ?: null;

        $transactionType = $request->transaction_type ?: null;

        $transactionStatus = $request->transaction_status ?: null;

        $sql = "SELECT customer.id AS customer_id,
                            customer.external_account_id AS external_account_id,
                            CONCAT(customer.first_name,' ',customer.last_name) AS customer_name,
                            DATE_FORMAT(ot.date_created,'%c/%d/%Y') AS transaction_date_created,
                            ot.network AS network,
                            ot.type AS type,
                            IF(ot.network = 'Points', ot.amount/100,ot.amount) AS amount,
                            o3.last_four AS last_four,
                            ot.status AS transaction_status,
                            results.message AS result_message,
                            COALESCE(CONCAT(ou.first_name,' ',ou.last_name),'Autopay') AS initiator,
                            results.external_id AS result_external_id
                            FROM pocomos_invoice_transactions pit
                            JOIN orkestra_transactions ot ON pit.transaction_id = ot.id
                            JOIN orkestra_results results ON ot.id = results.transaction_id
                            JOIN orkestra_accounts o3 ON ot.account_id = o3.id
                            JOIN pocomos_invoices i ON pit.invoice_id = i.id
                            JOIN pocomos_contracts contract ON i.contract_id = contract.id
                            JOIN pocomos_pest_contracts contract2 ON contract.id = contract2.contract_id
                            JOIN pocomos_customer_sales_profiles pcsp ON contract.profile_id = pcsp.id
                            JOIN pocomos_customers customer ON pcsp.customer_id = customer.id
                            JOIN pocomos_marketing_types t2 ON contract.found_by_type_id = t2.id 
                            LEFT JOIN pocomos_user_transactions put ON ot.id = put.transaction_id
                            LEFT JOIN orkestra_users ou ON put.user_id = ou.id
                            ";

        if (count($tagsArr) > 0) {
            $sql .= ' JOIN pocomos_pest_contracts_tags ppct ON contract2.id = ppct.contract_id';
        }

        $sql .= ' WHERE pcsp.office_id = '.$branchId.' AND ot.date_created BETWEEN "'.$startDate.'" AND "'.$endDate.'" ';

        if (count($tagsArr) > 0) {
            $tags = implode(',', $tagsArr);
            $sql .= 'AND ppct.tag_id IN ('.$tags.') ';
        }

        if (count($marketingTypesArr) > 0) {
            $marketingTypes = implode(',', $marketingTypesArr);
            $sql .= 'AND contract.found_by_type_id IN ('.$marketingTypes.') ';
        }

        if (count($serviceTypesArr) > 0) {
            $serviceTypes = implode(',', $serviceTypesArr);
            $sql .= 'AND contract2.service_type_id IN ('.$serviceTypes.') ';
        }

        // salesperson_id
        if (count($salespeopleArr) > 0) {
            $salespeople = implode(',', $salespeopleArr);
            $sql .= 'AND contract.salesperson_id IN ('.$salespeople.') ';
        }

        if ($networkType != null) {
            $sql .= 'AND ot.network = "'.$networkType.'" ';
        }

        if ($transactionStatus != null) {
            $sql .= 'AND ot.status = "'.$transactionStatus.'" ';
        }

        if ($transactionType != null) {
            $sql .= 'AND ot.type = "'.$transactionType.'" ';
        }


        if ($request->search) {
            $search = '"%'.$request->search.'%"';
            
            $sql .= " AND (customer.external_account_id LIKE $search
                    OR CONCAT(customer.first_name,' ',customer.last_name) LIKE $search 
                    OR DATE_FORMAT(ot.date_created,'%c/%d/%Y') LIKE $search 
                    OR ot.network LIKE $search 
                    OR ot.type LIKE $search 
                    OR IF(ot.network = 'Points', ot.amount/100,ot.amount) LIKE $search 
                    OR o3.last_four LIKE $search 
                    OR ot.status LIKE $search 
                    OR results.message LIKE $search
                    OR COALESCE(CONCAT(ou.first_name,' ',ou.last_name),'Autopay') LIKE $search
                    OR results.external_id LIKE $search
                    )";
        }

        $sql .= ' GROUP BY ot.id';


        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));
        /**If result data are from DB::row query then `true` else `false` normal laravel get listing */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $result = DB::select(DB::raw($sql));

        if ($request->download) {
            // return Excel::download(new ExportFinancialTransaction($result), 'ExportFinancialTransaction.csv');

            TransactionReportExportJob::dispatch($result);
            return $this->sendResponse(true, "Transactions Report export job has started. You will find the download link on your message board when it's complete. This could take a few minutes.");
        }

        return $this->sendResponse(true, 'Transactions result', [
            'result' => $result,
            'count' => $count,
        ]);
    }
}
