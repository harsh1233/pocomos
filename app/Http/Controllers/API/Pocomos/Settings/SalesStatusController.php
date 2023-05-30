<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosSalesStatus;
use Illuminate\Support\Facades\DB;

class SalesStatusController extends Controller
{
    use Functions;

    /**
     * API for list of Sales Status
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
            'status' => 'nullable|boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $status = $request->status ?? 1;
        $salesStatus = PocomosSalesStatus::where('office_id', $request->office_id)->where('active', $status);

        // if (isset($request->status)) {
        //     $salesStatus = $salesStatus->where('active', $request->status);
        // }

        if (isset($request->office_id)) {
            $salesStatus = $salesStatus->where('office_id', $request->office_id);
        }

        if ($request->search) {
            $search = $request->search;

            if (in_array($search, ['Yes', 'Paid', 'Serviced', 'yes', 'paid', 'serviced'])) {
                $search = 1;
            } elseif (in_array($search, ['No', ' Not Paid', 'Not Serviced', 'no', ' not Paid', 'not Serviced'])) {
                $search = 0;
            }

            $salesStatus = $salesStatus->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('description', 'like', '%' . $search . '%');
                $q->orWhere('default_status', 'like', '%' . $search . '%');
                $q->orWhere('paid', 'like', '%' . $search . '%');
                $q->orWhere('serviced', 'like', '%' . $search . '%');
                $q->orWhere('display_banner', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $salesStatus->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $salesStatus = $salesStatus->skip($perPage * ($page - 1))->take($perPage);
        }
        $salesStatus = $salesStatus->orderBy('position', 'asc')->get();

        $data = [
            'sales_status' => $salesStatus,
            'count' => $count
        ];
        return $this->sendResponse(true, __('strings.list', ['name' => 'Sales status']), $data);
    }

    /**
     * API for details of Sales Status
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosSalesStatus = PocomosSalesStatus::find($id);
        if (!$PocomosSalesStatus) {
            return $this->sendResponse(false, 'Sales Status Not Found');
        }
        return $this->sendResponse(true, 'Sales Status details.', $PocomosSalesStatus);
    }

    /**
     * API for create of Sales Status
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
            'description' => 'nullable',
            'paid' => 'required',
            'default_status' => 'required',
            'active' => 'nullable',
            'serviced' => 'required',
            'display_banner' => 'required',
            'apay' => 'boolean',
            'auto_update' => 'boolean',
            'default_initial_job_completed' => 'boolean',
            'default_initial_job_rescheduled' => 'boolean',
            'default_initial_job_cancelled' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosSalesStatus::whereName($request->name)->whereOfficeId($request->office_id)->where('active', 1)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'The name has already been taken']));
        }

        $query = PocomosSalesStatus::query();

        $areas = PocomosSalesStatus::where('office_id', $request->office_id)->where('active', 1)->get();

        if ($areas) {
            foreach ($areas as $pest) {
                $pest->update(['position' => $pest->position + 1]);
            }
        }

        if ($request->default_status == 1) {
            $query1 = "UPDATE  pocomos_sales_status set default_status=0 WHERE office_id = $request->office_id ";

            $serviceTypes = DB::select(DB::raw($query1));
        }

        if ($request->default_initial_job_cancelled == 1) {
            $query2 = "UPDATE  pocomos_sales_status set default_initial_job_cancelled=0 WHERE office_id = $request->office_id ";

            $serviceTypes = DB::select(DB::raw($query2));
        }
        if ($request->default_initial_job_completed == 1) {
            $query3 = "UPDATE  pocomos_sales_status set default_initial_job_completed=0 WHERE office_id = $request->office_id ";

            $serviceTypes = DB::select(DB::raw($query3));
        }
        if ($request->default_initial_job_rescheduled == 1) {
            $query4 = "UPDATE  pocomos_sales_status set default_initial_job_rescheduled=0 WHERE office_id = $request->office_id ";

            $serviceTypes = DB::select(DB::raw($query4));
        }
        $input = $request->only('office_id', 'name', 'paid', 'default_status', 'serviced', 'display_banner', 'apay', 'auto_update', 'default_initial_job_completed', 'default_initial_job_rescheduled', 'default_initial_job_cancelled') + ['active' => true, 'position' => 1];

        $input['description'] = $request->description ?? '';

        $PocomosSalesStatus =  (clone ($query))->create($input);


        /**End manage trail */
        return $this->sendResponse(true, 'Sales Status created successfully.', $PocomosSalesStatus);
    }

    /**
     * API for update of Sales Status
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'sales_status_id' => 'required|exists:pocomos_sales_status,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'description' => 'nullable',
            'paid' => 'required',
            'default_status' => 'required',
            'active' => 'nullable',
            'serviced' => 'required',
            'display_banner' => 'required',
            'apay' => 'boolean',
            'auto_update' => 'boolean',
            'default_initial_job_completed' => 'boolean',
            'default_initial_job_rescheduled' => 'boolean',
            'default_initial_job_cancelled' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosSalesStatus::whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->where('id', '!=', $request->sales_status_id)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'The name has already been taken']));
        }

        $PocomosSalesStatus = PocomosSalesStatus::where('id', $request->sales_status_id)->where('office_id', $request->office_id)->first();

        if (!$PocomosSalesStatus) {
            return $this->sendResponse(false, 'Sales Status not found.');
        }


        if ($request->default_status == 1) {
            $query1 = "UPDATE  pocomos_sales_status set default_status=0 WHERE office_id = $request->office_id ";

            $serviceTypes = DB::select(DB::raw($query1));
        }

        if ($request->default_initial_job_cancelled == 1) {
            $query2 = "UPDATE  pocomos_sales_status set default_initial_job_cancelled=0 WHERE office_id = $request->office_id ";

            $serviceTypes = DB::select(DB::raw($query2));
        }
        if ($request->default_initial_job_completed == 1) {
            $query3 = "UPDATE  pocomos_sales_status set default_initial_job_completed=0 WHERE office_id = $request->office_id ";

            $serviceTypes = DB::select(DB::raw($query3));
        }
        if ($request->default_initial_job_rescheduled == 1) {
            $query4 = "UPDATE  pocomos_sales_status set default_initial_job_rescheduled=0 WHERE office_id = $request->office_id ";

            $serviceTypes = DB::select(DB::raw($query4));
        }

        $input = $request->only('office_id', 'name', 'paid', 'default_status', 'active', 'serviced', 'display_banner', 'apay', 'auto_update', 'default_initial_job_completed', 'default_initial_job_rescheduled', 'default_initial_job_cancelled');

        $input['description'] = $request->description ?? '';

        $PocomosSalesStatus->update($input);

        return $this->sendResponse(true, 'Sales Status updated successfully.', $PocomosSalesStatus);
    }

    /**
     * API for delete of Sales Status
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        // return $request->office_id;

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosSalesStatus = PocomosSalesStatus::find($id);
        if (!$PocomosSalesStatus) {
            return $this->sendResponse(false, 'Sales Status not found.');
        }

        if (PocomosContract::where('sales_status_id', $id)->count()) {
            return $this->sendResponse(false, __('strings.message', ['message' => 'The sales status has exist on contracts.']));
        }

        $PocomosSalesStatus->active = false;
        $PocomosSalesStatus->save();

        $officeId = $request->office_id;

        $this->updatestatusPositions($officeId);

        return $this->sendResponse(true, 'Sales Status deleted successfully.');
    }

    public function updatestatusPositions($officeId)
    {
        // return $officeId;

        $query = "SELECT id FROM pocomos_sales_status WHERE office_id = $officeId AND active = 1 ORDER BY position";

        $pests = DB::select(DB::raw($query));

        $position = 1;
        foreach ($pests as $pest) {
            $sql = "UPDATE pocomos_sales_status SET position = $position WHERE office_id = $officeId AND id = $pest->id ";

            DB::select(DB::raw($sql));

            $position++;
        }
    }

    /**
     * API for reorder of Sales Status
     .
     *
     * @param  \Illuminate\Http\Request  $request, integer $id
     * @return \Illuminate\Http\Response
     */

    public function reorder(Request $request, $id)
    {
        $v = validator($request->all(), [
            'pos' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosSalesStatus = PocomosSalesStatus::where('id', $id)->where('office_id', $request->office_id)->first();
        if (!$PocomosSalesStatus) {
            return $this->sendResponse(false, 'Sales Status Not Found');
        }

        $is_reordered = false;
        $newPosition = $request->pos;
        $originalPosition = $PocomosSalesStatus->position;

        if ($newPosition === $originalPosition) {
            $is_reordered = true;
        }

        if (!$is_reordered) {
            $movedDown = $newPosition > $originalPosition;
            $videos = PocomosSalesStatus::where('active', true)->where('office_id', $request->office_id)->orderBy('position', 'asc')->get();
            foreach ($videos as $value) {
                $detail = PocomosSalesStatus::find($value->id);
                if ($value->id == $id) {
                    $position = $newPosition;
                } else {
                    $position = $detail->position;
                    if ($movedDown) {
                        if ($position > $originalPosition && $position <= $newPosition) {
                            $position--;
                        }
                    } elseif ($position <= $originalPosition && $position >= $newPosition) {
                        $position++;
                    }
                }
                $detail->position = $position;
                $detail->save();
            }
        }

        return $this->sendResponse(true, 'Sales Status reordered successfully.');
    }
}
