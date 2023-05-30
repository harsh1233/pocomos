<?php

namespace App\Http\Controllers\API\Pocomos\PestRoutes;

use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosPestRoutesConfig;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Http\Controllers\Functions;
use Illuminate\Http\Request;
use DB;

class ExportContractsController extends Controller
{
    use Functions;


    /**
     * API for Pest Route Export contracts
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function get(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestRoute = PocomosCompanyOffice::join(
            'pocomos_pest_routes_config as pprc',
            'pocomos_company_offices.id',
            'pprc.office_id'
        )
                        ->where('pocomos_company_offices.active', 1)
                        ->where('pprc.active', 1)
                        ->where('pprc.enabled', 1)
                        ->where('pocomos_company_offices.id', $request->office_id)
                        ->get();

        return $this->sendResponse(true, __('strings.list', ['name' => 'Branches']), $PocomosPestRoute);
    }

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $offices = $request->offices;

        $sql = "SELECT mec.id, 
                 cu.id AS customer_id,
                 CONCAT(cu.first_name, ' ', cu.last_name) as customer,
                 o.name AS office,
                 a.name AS contract,
                 mec.status,
                 mec.errors
                FROM pocomos_pest_routes_export_contract mec 
                INNER JOIN pocomos_company_offices o ON mec.office_id = o.id 
                LEFT JOIN pocomos_company_offices po ON o.parent_id = po.id 
                INNER JOIN pocomos_pest_routes_config ppc ON (o.id = ppc.office_id) 
                INNER JOIN pocomos_pest_contracts pc ON mec.pest_contract_id = pc.id 
                INNER JOIN pocomos_pest_agreements pa ON pc.agreement_id = pa.id 
                INNER JOIN pocomos_agreements a ON pa.agreement_id = a.id 
                JOIN pocomos_customers AS cu ON mec.customer_id = cu.id
                WHERE ((o.active = 1 AND o.parent_id IS NULL) OR (o.active = 1 AND po.active = 1)) 
                    AND mec.active = 1 AND ppc.enabled = 1
            ";

        if (count($offices)) {
            $offices = implode(',', $offices);
            $sql .= ' AND o.id IN ('.$offices.')';
        }

        if ($request->search) {
            $search = '"%'.$request->search.'%"';

            $sql .= ' AND (
                CONCAT(cu.first_name, \' \', cu.last_name) LIKE '.$search.' 
                OR o.name LIKE '.$search.' 
                OR a.name LIKE '.$search.' 
                OR mec.status LIKE '.$search.' 
            )';
        }

        $count = count(DB::select(DB::raw($sql)));

        // if(count($statuses)) {
        //     $offices = implode(',',$statuses);

        //     $sql .= ' AND mec.status IN ('.$statuses.')';
        // }

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $exportContracts = DB::select(DB::raw($sql));

        return $this->sendResponse(true, __('strings.list', ['name' => 'Pest Routes Configurations']), [
            'export_contracts' => $exportContracts,
            'count' => $count
        ]);
    }
}
