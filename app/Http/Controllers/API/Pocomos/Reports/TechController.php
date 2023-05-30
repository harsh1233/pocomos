<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosOfficeDashboardState;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class TechController extends Controller
{
    use Functions;

    /**
     * API for list of Admin Sender
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function owners(Request $request)
    {
        $dashboardstate = PocomosOfficeDashboardState::where('office_id', $request->office_id)->where('active', 1);
        $dashboardstate_count = $dashboardstate->count();
        $dashboardstate = $dashboardstate->orderBy('id', 'desc')->get();
        $data = [
                'dashboardstate' => $dashboardstate,
                'count' => $dashboardstate_count
            ];

        return $this->sendResponse(true, 'List of Dashboard Owners.', $data);
    }

    /**
     * API for details of Admin Sender
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosPhoneNumber = PocomosPhoneNumber::find($id);
        if (!$PocomosPhoneNumber) {
            return $this->sendResponse(false, 'Admin Sender Not Found');
        }
        return $this->sendResponse(true, 'Admin Sender details.', $PocomosPhoneNumber);
    }
}
