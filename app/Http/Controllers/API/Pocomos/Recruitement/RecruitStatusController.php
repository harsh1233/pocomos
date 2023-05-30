<?php

namespace App\Http\Controllers\API\Pocomos\Recruitement;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\Recruitement\OfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosRecruitStatus;
use App\Models\Pocomos\PocomosRecruitingOfficeConfiguration;

class RecruitStatusController extends Controller
{
    use Functions;

    /**
     * API for list of Recruiting Status
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruitStatus = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)->first();

        if ($PocomosRecruitStatus) {
            $PocomosRecruitStatus = PocomosRecruitStatus::where('active', 1)->where('recruiting_office_configuration_id', $PocomosRecruitStatus->id)->orderBy('id', 'desc');
        } else {
            $PocomosRecruitStatus = PocomosRecruitStatus::where('active', 1)->orderBy('id', 'desc');
        }

        if ($request->search) {
            $search = $request->search;
            $PocomosRecruitStatus->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $PocomosRecruitStatus->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosRecruitStatus->skip($perPage * ($page - 1))->take($perPage);
        }

        $PocomosRecruitStatus = $PocomosRecruitStatus->get();

        return $this->sendResponse(true, 'List', [
            'PocomosRecruitStatus' => $PocomosRecruitStatus,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Recruiting Status
     .
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosRecruitStatus = PocomosRecruitStatus::find($id);
        if (!$PocomosRecruitStatus) {
            return $this->sendResponse(false, 'Recruit Status Not Found');
        }
        return $this->sendResponse(true, 'Recruit Status details.', $PocomosRecruitStatus);
    }

    /**
     * API for create of Recruiting Status
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'description' => 'nullable',
            'default_status' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruitStatus = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)->first();

        $input_details = $request->only('default_status', 'name', 'description');

        if ($PocomosRecruitStatus) {
            $input_details['recruiting_office_configuration_id'] =  $PocomosRecruitStatus->id;
        }

        $input_details['active'] =  1;

        $PocomosRecruitStatus =  PocomosRecruitStatus::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Recruit Status created successfully.', $PocomosRecruitStatus);
    }

    /**
     * API for update of Recruiting Status
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'recruit_status_id' => 'required|exists:pocomos_recruit_status,id',
            'name' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'description' => 'nullable',
            'default_status' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruitStatus = PocomosRecruitStatus::find($request->recruit_status_id);

        if (!$PocomosRecruitStatus) {
            return $this->sendResponse(false, 'Recruit Status not found.');
        }

        $officeConfig = PocomosRecruitingOfficeConfiguration::whereOfficeId($request->office_id)->first();
        if ($request->default_status) {
            PocomosRecruitStatus::where('recruiting_office_configuration_id', $officeConfig->id)->update(array('default_status' => false));
        } else {
            $ifExistDefault = PocomosRecruitStatus::where('recruiting_office_configuration_id', $officeConfig->id)->where('default_status', true)->where('id', '!=', $request->recruit_status_id)->first();
            if (!$ifExistDefault) {
                throw new \Exception(__('strings.message', ['message' => "You must have a atleast one default Recruit Status status."]));
            }
        }

        $PocomosRecruitStatus->update(
            $request->only('default_status', 'name', 'description')
        );

        return $this->sendResponse(true, 'Recruit Status updated successfully.', $PocomosRecruitStatus);
    }

    /**
     * API for delete of Recruiting Status
     .
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $pocomosRecruitStatus = PocomosRecruitStatus::findOrFail($id);

        if ($pocomosRecruitStatus->default_status) {
            throw new \Exception(__('strings.message', ['message' => "Cann't delete default status data."]));
        }

        $defaultStatus = PocomosRecruitStatus::where('recruiting_office_configuration_id', $pocomosRecruitStatus->recruiting_office_configuration_id)->where('default_status', true)->first();
        if (!$defaultStatus) {
            throw new \Exception(__('strings.message', ['message' => "You must have a default Recruit Status status."]));
        }

        $pocomosRecruitStatus->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Recruit Status deleted successfully.');
    }

    /**
     * API for check any default status exist or not
     * @return \Illuminate\Http\Response
     */

    public function checkDefaultStatusExist(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeConfig = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)->first();

        $defaultStatus = PocomosRecruitStatus::where('recruiting_office_configuration_id', $officeConfig->id)->where('default_status', true)->first();
        if (!$defaultStatus) {
            throw new \Exception(__('strings.message', ['message' => "You must have a default Recruit Status status before you can create a Recruit."]));
        }

        return $this->sendResponse(true, __('strings.details', ['name' => 'Recruit Status']), $defaultStatus);
    }
}
