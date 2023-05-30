<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosOfficeDashboardState;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class DashboardAdminOnlyController extends Controller
{
    use Functions;

    /**
     * API for list of Admin Sender
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function get(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'date_range' =>'in:yesterday,this_week,last_week,this_month,last_month,last_six_months,this_year,all_time'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $dateRange = $request->date_range ? $request->date_range : 'yesterday';

        $officeDashboardState = PocomosOfficeDashboardState::with('adminWidget')->whereOfficeId($request->office_id)->whereActive(true);

        $officeDashboardStateCount = $officeDashboardState->count();

        $officeDashboardState = $officeDashboardState->groupBy('admin_widget_id')->get(['id','admin_widget_id',$dateRange, 'settings']);

        $officeDashboardState = $officeDashboardState->map(function ($officeDashboardState) use ($dateRange) {
            $officeDashboardState->$dateRange = unserialize($officeDashboardState->$dateRange);
            $officeDashboardState->settings = unserialize($officeDashboardState->settings);
            return $officeDashboardState;
        });

        $i = 0;
        foreach ($officeDashboardState as $val) {
            $tmp = $officeDashboardState[$i]->$dateRange;
            if (isset($val->$dateRange)) {
                $j = 0;
                foreach ($officeDashboardState[$i]->$dateRange as $q) {
                    if (isset($q['Office User'])) {
                        $tmp[$j]['office_user'] = $q['Office User'];
                        $j++;
                    }
                }
                $officeDashboardState[$i]->$dateRange = $tmp;
            }
            $i++;
        }

        return $this->sendResponse(true, 'Dashboard Report', $officeDashboardState);
    }
}
