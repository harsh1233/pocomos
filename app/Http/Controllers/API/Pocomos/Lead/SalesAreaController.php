<?php

namespace App\Http\Controllers\API\Pocomos\Lead;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosLead;
use App\Models\Pocomos\PocomosTeam;
use App\Http\Controllers\Controller;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\PocomosSalesArea;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosSalesAreaPivotTeams;

class SalesAreaController extends Controller
{
    use Functions;

    /**
     * Creates a new salesArea entity.
     *
     * @param Request $request
     */
    public function mapAreasList(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $revenue = [];
        //Show the areas on the map while creating the new areas so to avoid overlap
        $sales_areas = PocomosSalesArea::with('teams_details.team', 'salespeople_details.sales_person.office_user_details.user_details', 'managers_details.manager.user_details')->whereOfficeId($request->office_id)->whereActive(true)->get();

        foreach ($sales_areas as $sales_area) {
            $revenue[$sales_area->id] = $this->getRevenueBySalesArea($sales_area);
        }
        $data = [
            'areas' => $sales_areas,
            'revenue' => $revenue,
            'location' => $this->getGeocodeByOffice($request->office_id),
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Sales Map Area']), $data);
    }

    /**
     * List sales areas
     *
     * @param Request $request
     */
    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $areas = PocomosSalesArea::query();

        if ($request->search) {
            $search = $request->search;

            $teamsIds = PocomosTeam::where('name', 'like', '%' . $search . '%')->pluck('id')->toArray();
            $teamAreasIds = PocomosSalesAreaPivotTeams::whereIn('team_id', $teamsIds)->pluck('sales_area_id')->toArray();

            $managerAreaIdsTmp = DB::select(DB::raw("SELECT psm.sales_area_id
            FROM pocomos_sales_area_pivot_manager AS psm
            JOIN pocomos_company_office_users AS pou ON psm.office_user_id = pou.id
            JOIN orkestra_users AS ou ON pou.user_id = ou.id
            WHERE (ou.first_name like '%$search%' OR ou.last_name like '%$search%')"));

            $managerAreaIds = array_map(function ($value) {
                return $value->sales_area_id;
            }, $managerAreaIdsTmp);

            $salesPerAreaIdsTmp = DB::select(DB::raw("SELECT saps.sales_area_id
            FROM pocomos_sales_area_pivot_salesperson AS saps
            JOIN pocomos_salespeople AS psp ON saps.salesperson_id = psp.id
            JOIN pocomos_company_office_users AS pcou ON psp.user_id = pcou.id
            JOIN orkestra_users AS ou ON pcou.user_id = ou.id
            WHERE (ou.first_name like '%$search%' OR ou.last_name like '%$search%')"));

            $salesPerAreaIds = array_map(function ($value) {
                return $value->sales_area_id;
            }, $salesPerAreaIdsTmp);

            $areaIds = array_unique(array_merge($teamAreasIds, $managerAreaIds, $salesPerAreaIds));

            $userIds = OrkestraUser::where('first_name', 'like', '%' . $search . '%')
                ->orWhere('last_name', 'like', '%' . $search . '%')
                ->pluck('id')->toArray();

            $areas = $areas->whereIn('id', $areaIds);

            if (PocomosSalesArea::whereIn('created_by', $userIds)->count()) {
                $areas = $areas->orWhereIn('created_by', $userIds);
            }

            $areas = $areas->orWhere('name', 'like', '%' . $search . '%')
                ->orWhere('date_created', 'like', '%' . $search . '%');
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $areas->count();
        $areas->skip($perPage * ($page - 1))->take($perPage);

        $areas = $areas->with('teams_details.team', 'salespeople_details.sales_person.office_user_details.user_details', 'managers_details.manager.user_details', 'created_by_user')->whereOfficeId($request->office_id)->whereActive(true)->orderBy('id', 'desc')->orderBy('id', 'desc')->get();

        $data = [
            'lead' => $areas,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Sales Area']), $data);
    }

    /**
     * List sales areas
     *
     * @param Request $request
     */
    public function delete(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sales_area = PocomosSalesArea::whereOfficeId($request->office_id)->whereActive(true)->findOrFail($id);

        $office_user = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->first();

        $can_edit = $this->canSalesAreaEdit($office_user, $sales_area);

        if ($can_edit || $this->isGranted('ROLE_OWNER')) {
            $sales_area->active = false;
            $sales_area->enabled = false;
            $sales_area->save();

            PocomosLead::where('sales_area_id', $id)->update(['sales_area_id' => null]);
        }
        return $this->sendResponse(true, __('strings.delete', ['name' => 'Sales Area']));
    }
}
