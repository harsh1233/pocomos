<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAcsNotification;
use App\Models\Pocomos\PocomosSalestrackerOfficeSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class BulletinController extends Controller
{
    use Functions;

    /**
     * API for list of Sales Status
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function getbulletin(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosSalesStatus = PocomosSalestrackerOfficeSetting::where('office_id', $request->office_id)->select('id', 'office_id', 'bulletin')->get();

        return $this->sendResponse(true, 'List of Sales Status.', $PocomosSalesStatus);
    }

    /**
     * API for update Form Letter
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'bulletin' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosSalestrackerOfficeSetting = PocomosSalestrackerOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosSalestrackerOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        $PocomosSalestrackerOfficeSetting->update(
            $request->only('bulletin')
        );

        return $this->sendResponse(true, 'The bulletin message has been updated successfully.', $PocomosSalestrackerOfficeSetting);
    }
}
