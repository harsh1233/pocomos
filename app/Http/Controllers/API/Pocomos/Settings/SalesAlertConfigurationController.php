<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosSalestrackerOfficeSalesAlertSetting;
use App\Models\Pocomos\PocomosNotificationSetting;
use DB;
use App\Models\Pocomos\PocomosSalestrackerOfficeSetting;

class SalesAlertConfigurationController extends Controller
{
    use Functions;

    /**
     * API for list of SalesAlertConfiguration entities
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request, $office_id)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $salestrackerOfficeSalesAlertSetting = DB::table('pocomos_notification_settings as sa')->join('pocomos_salestracker_office_sales_alert_settings as ca', 'ca.config_id', '=', 'sa.id')->where('ca.office_id', $office_id);

        if ($request->search) {
            $search = $request->search;
            if ($search == 'Yes' || $search == 'yes') {
                $search = 1;
            } elseif ($search == 'No' || $search == 'no') {
                $search = 0;
            }

            $salestrackerOfficeSalesAlertSetting = $salestrackerOfficeSalesAlertSetting->where(function ($q) use ($search) {
                $q->where('ca.no_of_sales', 'like', '%' . $search . '%');
                $q->orWhere('ca.interval_type', 'like', '%' . $search . '%');
                $q->orWhere('ca.notify_type', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $salestrackerOfficeSalesAlertSetting->count();
        $salestrackerOfficeSalesAlertSetting = $salestrackerOfficeSalesAlertSetting->skip($perPage * ($page - 1))->take($perPage)->orderBy('sa.id', 'desc')->get();

        $data = [
            'sales_alerts' => $salestrackerOfficeSalesAlertSetting,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Sales alrts']), $data);
    }

    /**
     * API for details of SalesAlertConfiguration entities
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosSalestrackerOfficeSalesAlertSetting = DB::table('pocomos_salestracker_office_sales_alert_settings as sa')->join('pocomos_notification_settings as ca', 'ca.id', '=', 'sa.config_id')->where('sa.id', $id)->first();

        if (!$PocomosSalestrackerOfficeSalesAlertSetting) {
            return $this->sendResponse(false, 'Configuration Not Found');
        }

        return $this->sendResponse(true, 'Configuration details.', $PocomosSalestrackerOfficeSalesAlertSetting);
    }

    /**
     * API for create of SalesAlertConfiguration entities
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'no_of_sales' => 'required',
            'interval_type' => 'required',
            'notify_type' => 'required',
            'send_alert' => 'boolean',
            'send_email' => 'boolean',
            'active' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('send_alert', 'send_email', 'alert_template', 'email_template');

        $input_details['active'] = 1;

        $PocomosNotificationSetting =  PocomosNotificationSetting::create($input_details);

        $input_details = $request->only('office_id', 'no_of_sales', 'interval_type', 'active', 'notify_type');

        $input_details['config_id'] = $PocomosNotificationSetting->id;

        $PocomosSalestrackerOfficeSalesAlertSetting =  PocomosSalestrackerOfficeSalesAlertSetting::create($input_details);

        return $this->sendResponse(true, 'Configuration created successfully.', $PocomosSalestrackerOfficeSalesAlertSetting);
    }

    /**
     * API for update of SalesAlertConfiguration entities
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'sales_alert_id' => 'required|exists:pocomos_salestracker_office_sales_alert_settings,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'no_of_sales' => 'required',
            'interval_type' => 'required',
            'notify_type' => 'required',
            'send_alert' => 'boolean',
            'send_email' => 'boolean',
            'active' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $SalesAlertSetting = PocomosSalestrackerOfficeSalesAlertSetting::where('office_id', $request->office_id)->where('id', $request->sales_alert_id)->first();

        if (!$SalesAlertSetting) {
            return $this->sendResponse(false, 'Unable to find the SalesAlertConfiguration.');
        }

        $PocomosNotificationSetting = PocomosNotificationSetting::find($SalesAlertSetting->config_id);

        $PocomosNotificationSetting->update(
            $request->only('send_alert', 'send_email', 'alert_template', 'email_template')
        );

        $SalesAlertSetting->update(
            $request->only('office_id', 'no_of_sales', 'interval_type', 'active', 'notify_type')
        );

        return $this->sendResponse(true, 'Configuration updated successfully.', $SalesAlertSetting);
    }

    /**
     * API for delete of SalesAlertConfiguration entities
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosSalestrackerOfficeSalesAlertSetting = PocomosSalestrackerOfficeSalesAlertSetting::find($id);
        if (!$PocomosSalestrackerOfficeSalesAlertSetting) {
            return $this->sendResponse(false, 'SalesAlertConfiguration entities not found.');
        }

        $PocomosSalestrackerOfficeSalesAlertSetting->delete();

        return $this->sendResponse(true, 'Configuration deleted successfully.');
    }


    /**
     * API for details of alertconfiguration setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function alertconfiguration(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'play_sound' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosSalestrackerOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Pest Office Configuration.');
        }

        $PocomosCompanyOffice->update([
            'play_sound' => $request->play_sound
        ]);

        return $this->sendResponse(true, 'Office configuration updated successfully.', $PocomosCompanyOffice);
    }

    /**
     * API for list of alertconfiguration setting play sound
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function alertconfigurationList($id)
    {
        $PocomosCompanyOffice = PocomosSalestrackerOfficeSetting::with('notification_detail')->where('office_id', $id)
            ->first();

        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Pest Office Configuration.');
        }

        return $this->sendResponse(true, 'Office configuration updated successfully.', $PocomosCompanyOffice);
    }
}
