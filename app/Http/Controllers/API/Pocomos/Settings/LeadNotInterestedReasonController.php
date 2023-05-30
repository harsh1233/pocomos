<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosLeadNotInterestedReason;

class LeadNotInterestedReasonController extends Controller
{
    use Functions;

    /**
     * API for list of Lead Not Interested Reason
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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

        $leadNotInterestedReason = PocomosLeadNotInterestedReason::where('active', true)->where('office_id', $request->office_id);

        if ($request->search) {
            $search = $request->search;

            $leadNotInterestedReason = $leadNotInterestedReason->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $leadNotInterestedReason->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $leadNotInterestedReason = $leadNotInterestedReason->skip($perPage * ($page - 1))->take($perPage);
        }
        $leadNotInterestedReason = $leadNotInterestedReason->orderBy('id', 'desc')->get();

        $data = [
            'lead_not_interested_reason' => $leadNotInterestedReason,
            'count' => $count
        ];
        return $this->sendResponse(true, __('strings.list', ['name' => 'Lead not interested reasons']), $data);
    }

    /**
     * API for details of Lead Not Interested Reason
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosLeadNotInterestedReason = PocomosLeadNotInterestedReason::find($id);
        if (!$PocomosLeadNotInterestedReason) {
            return $this->sendResponse(false, 'Lead not interested reason Not Found');
        }
        return $this->sendResponse(true, 'Lead Not Interested Reason details.', $PocomosLeadNotInterestedReason);
    }

    /**
     * API for create of Lead Not Interested Reason
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'description' => 'required',
            'name' => 'required',
            'active' => 'nullable',
            'enabled' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosLeadNotInterestedReason::whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'This Lead Not Interested Reasons already exists.']));
        }

        $input_details = $request->only('office_id', 'name', 'description', 'active', 'enabled');

        $PocomosLeadNotInterestedReason =  PocomosLeadNotInterestedReason::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Lead Not Interested Reason created successfully.', $PocomosLeadNotInterestedReason);
    }

    /**
     * API for update of Lead Not Interested Reason
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'reason_id' => 'required|exists:pocomos_lead_not_interested_reasons,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'description' => 'required',
            'name' => 'required',
            'active' => 'nullable',
            'enabled' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosLeadNotInterestedReason::whereName($request->name)->whereOfficeId($request->office_id)->where('id', '!=', $request->reason_id)->where('active', 1)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'This Lead Not Interested Reasons already exists.']));
        }

        $PocomosLeadNotInterestedReason = PocomosLeadNotInterestedReason::where('id', $request->reason_id)->where('office_id', $request->office_id)->first();

        if (!$PocomosLeadNotInterestedReason) {
            return $this->sendResponse(false, 'Lead not interested reason not found.');
        }

        $PocomosLeadNotInterestedReason->update(
            $request->only('office_id', 'name', 'description', 'active', 'enabled')
        );

        return $this->sendResponse(true, 'Lead Not Interested Reason updated successfully.', $PocomosLeadNotInterestedReason);
    }

    /* API for changeStatus of  Reason */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'reason_id' => 'required|integer|min:1',
            'enabled' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLeadNotInterestedReason = PocomosLeadNotInterestedReason::find($request->reason_id);
        if (!$PocomosLeadNotInterestedReason) {
            return $this->sendResponse(false, 'Reason not found');
        }

        $PocomosLeadNotInterestedReason->update([
            'enabled' => $request->enabled
        ]);

        return $this->sendResponse(true, 'Lead not interested reason status changed successfully.');
    }
}
