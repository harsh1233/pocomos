<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use DB;
use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosEmailTypeSetting;
use App\Models\Pocomos\PocomosCreditHoldSetting;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosNotificationSetting;
use App\Models\Pocomos\PocomosSalestrackerOfficeSetting;

class EmailTypeSettingController extends Controller
{
    use Functions;

    /* API for list of Email Type Setting */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosEmailTypeSetting = PocomosEmailTypeSetting::where('active', 1)->where('office_id', $request->office_id)->get();

        return $this->sendResponse(true, 'List of Email Type Setting.', $PocomosEmailTypeSetting);
    }

    /* API for get details of Email Type Setting  */

    public function get($id)
    {
        $PocomosEmailTypeSetting = PocomosEmailTypeSetting::find($id);
        if (!$PocomosEmailTypeSetting) {
            return $this->sendResponse(false, 'Email Type Setting Not Found');
        }
        return $this->sendResponse(true, 'Email Type Setting details.', $PocomosEmailTypeSetting);
    }

    /* API for changeStatus of  Email Type Setting */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'email_type_setting_id' => 'required|integer|min:1',
            'enabled' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosEmailTypeSetting = PocomosEmailTypeSetting::find($request->email_type_setting_id);
        if (!$PocomosEmailTypeSetting) {
            return $this->sendResponse(false, 'Email Type Setting not found');
        }

        $PocomosEmailTypeSetting->update([
            'enabled' => $request->enabled
        ]);

        return $this->sendResponse(true, 'Status changed successfully.');
    }


    /**
     * API for pest Office configuration Edit Email
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function pestOfficeconfigurationEditEmail(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'notify_only_verified' => 'boolean|nullable',
            'notify_on_assign' => 'boolean|nullable',
            'only_show_date' => 'boolean|nullable',
            'include_time_window' => 'boolean|nullable',
            'time_window_length' => 'nullable',
            'notify_on_reschedule' => 'boolean|nullable',
            'include_begin_end_in_invoice' => 'boolean|nullable',
            'send_welcome_email' => 'nullable',
            'send_alert' => 'nullable',
            'send_email' => 'nullable',
            'alert_template' => 'nullable',
            'email_template' => 'nullable',
            'welcome_letter' => 'nullable',
            'assign_message' => 'nullable',
            'reschedule_message' => 'nullable',
            'complete_message' => 'nullable',
            'bill_message' => 'nullable',
            'send_inbound_sms_email' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)
            ->first();

        $input_details = $request->only('notify_only_verified', 'notify_on_assign', 'only_show_date', 'include_time_window', 'time_window_length', 'notify_on_reschedule', 'include_begin_end_in_invoice', 'send_welcome_email', 'welcome_letter', 'assign_message', 'reschedule_message', 'complete_message', 'bill_message', 'send_inbound_sms_email');

        if (!$PocomosPestOfficeSetting) {
            $input_details['initial_duration'] = 0;
            $input_details['regular_duration'] = 0;
            $input_details['active'] = true;
            $input_details['office_id'] = $request->office_id;
            $input_details['include_schedule'] = true;
            $input_details['include_pricing'] = true;
            $input_details['separated_by_type'] = true;
            $input_details['enable_optimization'] = true;
            $input_details['anytime_enabled'] = true;
            $input_details['disable_recurring_jobs'] = true;
            $input_details['require_map_code'] = true;
            $input_details['only_show_date'] = true;
            $input_details['coloring_scheme'] = true;
            $input_details['route_map_coloring_scheme'] = true;
            $input_details['enable_remote_completion'] = true;
            $input_details['my_spots_duration'] = true;
            $input_details['show_service_duration_option_agreement'] = true;
            $PocomosPestOfficeSetting = PocomosPestOfficeSetting::create($input_details);
        } else {
            $PocomosPestOfficeSetting->update($input_details);
        }

        $PocomosSalestrackerOfficeSetting = PocomosSalestrackerOfficeSetting::where('office_id', $request->office_id)->first();

        if ($PocomosSalestrackerOfficeSetting) {
            $PocomosNotificationSetting = PocomosNotificationSetting::find($PocomosSalestrackerOfficeSetting->initial_service_alert_config_id);

            $PocomosNotificationSetting->update(
                $request->only('send_alert', 'send_email', 'alert_template', 'email_template')
            );
        } else {
            $PocomosNotificationSetting = PocomosNotificationSetting::create(
                $request->only('send_alert', 'send_email', 'alert_template', 'email_template') + ['active' => true]
            );

            $salesTrackerInput['office_id'] = $request->office_id;
            $salesTrackerInput['active'] = true;
            $salesTrackerInput['initial_service_alert_config_id'] = $PocomosNotificationSetting->id;
            $salesTrackerInput['bulletin'] = '';
            $salesTrackerInput['play_sound'] = true;
            $salesTrackerInput['vtp_enabled'] = true;
            $PocomosSalestrackerOfficeSetting = PocomosSalestrackerOfficeSetting::create($salesTrackerInput);
        }

        return $this->sendResponse(true, 'Email notifications updated successfully.', $PocomosPestOfficeSetting);
    }


    /* API for list of Email Type Setting */

    public function ListpestOfficeconfigurationEmail($id)
    {
        $PocomosPestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $id)
            ->first();

        $PocomosSalestrackerOfficeSetting = DB::table('pocomos_salestracker_office_settings as sa')->where('office_id', $id)->join('pocomos_notification_settings as ca', 'ca.id', '=', 'sa.initial_service_alert_config_id')->select('send_alert', 'send_email', 'alert_template', 'email_template')->first();

        if ($PocomosSalestrackerOfficeSetting) {
            $PocomosPestOfficeSetting['send_alert'] =  $PocomosSalestrackerOfficeSetting->send_alert;

            $PocomosPestOfficeSetting['send_email'] =  $PocomosSalestrackerOfficeSetting->send_email;

            $PocomosPestOfficeSetting['alert_template'] =  $PocomosSalestrackerOfficeSetting->alert_template;

            $PocomosPestOfficeSetting['email_template'] =  $PocomosSalestrackerOfficeSetting->email_template;
        }
        return $this->sendResponse(true, 'List of Email Type Setting.', $PocomosPestOfficeSetting);
    }

    /**Udpate credit hold setting */
    public function creditHoldSetting(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'due_days' => 'required|integer',
            'on_hold' => 'required|boolean',
            'reactivate' => 'required|boolean'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('due_days', 'on_hold', 'reactivate', 'office_id') + ['active' => true];

        PocomosCreditHoldSetting::updateOrCreate(['office_id' => $request->office_id], $input_details);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Credit hold settings updated']));
    }

    /**Get credit hold setting */
    public function getCreditHoldSetting(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $setting = PocomosCreditHoldSetting::whereOfficeId($request->office_id)->first();

        return $this->sendResponse(true, __('strings.details', ['name' => 'Credit hold settings']), $setting);
    }
}
