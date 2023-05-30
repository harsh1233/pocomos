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

class SettingController extends Controller
{
    use Functions;

    public function getApiCredential(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pestPacConfig = PocomosPestpacConfig::whereOfficeId($request->office_id)->whereActive(true)->firstOrFail();

        return $this->sendResponse(true, 'PestPac api credentials', $pestPacConfig);
    }

    public function updateApiCredential(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'enabled' => 'required',
            'pestpac_company_key' => 'required',
            'pestpac_api_key' => 'required',
            'pestpac_client_id' => 'required',
            'pestpac_client_secret' => 'required',
            'pestpac_username' => 'required',
            'pestpac_password' => 'required',
            'vantiv_account_token' => 'required',
            'vantiv_application_id' => 'required',
            'vantiv_acceptor_id' => 'required',
            'vantiv_account_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        PocomosPestpacConfig::whereId($id)->update([
            'enabled'               => $request->enabled,
            'pestpac_company_key'   => $request->pestpac_company_key,
            'pestpac_api_key'       => $request->pestpac_api_key,
            'pestpac_client_id'     => $request->pestpac_client_id,
            'pestpac_client_secret' => $request->pestpac_client_secret,
            'pestpac_username'      => $request->pestpac_username,
            'pestpac_password'      => $request->pestpac_password,
            'vantiv_account_token'  => $request->vantiv_account_token,
            'vantiv_application_id' => $request->vantiv_application_id,
            'vantiv_acceptor_id'    => $request->vantiv_acceptor_id,
            'vantiv_account_id'     => $request->vantiv_account_id,
        ]);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Pestpac API credentials']));
    }


    public function getData(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pestPacConfigId = PocomosPestpacConfig::whereOfficeId($request->office_id)->whereActive(true)->firstOrFail()->id;

        $pestPacSetting = PocomosPestpacSetting::wherePestpacConfigId($pestPacConfigId)->whereOfficeId($request->office_id)->first();

        $timezones = PocomosTimezone::whereActive(1)->get();

        $taxCodes = PocomosTaxCode::whereOfficeId($officeId)->whereEnabled(1)->whereActive(1)->get();

        return $this->sendResponse(true, 'Form data', [
            'pestpac_setting' => $pestPacSetting,
            'timezones' => $timezones,
            'tax_codes' => $taxCodes,
        ]);
    }

    public function updateSetting(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'validate_and_geocode' => 'required',
            'block_tomorrow_routes' => 'required',
            'source' => 'max:20',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pestPacConfigId = PocomosPestpacConfig::whereOfficeId($request->office_id)->whereEnabled(true)->whereActive(true)->firstOrFail()->id;

        $pestPacSetting = PocomosPestpacSetting::updateOrCreate(
            [
            'office_id' => $officeId
            ],
            [
            'pestpac_config_id'      => $pestPacConfigId,
            'branch_name'            => $request->branch_name,
            'time_zone'              => $request->time_zone,
            'source'                 => $request->source,
            'default_tax_code_id'    => $request->default_tax_code_id,
            'default_region'         => $request->default_region,
            'validate_and_geocode'   => $request->validate_and_geocode,
            'block_tomorrow_routes'  => $request->block_tomorrow_routes,
            'active'                 => 1,
        ]
        );

        return $this->sendResponse(true, __('strings.update', ['name' => 'Pestpac Settings has been']), $pestPacSetting);
    }


    public function listServiceType(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pestpacServiceTypes = PocomosPestpacServiceType::with('contract_service_type')
                ->whereOfficeId($officeId)->whereActive(1)->get();

        return $this->sendResponse(true, 'List', [
            'pestpac_service_types' => $pestpacServiceTypes,
        ]);
    }

    public function getPestContractServiceType(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pestContractServiceTypes = PocomosPestContractServiceType::whereOfficeId($officeId)->whereActive(1)->get();

        return $this->sendResponse(true, 'List', [
            'contract_service_types' => $pestContractServiceTypes,
        ]);
    }


    public function createServiceType(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'pocomos_service_type_id'   => 'nullable|unique:pocomos_pestpac_service_types',
            'pp_service_setup_type'   => 'required',
            'pp_service_setup_color'   => 'required',
            'pp_service_order_type'   => 'required',
            'pp_service_order_color'   => 'required',
            'pp_service_schedule'        => 'required',
            'pp_service_description'   => 'required',
            'default_service'            => 'required',
            'enabled'                    => 'required',
        ], [
            'pocomos_service_type_id.unique' => 'This service type already configured for the current office'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pestpacServiceTypes = PocomosPestpacServiceType::create([
            'office_id'               => $request->office_id,
            'pocomos_service_type_id' => $request->pocomos_service_type_id,
            'pp_service_setup_type'   => $request->pp_service_setup_type,
            'pp_service_setup_color'  => $request->pp_service_setup_color,
            'pp_service_order_type'   => $request->pp_service_order_type,
            'pp_service_order_color'  => $request->pp_service_order_color,
            'pp_service_schedule'     => $request->pp_service_schedule,
            'pp_service_description'  => $request->pp_service_description,
            'default_service'         => $request->default_service,
            'enabled'                 => $request->enabled,
        ]);

        //any service type= null

        return $this->sendResponse(true, __('strings.create', ['name' => 'PestPac service type']));
    }

    public function editPestpacServiceType(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pestPacServiceType = PocomosPestpacServiceType::whereOfficeId($officeId)->whereActive(1)->findOrFail($id);

        return $this->sendResponse(true, __('strings.details', ['name' => 'Pestpac service type']), $pestPacServiceType);
    }


    public function updatePestpacServiceType(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'pocomos_service_type_id'   => 'nullable|unique:pocomos_pestpac_service_types,pocomos_service_type_id,'.$id,
            'pp_service_setup_type'   => 'required',
            'pp_service_setup_color'   => 'required',
            'pp_service_order_type'   => 'required',
            'pp_service_order_color'   => 'required',
            'pp_service_schedule'        => 'required',
            'pp_service_description'   => 'required',
            'default_service'            => 'required',
            'enabled'                    => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pestpacServiceTypes = PocomosPestpacServiceType::whereId($id)->update([
            'pocomos_service_type_id' => $request->pocomos_service_type_id,
            'pp_service_setup_type'   => $request->pp_service_setup_type,
            'pp_service_setup_color'  => $request->pp_service_setup_color,
            'pp_service_order_type'   => $request->pp_service_order_type,
            'pp_service_order_color'  => $request->pp_service_order_color,
            'pp_service_schedule'     => $request->pp_service_schedule,
            'pp_service_description'  => $request->pp_service_description,
            'default_service'         => $request->default_service,
            'enabled'                 => $request->enabled,
        ]);

        //any service type= null

        return $this->sendResponse(true, __('strings.update', ['name' => 'PestPac service type']));
    }

    public function togglePestpacServiceType(Request $request, $id)
    {
        $v = validator($request->all(), [
            'enabled'    => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pestpacServiceTypes = PocomosPestpacServiceType::whereId($id)->update([
            'enabled'                 => $request->enabled,
        ]);

        //any service type= null

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Service type enabled/disable']));
    }
}
