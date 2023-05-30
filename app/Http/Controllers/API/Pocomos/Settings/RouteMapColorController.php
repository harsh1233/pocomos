<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class RouteMapColorController extends Controller
{
    use Functions;

    /**
     * API for pest ofice Configuration route Edit Update setting routes
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function pestoficeConfigurationrouteEdit(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'route_map_coloring_scheme' => 'required|in:Preferred,Scheduled',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosPestOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosPestOfficeSetting->update([
            'route_map_coloring_scheme' => $request->route_map_coloring_scheme
        ]);

        return $this->sendResponse(true, 'Setting changed successfully.');
    }


    public function pestoficeConfigurationrouteget(Request $request)
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

        $PocomosOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->select('route_map_coloring_scheme')
            ->first();

        if (!$PocomosOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        return $this->sendResponse(true, 'Office Configuration.', $PocomosOfficeSetting);
    }
}
