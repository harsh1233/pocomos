<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosSalestrackerOfficeSalesAlertSetting;
use App\Models\Pocomos\PocomosNotificationSetting;
use DB;
use App\Models\Pocomos\PocomosOfficeSetting;

class AlertConfigurationController extends Controller
{
    use Functions;

    /**
     * API for details of SalesAlertConfiguration setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function messageboardconfigs(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'enable_to_do_priority' => 'required|boolean',
            'enable_alert_priority' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }


        $PocomosCompanyOffice = PocomosOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the SalesAlertConfiguration.');
        }

        $PocomosCompanyOffice->update([
            'enable_alert_priority' => $request->enable_alert_priority,
            'enable_to_do_priority' => $request->enable_to_do_priority,
        ]);

        return $this->sendResponse(true, 'Office configuration updated successfully.', $PocomosCompanyOffice);
    }

    /**
     * API for get details of SalesAlertConfiguration setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function getmessageboardconfigs(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosOfficeSetting::where('office_id', $request->office_id)->select('enable_alert_priority', 'enable_to_do_priority')->get();

        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the SalesAlertConfiguration.');
        }

        return $this->sendResponse(true, 'Office configuration .', $PocomosCompanyOffice);
    }
}
