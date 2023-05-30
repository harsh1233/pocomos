<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosCustomField;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosCustomFieldConfiguration;

class CustomFieldConfigController extends Controller
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
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();
        $customFieldConfiguration = PocomosCustomFieldConfiguration::where('office_configuration_id', $officeSetting->id)
            ->where('active', 1);

        // if(isset($request->active)){
        //     $customFieldConfiguration = $customFieldConfiguration->where('active', $request->active);
        // }

        if ($request->search) {
            $search = $request->search;

            if ($search == 'Yes' || $search == 'yes') {
                $search = 1;
            } elseif ($search == 'No' || $search == 'no') {
                $search = 0;
            }

            $customFieldConfiguration = $customFieldConfiguration->where(function ($q) use ($search) {
                $q->where('label', 'like', '%' . $search . '%');
                $q->orWhere('required', 'like', '%' . $search . '%');
                $q->orWhere('tech_visible', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $customFieldConfiguration->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $customFieldConfiguration = $customFieldConfiguration->skip($perPage * ($page - 1))->take($perPage);
        }
        $customFieldConfiguration = $customFieldConfiguration->orderBy('id', 'desc')->get();

        $data = [
            'custom_fields' => $customFieldConfiguration,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Custom fields']), $data);
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
            'label' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'required' => 'boolean',
            'tech_visible' => 'boolean',
            'show_on_acct_status' => 'boolean',
            'show_on_precompleted_invoice' => 'boolean',
            'active' => 'boolean',
            'show_on_route_map' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->first();

        $pest = PocomosCustomFieldConfiguration::where('label', $request->label)->where('office_configuration_id', $pestOfficeSetting->id)->where('active', 1)->count();

        if ($pest) {
            throw new \Exception(__('strings.message', ['message' => 'Lable already exists']));
        }

        $input_details = $request->only(
            // 'office_configuration_id',
            'label',
            'required',
            'tech_visible',
            'show_on_acct_status',
            'show_on_precompleted_invoice',
            'active',
            'show_on_route_map'
        ) + ['office_configuration_id' => $pestOfficeSetting->id ?? null];

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
            'label' => 'required',
            'custom_field_id' => 'required',
            'required' => 'boolean',
            'tech_visible' => 'boolean',
            'show_on_acct_status' => 'boolean',
            'show_on_precompleted_invoice' => 'boolean',
            'active' => 'boolean',
            'show_on_route_map' => 'boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomFieldConfiguration = PocomosCustomFieldConfiguration::find($request->custom_field_id);

        if (!$PocomosCustomFieldConfiguration) {
            return $this->sendResponse(false, 'Custom Field not found.');
        }

        $pestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->first();

        $pest = PocomosCustomFieldConfiguration::where('office_configuration_id', $pestOfficeSetting->id)->where('label', $request->label)->where('id', '!=', $request->custom_field_id)->where('active', 1)->count();

        if ($pest) {
            throw new \Exception(__('strings.message', ['message' => 'Lable already exists']));
        }

        $PocomosCustomFieldConfiguration->update(
            $request->only('label', 'required', 'tech_visible', 'show_on_acct_status', 'active', 'show_on_precompleted_invoice', 'show_on_route_map')
        );

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

        PocomosCustomField::where('custom_field_configuration_id', $id)->delete();

        // if (PocomosCustomField::where('custom_field_configuration_id', $id)->count()) {
        //     return $this->sendResponse(false, __('strings.message', ['message' => 'The custom field has exist on configuration.']));
        // }

        $PocomosCustomFieldConfiguration->active = 0;
        $PocomosCustomFieldConfiguration->save();

        return $this->sendResponse(true, 'Custom Field deleted successfully.');
    }
}
