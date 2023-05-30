<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosOfficeOpiniionSetting;
use DB;
use App\Models\Pocomos\PocomosDocusendConfiguration;
use App\Models\Pocomos\PocomosFreshlimeOfficeConfig;
use App\Models\Pocomos\PocomosOfficeBirdeyeSetting;

class OfficeOpiniionSettingController extends Controller
{
    use Functions;

    /**
     * API for list of OfficeOpiniionSetting
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function listopiniion($id)
    {
        $PocomosOfficeOpiniionSetting = PocomosOfficeOpiniionSetting::where('active', 1)->where('office_id', $id)->first();

        return $this->sendResponse(true, 'List of Opiniion Settings.', $PocomosOfficeOpiniionSetting);
    }


    /**
     * API for list of docusend
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function listdocusend($id)
    {
        $PocomosDocusendSetting = PocomosDocusendConfiguration::where('office_id', $id)->first();

        return $this->sendResponse(true, 'List of Docusend Settings.', $PocomosDocusendSetting);
    }


    /**
     * API for list of freshlime
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function listfreshlime($id)
    {
        $PocomosFreshlimeOfficeConfig = PocomosFreshlimeOfficeConfig::where('office_id', $id)->first();

        return $this->sendResponse(true, 'List of freshlime Settings.', $PocomosFreshlimeOfficeConfig);
    }


    /**
     * API for list of listbirdeye
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function listbirdeye($id)
    {
        $PocomosOfficeBirdeyeSetting = PocomosOfficeBirdeyeSetting::where('office_id', $id)->first();

        return $this->sendResponse(true, 'List of birdeye Settings.', $PocomosOfficeBirdeyeSetting);
    }


    /**
     * API for update of OfficeOpiniionSetting
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function opiniion_integration(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'integer|min:1',
            'enabled' => 'boolean',
            'uid' => 'nullable',
            'api_key' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosOfficeOpiniionSetting = PocomosOfficeOpiniionSetting::where('active', 1)->where('office_id', $request->office_id)->first();

        if (!$PocomosOfficeOpiniionSetting) {
            return $this->sendResponse(false, 'Unable to find the Opiniion Settings.');
        }

        $PocomosOfficeOpiniionSetting->update(
            $request->only('api_key', 'uid', 'enabled')
        );

        return $this->sendResponse(true, 'Settings have been updated successfully.', $PocomosOfficeOpiniionSetting);
    }

    /**
     * API for update of OfficeOpiniionSetting
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function docusend_config(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'integer|min:1',
            'live' => 'boolean',
            'user_email' => 'nullable|email',
            'user_password' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosDocusendSetting = PocomosDocusendConfiguration::where('office_id', $request->office_id)->first();

        if (!$PocomosDocusendSetting) {
            return $this->sendResponse(false, 'Unable to find the Docusend config Settings.');
        }

        $PocomosDocusendSetting->update(
            $request->only('user_email', 'user_password', 'live')
        );

        return $this->sendResponse(true, 'Settings have been updated successfully.', $PocomosDocusendSetting);
    }

    /**
     * API for update of freshlime_config
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function freshlime_config(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'integer|min:1',
            'active' => 'boolean',
            'app_id' => 'nullable',
            'user_key' => 'nullable',
            'merchant_id' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosFreshlimeOfficeConfig = PocomosFreshlimeOfficeConfig::where('office_id', $request->office_id)->first();

        if (!$PocomosFreshlimeOfficeConfig) {
            return $this->sendResponse(false, 'Unable to find the Opiniion Settings.');
        }

        $PocomosFreshlimeOfficeConfig->update(
            $request->only('active', 'app_id', 'user_key', 'merchant_id')
        );

        return $this->sendResponse(true, 'Settings have been updated successfully.', $PocomosFreshlimeOfficeConfig);
    }



    /**
     * API for update of birdeye_config
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function birdeye_config(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'integer|min:1',
            'enabled' => 'boolean',
            'api_key' => 'nullable',
            'tags' => 'nullable',
            'business_id' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosOfficeBirdeyeSetting = PocomosOfficeBirdeyeSetting::where('office_id', $request->office_id)->first();

        if (!$PocomosOfficeBirdeyeSetting) {
            $input['enabled'] = $request['enabled'];
            $input['api_key'] =  $request['api_key'];
            $input['tags'] = $request['tags'];
            $input['business_id'] = $request['business_id'];
            $input['office_id'] = $request['office_id'];

            $Configuration =  PocomosOfficeBirdeyeSetting::create($input);

            return $this->sendResponse(true, 'The configuration has been updated successfully.', $Configuration);
        }

        $PocomosOfficeBirdeyeSetting->update(
            $request->only('enabled', 'api_key', 'tags', 'business_id')
        );

        return $this->sendResponse(true, 'Settings have been updated successfully.', $PocomosOfficeBirdeyeSetting);
    }
}
