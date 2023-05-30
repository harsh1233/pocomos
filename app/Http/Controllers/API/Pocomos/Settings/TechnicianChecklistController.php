<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosTechnicianLicenses;
use App\Models\Pocomos\PocomosTechnicianChecklist;
use App\Models\Pocomos\PocomosTechnicianChecklistConfiguration;

class TechnicianChecklistController extends Controller
{
    use Functions;

    /**
     * API for list of Technician
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $technicianChecklists = PocomosTechnicianChecklist::where('office_id', $request->office_id)->whereDeleted(0);

        if ($request->search) {
            $search = $request->search;

            // if ($search == 'Yes' || $search == 'yes') {
            //     $search = 1;
            // } elseif ($search == 'No' || $search == 'no') {
            //     $search = 0;
            // }

            $status = 10;
            if (stripos('yes', $request->search)  !== false) {
                $status = 1;
            } elseif (stripos('no', $request->search) !== false) {
                $status = 0;
            }

            $technicianChecklists = $technicianChecklists->where(function ($q) use ($search, $status) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('active', 'like', '%' . $status . '%');
                $q->orWhere('position', $search);
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $technicianChecklists->count();
        $allIds = $technicianChecklists->orderBy('position', 'asc')->pluck('id');
        $technicianChecklists = $technicianChecklists->skip($perPage * ($page - 1))->take($perPage)->orderBy('position', 'asc')->get();

        $data = [
            'technician_checklists' => $technicianChecklists,
            'all_ids' => $allIds,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Technician checklists']), $data);
    }

    /**
     * API for details of Technician
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosTechnicianChecklist = PocomosTechnicianChecklist::find($id);
        if (!$PocomosTechnicianChecklist) {
            return $this->sendResponse(false, 'Technician Not Found');
        }
        return $this->sendResponse(true, 'Technician details.', $PocomosTechnicianChecklist);
    }

    /**
     * API for create of Technician
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'active' => 'boolean'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = PocomosTechnicianChecklist::query();

        $officeId = $request->office_id;

        if (PocomosTechnicianChecklist::whereName($request->name)->where('deleted', 0)->whereOfficeId($request->office_id)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'The name has already been taken']));
        }

        $pests = PocomosTechnicianChecklist::where('office_id', $request->office_id)->where('deleted', 0)->get();

        if ($pests) {
            foreach ($pests as $pest) {
                $pest->update(['position' => $pest->position + 1]);
            }
        }

        $input = $request->only('office_id', 'name') + ['active' => true, 'position' => 1];

        $PocomosPest =  (clone ($query))->create($input);

        /**End manage trail */
        return $this->sendResponse(true, 'Technician created successfully.', $PocomosPest);
    }

    /**
     * API for update of Technician
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'technician_id' => 'required|exists:pocomos_technician_checklist,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'deleted' => 'nullable',
            'position' => 'nullable',
            'active' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosTechnicianChecklist::whereName($request->name)->whereOfficeId($request->office_id)->where('id', '!=', $request->technician_id)->where('deleted', 0)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'The name has already been taken']));
        }

        $PocomosTechnicianChecklist = PocomosTechnicianChecklist::where('id', $request->technician_id)->where('office_id', $request->office_id)->first();

        if (!$PocomosTechnicianChecklist) {
            return $this->sendResponse(false, 'Technician not found.');
        }

        $PocomosTechnicianChecklist->update(
            $request->only('office_id', 'name', 'deleted', 'position', 'active')
        );

        return $this->sendResponse(true, 'Technician updated successfully.', $PocomosTechnicianChecklist);
    }

    /**
     * API for delete of Technician
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $PocomosTechnicianChecklist = PocomosTechnicianChecklist::find($id);
        if (!$PocomosTechnicianChecklist) {
            return $this->sendResponse(false, 'Technician not found.');
        }

        $PocomosTechnicianChecklist->deleted = 1;
        $PocomosTechnicianChecklist->save();

        // update positions
        $query = "SELECT id FROM pocomos_technician_checklist WHERE office_id = $officeId AND deleted = 0 ORDER BY position";

        $techChecklists = DB::select(DB::raw($query));

        if ($techChecklists) {
            $position = 1;
            foreach ($techChecklists as $tech) {
                $sql = "UPDATE pocomos_technician_checklist SET position = $position WHERE office_id = $officeId AND id = $tech->id ";

                DB::select(DB::raw($sql));

                $position++;
            }
        }

        return $this->sendResponse(true, 'Technician deleted successfully.');
    }

    /**
     * API for update Check list
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function updateChecklist(Request $request)
    {
        $v = validator($request->all(), [
            'enable_checklist' => 'boolean',
            'enable_required' => 'boolean',
            'active' => 'boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosTechnicianChecklist = PocomosTechnicianChecklistConfiguration::where('office_id', $request->office_id)->first();

        if (!$PocomosTechnicianChecklist) {
            $input_details = $request->only('enable_checklist', 'enable_required', 'active');
            $input_details['office_id'] = $request->office_id;
            $PocomosTechnicianChecklist =  PocomosTechnicianChecklistConfiguration::create($input_details);

            return $this->sendResponse(true, 'Checklist Config Updated', $PocomosTechnicianChecklist);
        }

        $PocomosTechnicianChecklist->update(
            $request->only('enable_checklist', 'enable_required', 'active')
        );

        return $this->sendResponse(true, 'Checklist Config Updated', $PocomosTechnicianChecklist);
    }

    public function reorder(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $technicians = $request->technicians;

        $i = 1;
        foreach ($technicians as $technician) {
            $res = DB::select(DB::raw("UPDATE pocomos_technician_checklist SET position = $i
                        WHERE office_id = $request->office_id AND id = $technician"));
            $i++;
        }
    }

    /**Get technician checklist configuration */
    public function getTechnicianChecklistConfiguration(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $config = PocomosTechnicianChecklistConfiguration::where('office_id', $request->office_id)->firstOrFail();
        return $this->sendResponse(true, __('strings.details', ['name' => 'Technician checklist configuration']), $config);
    }

    /**Get technician checklist configuration */
    public function getTechnicianLicensesDetails(Request $request)
    {
        $v = validator($request->all(), [
            'technician_id' => 'required|exists:orkestra_users,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $licenses = array();

        $officeUser = PocomosCompanyOfficeUser::whereUserId($request->technician_id)->first();
        if ($officeUser) {
            $technician = PocomosTechnician::with('licenses.service_type')->whereUserId($officeUser->id)->firstOrFail();
        }

        return $this->sendResponse(true, __('strings.details', ['name' => 'Technician licenses']), $technician);
    }
}
