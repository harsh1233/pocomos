<?php

namespace App\Http\Controllers\API\Pocomos\Recruitement;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\Recruitement\OfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosCustomFieldConfiguration;
use App\Models\Pocomos\PocomosRecruitingOfficeConfiguration;

class CustomFieldConfigurationController extends Controller
{
    use Functions;

    /**
     * API for list of Custom Field
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
            $PocomosCustomFieldConfiguration = PocomosCustomFieldConfiguration::where('active', 1)->where('office_configuration_id', $PocomosRecruitStatus->id)->orderBy('id', 'desc');
        } else {
            $PocomosCustomFieldConfiguration = PocomosCustomFieldConfiguration::where('active', 1)->orderBy('id', 'desc');
        }

        if ($request->search) {
            $search = $request->search;
            $PocomosCustomFieldConfiguration->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $PocomosCustomFieldConfiguration->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosCustomFieldConfiguration->skip($perPage * ($page - 1))->take($perPage);
        }

        $PocomosCustomFieldConfiguration = $PocomosCustomFieldConfiguration->get();

        return $this->sendResponse(true, 'List', [
            'PocomosCustomFieldConfiguration' => $PocomosCustomFieldConfiguration,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Custom Field
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosCustomFieldConfiguration = PocomosCustomFieldConfiguration::find($id);
        if (!$PocomosCustomFieldConfiguration) {
            return $this->sendResponse(false, 'Custom Field Not Found');
        }
        return $this->sendResponse(true, 'Custom Field details.', $PocomosCustomFieldConfiguration);
    }

    /**
     * API for create of Custom Field
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'description' => 'required',
            'label' => 'required',
            'legally_binding' => 'boolean|required',
            'required' => 'boolean|required',
            'type' => 'nullable',
            'options' => 'array',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('label', 'name', 'description', 'required', 'type', 'legally_binding');

        $PocomosRecruitStatus = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)->first();

        if ($PocomosRecruitStatus) {
            $input_details['office_configuration_id'] =  $PocomosRecruitStatus->id;
        }

        if ($request->options) {
            $input_details['options'] =  serialize($request->input('options'));
        } else {
            $input_details['options'] =  serialize(array());
        }

        $input_details['active'] = 1;

        $PocomosCustomFieldConfiguration =  PocomosCustomFieldConfiguration::create($input_details);

        return $this->sendResponse(true, 'Custom Field created successfully.', $PocomosCustomFieldConfiguration);
    }

    /**
     * API for update of Custom Field
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'custom_field_id' => 'required|exists:pocomos_recruit_custom_field_configurations,id',
            'name' => 'required',
            'description' => 'required',
            'label' => 'required',
            'legally_binding' => 'boolean|required',
            'required' => 'boolean|required',
            'type' => 'nullable',
            'options' => 'array',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomFieldConfiguration = PocomosCustomFieldConfiguration::find($request->custom_field_id);

        if (!$PocomosCustomFieldConfiguration) {
            return $this->sendResponse(false, 'Custom Field not found.');
        }

        $input_details = $request->only('label', 'name', 'description', 'required', 'type', 'legally_binding');

        if ($request->options) {
            $input_details['options'] =  serialize($request->input('options'));
        }else{
            $input_details['options'] =  serialize(array());
        }

        $PocomosCustomFieldConfiguration->update($input_details);

        return $this->sendResponse(true, 'Custom Field updated successfully.', $PocomosCustomFieldConfiguration);
    }

    /**
     * API for delete of Custom Field
     .
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosCustomFieldConfiguration = PocomosCustomFieldConfiguration::find($id);
        if (!$PocomosCustomFieldConfiguration) {
            return $this->sendResponse(false, 'Custom Field not found.');
        }

        $PocomosCustomFieldConfiguration->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Custom Field deleted successfully.');
    }
}
