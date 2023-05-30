<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosReportSummerTotalConfiguration;
use App\Models\Pocomos\PocomosSalesStatus;
use DB;
use App\Models\Pocomos\PocomosReportSummerTotalConfigurationStatus;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class SummerTotalConfigurationController extends Controller
{
    use Functions;

    /* API for list Sales Status Setting */

    public function listSalesStatus($officeid)
    {
        $parentofficeid = PocomosCompanyOffice::where('id', $officeid)->pluck('parent_id')->first();

        if ($parentofficeid != null) {
            $officeid = $parentofficeid;
        }

        $PocomosSalesStatus = PocomosSalesStatus::where('office_id', $officeid)->where('active', 1)->orderBy('default_status', 'DESC')
            ->get();

        $Configuration = PocomosReportSummerTotalConfiguration::where('office_id', $officeid)->first();

        $PocomosSalesStatus->map(function ($status) use ($officeid, $Configuration) {
            $action = PocomosReportSummerTotalConfigurationStatus::where('configuration_id', $Configuration->id)->where('sales_status_id', $status['id'])->first();

            if ($action == null) {
                $status['checked'] = false;
            } else {
                $status['checked'] = true;
            }
        });

        $data = [
            'records' => $PocomosSalesStatus,
            'salesperson_minimum' => $Configuration->salesperson_minimum,
            'branch_minimum' => $Configuration->branch_minimum,
        ];

        return $this->sendResponse(true, 'Lists all Summer Total Configuration.', $data);
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
            'branch_minimum' => 'required|numeric',
            'salesperson_minimum' => 'required|numeric',
            'sales_status_id' => 'required|array',
            'sales_status_id.*' => 'exists:pocomos_sales_status,id'
        ]);
        //sales_status_id validation : |unique:pocomos_report_summer_total_configurations_statuses

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $parentofficeid = PocomosCompanyOffice::where('id', $request->office_id)->pluck('parent_id')->first();

        if ($parentofficeid != null) {
            $request->office_id = $parentofficeid;
        }

        $Configuration = PocomosReportSummerTotalConfiguration::where('office_id', $request->office_id)
            ->first();

        if (!$Configuration) {
            $input['office_id'] = $request->office_id;
            $input['branch_minimum'] =  $request['branch_minimum'];
            $input['salesperson_minimum'] = $request['salesperson_minimum'];
            $input['active'] = 1;

            $Configuration =  PocomosReportSummerTotalConfiguration::create($input);

            foreach ($request->sales_status_id as $sales) {
                $action = new PocomosReportSummerTotalConfigurationStatus();
                $action->configuration_id =  $Configuration->id;
                $action->sales_status_id =  $sales;
                $action->save();
            }
            return $this->sendResponse(true, 'The configuration has been updated successfully.', $Configuration);
        }

        $Configuration->update(
            $request->only('branch_minimum', 'salesperson_minimum')
        );

        $action = PocomosReportSummerTotalConfigurationStatus::where('configuration_id', $Configuration->id)->delete();

        foreach ($request->sales_status_id as $sales) {
            $action = new PocomosReportSummerTotalConfigurationStatus();
            $action->configuration_id =  $Configuration->id;
            $action->sales_status_id =  $sales;
            $action->save();
        }

        return $this->sendResponse(true, 'The configuration has been updated successfully.', $Configuration);
    }
}
