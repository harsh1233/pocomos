<?php

namespace App\Http\Controllers\API\Pocomos\MissionSetting;

use App\Models\Pocomos\PocomosMissionConfig;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MissionConfigurationController extends Controller
{
    use Functions;


    /**
     * API for Mission Setting Edit
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'api_key' => 'required',
            'auth_token' => 'required',
            'test_env' => 'required|in:0,1',
            'enabled' => 'required|in:0,1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosMission = PocomosMissionConfig::where('office_id', $request->office_id)->first();

        if (!$PocomosMission) {
            return $this->sendResponse(false, 'Not Found');
        }

        $PocomosMission->update(
            $request->only('office_id', 'api_key', 'auth_token', 'test_env', 'enabled') + ['active' => true]
        );

        return $this->sendResponse(true, __('strings.update', ['name' => 'Mission Configuration has been']));
    }

    public function get($id)
    {
        $PocomosMission = PocomosMissionConfig::where('office_id', $id)->first();
        if (!$PocomosMission) {
            return $this->sendResponse(false, ' Not Found');
        }
        return $this->sendResponse(true, 'Details.', $PocomosMission);
    }
}
