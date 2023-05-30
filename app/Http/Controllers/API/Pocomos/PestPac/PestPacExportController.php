<?php

namespace App\Http\Controllers\API\Pocomos\PestPac;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosPestpacConfig;
use App\Models\Pocomos\PocomosPestpacSetting;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosPestpacServiceType;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosPestContractServiceType;
use DB;
use App\Models\Pocomos\PocomosTimezone;

class PestPacExportController extends Controller
{
    use Functions;

    public function getOffices(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $companyOffices = PocomosCompanyOffice::select('*', 'pocomos_company_offices.id')
            ->join('pocomos_pestpac_config as ppc', 'pocomos_company_offices.id', 'ppc.office_id')
            ->where('pocomos_company_offices.active', 1)
            ->where('ppc.active', 1)
            ->where('ppc.enabled', 1)
            ->where('pocomos_company_offices.id', $request->office_id)->get();

        if (!$companyOffices) {
            return $this->sendResponse(false, 'Company Offices not found.');
        }

        return $this->sendResponse(true, 'Offices', $companyOffices);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'office_ids' => 'nullable',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pestPacConfig = PocomosPestpacConfig::whereOfficeId($request->office_id)->whereActive(true)->firstOrFail();

        if ($pestPacConfig && $pestPacConfig->enabled == 0) {
            return $this->sendResponse(false, "Pestpac isn't enabled for this office");
        }

        $sql = "SELECT pec.id,
                 cu.id AS customer_id,
                 CONCAT(cu.first_name, ' ', cu.last_name) as customer,
                 o.name AS office,
                 a.name AS contract,
                 pec.location_id,
                 pec.bill_to_id,
                 pec.service_order_id,
                 pec.contract_file_id,
                 pec.contract_file_uploaded,
                 pec.service_setup_id,
                 pec.card_token,
                 pec.card_brand,
                 pec.card_id,
                 pec.status,
                 pec.errors
                FROM pocomos_pestpac_export_customers pec
                INNER JOIN pocomos_company_offices o ON pec.office_id = o.id
                LEFT JOIN pocomos_company_offices po ON o.parent_id = po.id
                INNER JOIN pocomos_pestpac_config ppc ON (o.id = ppc.office_id)
                INNER JOIN pocomos_pest_contracts pc ON pec.pest_contract_id = pc.id
                INNER JOIN pocomos_pest_agreements pa ON pc.agreement_id = pa.id
                INNER JOIN pocomos_agreements a ON pa.agreement_id = a.id
                JOIN pocomos_customers AS cu ON pec.customer_id = cu.id
                WHERE ((o.active = 1 AND o.parent_id IS NULL) OR (o.active = 1 AND po.active = 1))
                    AND pec.active = 1 AND ppc.enabled = 1
            ";


        $offices = $request->office_ids ?: [];
        if (count($offices)) {
            $offices = implode(',', $offices);
            $sql .= ' AND o.id IN (' . $offices . ')';
        }

        if ($request->search) {
            $searchTerm = '"%' . $request->search . '%"';

            $sql .= ' AND (
                CONCAT(cu.first_name, \' \', cu.last_name) LIKE ' . $searchTerm . '
                OR o.name LIKE ' . $searchTerm . '
                OR a.name LIKE ' . $searchTerm . '
                OR pec.status LIKE ' . $searchTerm . '
            )';
        }

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $list = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'Queued customers for Pestpac Export', [
            'list' => $list,
            'count' => $count,
        ]);
    }
}
