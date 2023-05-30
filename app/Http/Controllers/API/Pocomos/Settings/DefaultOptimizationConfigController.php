<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosOfficeOptimizationSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class DefaultOptimizationConfigController extends Controller
{
    use Functions;

    /**
     * API for pest ofice Configuration route Edit Update setting routes
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function officeConfigurationOptimization(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'allowed_movement' => 'required|in:0,1,2',
            'optimization_type' => 'required|in:Most Efficient,Preferred Technician,Service Type',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosOfficeOptimizationSetting = PocomosOfficeOptimizationSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosOfficeOptimizationSetting) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosOfficeOptimizationSetting->update([
            'allowed_movement' => $request->allowed_movement,
            'optimization_type' => $request->optimization_type
        ]);

        return $this->sendResponse(true, 'The settings have been updated successfully.');
    }

    public function officeConfigurationOptimizationget(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::find($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosOfficeSetting = PocomosOfficeOptimizationSetting::where('office_id', $request->office_id)->select('allowed_movement', 'optimization_type')
            ->first();

        if (!$PocomosOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        return $this->sendResponse(true, 'Office Configuration.', $PocomosOfficeSetting);
    }
}
