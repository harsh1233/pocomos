<?php

namespace App\Http\Controllers\API\Pocomos\MessageBoard;

use Auth;
use Hash;
use Excel;
use App\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Notifications\TaskAddNotification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosOfficeAlert;
use App\Models\Pocomos\PocomosTeam;

class AlertController extends Controller
{
    use Functions;


    /* Add new ToDO Task */
    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'required|exists:orkestra_users,id',
            'description' => 'required',
            'priority' => 'required|in:Low,Normal,High',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $data = $this->createToDo($request, 'Alert', 'Alert');

        return $this->sendResponse(true, 'The task has been created successfully.', $data);
    }

    /* Add new ToDO Task for messageBranch*/
    public function messageBranch(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|integer|min:1',
            'assign_to' => 'required|integer|min:1',
            'name' => 'required',
            'description' => 'required',
            'priority' => 'required',
            'dateDue' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $data = $this->createToDo($request, 'Alert', 'Alert');

        return $this->sendResponse(true, 'The task has been created successfully.', $data);
    }


    /* Add new alert to  Someone*/
    public function alertSomeone(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'required|array|exists:orkestra_users,id',
            'description' => 'required',
            'priority' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        foreach ($request->assign_to as $assign_to) {
            $find_assigned_by_id = PocomosCompanyOfficeUser::where('user_id', $request->assignd_by)->where('office_id', $request->office_id)->first();
            $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $assign_to)->where('office_id', $request->office_id)->first();

            $input['name'] = 'Alert';
            $input['description'] = $request->description;
            $input['priority'] = $request->priority;
            $input['status'] = 'Posted';
            $input['type'] = 'Alert';
            $input['date_due'] = $request->dateDue;
            $input['active'] = 1;
            $input['notified'] = 0;
            $alert = PocomosAlert::create($input);

            $pocomos_office_alert = [];
            $pocomos_office_alert['alert_id'] = $alert->id;
            $pocomos_office_alert['assigned_by_user_id'] = $find_assigned_by_id->id ?? null;
            $pocomos_office_alert['assigned_to_user_id'] = $find_assigned_by_to->id ?? null;
            $pocomos_office_alert['active'] = '1';
            $pocomos_office_alert_create = PocomosOfficeAlert::create($pocomos_office_alert);
        }

        return $this->sendResponse(true, 'The alert has been created successfully.', $pocomos_office_alert_create);
    }


    /* Add new alert to Team*/
    public function alertTeam(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'required|exists:pocomos_teams,id',
            'description' => 'required',
            'priority' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosTeam =  PocomosTeam::find($request->assign_to);

        $sender_office = PocomosCompanyOfficeUser::join('orkestra_users as pj', 'pocomos_company_office_users.user_id', 'pj.id')->join('pocomos_salespeople as ps', 'pocomos_company_office_users.id', 'ps.user_id')->join('pocomos_memberships as pm', 'ps.id', 'pm.salesperson_id')->where('pocomos_company_office_users.active', 1)->where('pj.active', 1)->where('pm.team_id', $request->assign_to)->pluck('pocomos_company_office_users.user_id')->toArray();

        foreach ($sender_office as $assign_to) {
            $find_assigned_by_id = PocomosCompanyOfficeUser::where('user_id', $request->assignd_by)->where('office_id', $request->office_id)->first();
            $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $assign_to)->where('office_id', $request->office_id)->first();

            $input['name'] = 'Alert';
            $input['description'] = $request->description;
            $input['priority'] = $request->priority;
            $input['status'] = 'Posted';
            $input['type'] = 'Alert';
            $input['date_due'] = $request->dateDue;
            $input['active'] = 1;
            $input['notified'] = 0;
            $alert = PocomosAlert::create($input);

            $pocomos_office_alert = [];
            $pocomos_office_alert['alert_id'] = $alert->id;
            $pocomos_office_alert['assigned_by_user_id'] = $find_assigned_by_id->id ?? null;
            $pocomos_office_alert['assigned_to_user_id'] = $find_assigned_by_to->id ?? null;
            $pocomos_office_alert['active'] = '1';
            $pocomos_office_alert_create = PocomosOfficeAlert::create($pocomos_office_alert);
        }

        return $this->sendResponse(true, 'The alert has been created successfully.');
    }


    /* Add new alert to branch*/
    public function alertBranch(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'required|exists:pocomos_company_offices,id',
            'description' => 'required',
            'priority' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sender_office =  PocomosCompanyOfficeUser::join('orkestra_users as pj', 'pocomos_company_office_users.user_id', 'pj.id')->where('pocomos_company_office_users.active', 1)->where('pj.active', 1)->where('pocomos_company_office_users.office_id', $request->office_id)->pluck('pocomos_company_office_users.user_id')->toArray();

        foreach ($sender_office as $assign_to) {
            $find_assigned_by_id = PocomosCompanyOfficeUser::where('user_id', $request->assignd_by)->where('office_id', $request->office_id)->first();
            $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $assign_to)->where('office_id', $request->office_id)->first();

            $input['name'] = 'Alert';
            $input['description'] = $request->description;
            $input['priority'] = $request->priority;
            $input['status'] = 'Posted';
            $input['type'] = 'Alert';
            $input['date_due'] = $request->dateDue;
            $input['active'] = 1;
            $input['notified'] = 0;
            $alert = PocomosAlert::create($input);

            $pocomos_office_alert = [];
            $pocomos_office_alert['alert_id'] = $alert->id;
            $pocomos_office_alert['assigned_by_user_id'] = $find_assigned_by_id->id ?? null;
            $pocomos_office_alert['assigned_to_user_id'] = $find_assigned_by_to->id ?? null;
            $pocomos_office_alert['active'] = '1';
            $pocomos_office_alert_create = PocomosOfficeAlert::create($pocomos_office_alert);
        }

        return $this->sendResponse(true, 'The alert has been created successfully.');
    }

    /* Add new alert to Everyone*/
    public function alertEveryone(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'nullable|exists:orkestra_users,id',
            'description' => 'required',
            'priority' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sender_office = PocomosCompanyOfficeUser::join('orkestra_users as pj', 'pocomos_company_office_users.user_id', 'pj.id')->where('pocomos_company_office_users.active', 1)->where('pj.active', 1)->where('pocomos_company_office_users.office_id', $request->office_id)->pluck('pocomos_company_office_users.user_id')->toArray();

        foreach ($sender_office as $assign_to) {
            $find_assigned_by_id = PocomosCompanyOfficeUser::where('user_id', $request->assignd_by)->where('office_id', $request->office_id)->first();
            $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $assign_to)->where('office_id', $request->office_id)->first();

            $input['name'] = 'Alert';
            $input['description'] = $request->description;
            $input['priority'] = $request->priority;
            $input['status'] = 'Posted';
            $input['type'] = 'Alert';
            $input['date_due'] = $request->dateDue;
            $input['active'] = 1;
            $input['notified'] = 0;
            $alert = PocomosAlert::create($input);

            $pocomos_office_alert = [];
            $pocomos_office_alert['alert_id'] = $alert->id;
            $pocomos_office_alert['assigned_by_user_id'] = $find_assigned_by_id->id ?? null;
            $pocomos_office_alert['assigned_to_user_id'] = $$find_assigned_by_to->id ?? null;
            $pocomos_office_alert['active'] = '1';
            $pocomos_office_alert_create = PocomosOfficeAlert::create($pocomos_office_alert);
        }

        return $this->sendResponse(true, 'The alert has been created successfully.');
    }

    /* API for Alerts Listing*/
    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:orkestra_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $request->user_id)->where('office_id', $request->office_id)->pluck('id')->toArray();
        // $alert = PocomosOfficeAlert::whereIn('assigned_to_user_id', $find_assigned_by_to)->with('alert_details', 'assigned_by_details', 'assigned_to_details')->get();

        // $all_leads = collect($alert);
        // // $leadsCollection = $all_leads->filter(function ($value) {
        // //     return !is_null($value->todo_details);
        // // })->values();
        // $all_leads->all();
        // return $this->sendResponse(true, 'Alerts Listeninig', $all_leads);

        $alerts = PocomosOfficeAlert::whereHas(
            'alert_details',
            function ($query) {
                $query->where('status', '!=', 'Completed');
            }
        )->whereIn('assigned_to_user_id', $find_assigned_by_to)->with('alert_details', 'assigned_by_details', 'assigned_to_details')->orderBy('date_created', 'desc')->get();

        return $this->sendResponse(true, 'Alerts', $alerts);
    }

    /*APi for change status of all alert to complete*/
    public function changealertStatusComplete(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:orkestra_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $find_assigned_by_user = PocomosCompanyOfficeUser::where('user_id', $request->user_id)->where('office_id', $request->office_id)->first();
        if (!$find_assigned_by_user) {
            return $this->sendResponse(false, 'User not found');
        }
        $PocomosOfficeAlert = PocomosOfficeAlert::where('assigned_to_user_id', $find_assigned_by_user->id)
            ->pluck('alert_id')->toArray();

        if (!$PocomosOfficeAlert) {
            return $this->sendResponse(false, 'Alerts not found');
        }

        $alert = PocomosAlert::whereIn('id', $PocomosOfficeAlert)->update(['status' => 'Completed']);

        return $this->sendResponse(true, 'Alert status updated', $alert);
    }
}
