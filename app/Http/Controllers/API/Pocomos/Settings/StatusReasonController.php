<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosStatusReason;

class StatusReasonController extends Controller
{
    use Functions;

    /**
     * API for list of Cancel Reason
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

        $statusReason = PocomosStatusReason::where('active', 1)->where('office_id', $request->office_id);

        if ($request->search) {
            $search = $request->search;

            $statusReason = $statusReason->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $statusReason->count();
        if($request->page && $request->perPage){
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $statusReason = $statusReason->skip($perPage * ($page - 1))->take($perPage);
        }
        $statusReason = $statusReason->orderBy('id', 'desc')->get();

        $data = [
            'cancel_reasons' => $statusReason,
            'count' => $count
        ];
        return $this->sendResponse(true, __('strings.list', ['name' => 'Cancel reasons']), $data);
    }

    /**
     * API for details of Cancel Reason
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosStatusReason = PocomosStatusReason::find($id);
        if (!$PocomosStatusReason) {
            return $this->sendResponse(false, 'Cancel Reason Not Found');
        }
        return $this->sendResponse(true, 'Cancel Reason details.', $PocomosStatusReason);
    }

    /**
     * API for create of Cancel Reason
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
            'active' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosStatusReason::whereName($request->name)->whereOfficeId($request->office_id)->where('active', 1)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'The name has already been taken']));
        }

        $input = $request->only('office_id', 'name') + ['active' => true];

        $input['description'] = $request->description ?? '';

        $PocomosStatusReason =  PocomosStatusReason::create($input);


        /**End manage trail */
        return $this->sendResponse(true, 'Cancel Reason created successfully.', $PocomosStatusReason);
    }

    /**
     * API for update of Cancel Reason
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'status_reason_id' => 'required|exists:pocomos_status_reasons,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'description' => 'nullable',
            'active' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosStatusReason::whereName($request->name)->whereOfficeId($request->office_id)->where('id', '!=', $request->status_reason_id)->where('active', 1)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'The name has already been taken']));
        }

        $PocomosStatusReason = PocomosStatusReason::where('id', $request->status_reason_id)->where('office_id', $request->office_id)->first();

        if (!$PocomosStatusReason) {
            return $this->sendResponse(false, 'Cancel Reason not found.');
        }


        $input = $request->only('office_id', 'name');

        $input['description'] = $request->description ?? '';

        $input['active'] = 1;

        $PocomosStatusReason->update($input);

        return $this->sendResponse(true, 'Cancel Reason updated successfully.', $PocomosStatusReason);
    }

    /**
     * API for delete of Cancel Reason
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosStatusReason = PocomosStatusReason::find($id);
        if (!$PocomosStatusReason) {
            return $this->sendResponse(false, 'Cancel Reason not found.');
        }

        $PocomosStatusReason->active = false;
        $PocomosStatusReason->save();

        return $this->sendResponse(true, 'Cancel Reason deleted successfully.');
    }
}
