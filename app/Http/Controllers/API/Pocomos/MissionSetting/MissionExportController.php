<?php

namespace App\Http\Controllers\API\Pocomos\MissionSetting;

use App\Models\Pocomos\PocomosMissionExportContract;
use App\Models\Pocomos\PocomosMissionConfig;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class MissionExportController extends Controller
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

        $companyOffices = PocomosCompanyOffice::select('*', 'pocomos_company_offices.id')
            ->join('pocomos_mission_config as pmc', 'pocomos_company_offices.id', 'pmc.office_id')
            ->where('pocomos_company_offices.active', 1)
            ->where('pmc.active', 1)
            ->where('pmc.enabled', 1)
            ->where('pocomos_company_offices.id', $request->office_id)->get();


        /*
        for Environment :

        All = null
        'Test' => 1,
        'Production' => 0,
        */

        return $this->sendResponse(true, 'Mission export filters.', [
            'offices' => $companyOffices,
        ]);
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

        $sql = "SELECT mec.id,
                 cu.id AS customer_id,
                 CONCAT(cu.first_name, ' ', cu.last_name) as customer,
                 o.name AS office,
                 a.name AS contract,
                 mec.status,
                 mec.test_env,
                 mec.errors
                FROM pocomos_mission_export_contract mec
                LEFT JOIN pocomos_company_offices o ON mec.office_id = o.id
                LEFT JOIN pocomos_company_offices po ON o.parent_id = po.id
                LEFT JOIN pocomos_mission_config ppc ON (o.id = ppc.office_id)
                LEFT JOIN pocomos_pest_contracts pc ON mec.pest_contract_id = pc.id
                LEFT JOIN pocomos_pest_agreements pa ON pc.agreement_id = pa.id
                LEFT JOIN pocomos_agreements a ON pa.agreement_id = a.id
               LEFT JOIN pocomos_customers AS cu ON mec.customer_id = cu.id
               WHERE ((o.active = 1 AND o.parent_id IS NULL) OR (o.active = 1 AND po.active = 1))
                   AND mec.active = 1 AND ppc.enabled = 1
            ";


        $offices = $request->office_ids ?: [];
        if (count($offices)) {
            $offices = implode(',', $offices);
            $sql .= ' AND o.id IN ('.$offices.')';
        }

        if (is_numeric($request->env)) {
            $sql .= ' AND mec.test_env = '.$request->env.'';
        }

        // if(count($request->statuses)) {
        //     $statuses = implode(',',$request->statuses);
        //     $sql .= ' AND pec.status IN ('.$statuses.')';
        // }

        if ($request->search) {
            $searchTerm = '"%' . $request->search . '%"';

            $sql .= ' AND (
                CONCAT(cu.first_name, \' \', cu.last_name) LIKE ' . $searchTerm . '
                OR mec.id LIKE ' . $searchTerm . '
                OR a.name LIKE ' . $searchTerm . '
                OR mec.status LIKE ' . $searchTerm . '
            )';
        }

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $list = DB::select(DB::raw(($sql)));

        /*
            -show errors btn only if its not null
            -if status is other than Pending/Paused than show 2 btns
        */

        return $this->sendResponse(true, 'Mission Export List', [
            'List' => $list,
            'count' => $count,
        ]);
    }

    public function show($id)
    {
        $missionExportContract = PocomosMissionExportContract::with([
            'customer.contact_address.primaryPhone',
            'pestContract.service_type_details',
            'pestContract.contract_details',
        ])->findOrFail($id);

        /*
            contract = (contract name) - service_type_details->name - contract_details->status
            Date Export Attempted = date_modified
          */

        return $this->sendResponse(true, __('strings.details', ['name' => 'Mission Export Contract']), $missionExportContract);
    }

    public function changeMissionExportContractStatus(Request $request, $id)
    {
        $v = validator($request->all(), [
            'status' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $status = $request->status;

        $missionExportContract = PocomosMissionExportContract::findOrFail($id);

        if (!in_array($status, array('Pending', 'Success', 'Failed', 'Paused'))) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find ' . $status . ' Mission Export Contract status.']));
        }

        $missionExportContract->status = $status;
        $missionExportContract->save();

        /*
            Reschedule = Pending
        */
        return $this->sendResponse(true, __('strings.update', ['name' => 'Mission export contract status']));
    }

    public function tryExporting(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $status = $request->status;

        $missionConfig = PocomosMissionConfig::whereOfficeId($request->office_id)->whereActive(1)->whereEnabled(1)->first();

        $missionExportContract = PocomosMissionExportContract::whereOfficeId($request->office_id)->findOrFail($id);

        if ($missionConfig->test_env == 1) {
            // $baseUri = $this->missionParameters['testUrl'];
        } else {
            // $baseUri = $this->missionParameters['prodUrl'];
        }


        $missionExportContract->test_env = $status;
        // $missionExportContract->fileId_in_mission = $fileId_in_mission;
        // $missionExportContract->etag_in_mission = $etag_in_mission;
        $missionExportContract->status = $status;
        $missionExportContract->errors = '';
        $missionExportContract->save();


        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Customer exported']));
    }
}
