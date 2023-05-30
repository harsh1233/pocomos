<?php

namespace App\Http\Controllers\API\Pocomos\MessageBoard;

use Auth;
use Hash;
use Excel;
use App\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosTeam;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Orkestra\OrkestraGroup;
use Illuminate\Support\Facades\Redirect;
use App\Models\Pocomos\PocomosOfficeAlert;
use App\Notifications\TaskAddNotification;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class ToDoController extends Controller
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
            'dateDue' => 'required|date',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = OrkestraUser::where('id', $request->assign_to)->first();

        $sender = OrkestraUser::where('id', $request->assignd_by)->first();

        $user->notify(new TaskAddNotification($request, $user, $sender));

        $data = $this->createToDo($request, 'ToDo', 'Task');

        return $this->sendResponse(true, 'The task has been created successfully.', $data);
    }



    /* Add new ToDO Task Someone*/
    public function taskSomeone(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'required|array|exists:orkestra_users,id',
            'description' => 'required',
            'priority' => 'required',
            'dateDue' => 'required|date',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        foreach ($request->assign_to as $assign_to) {
            $user = OrkestraUser::where('id', $request->assign_to)->first();
            $sender = OrkestraUser::where('id', $request->assignd_by)->first();
            $user->notify(new TaskAddNotification($request, $user, $sender));

            $find_assigned_by_id = PocomosCompanyOfficeUser::where('user_id', $request->assignd_by)->where('office_id', $request->office_id)->first();
            $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $assign_to)->where('office_id', $request->office_id)->first();

            $input['name'] = 'Task';
            $input['description'] = $request->description;
            $input['priority'] = $request->priority;
            $input['status'] = 'Posted';
            $input['type'] = 'ToDo';
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

        return $this->sendResponse(true, 'The task has been created successfully.', $pocomos_office_alert_create);
    }



    /* Add new ToDO Task to Team*/
    public function taskTeam(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'required|exists:pocomos_teams,id',
            'description' => 'required',
            'priority' => 'required',
            'dateDue' => 'required|date',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosTeam =  PocomosTeam::find($request->assign_to);

        $sender_office = PocomosCompanyOfficeUser::join('orkestra_users as pj', 'pocomos_company_office_users.user_id', 'pj.id')->join('pocomos_salespeople as ps', 'pocomos_company_office_users.id', 'ps.user_id')->join('pocomos_memberships as pm', 'ps.id', 'pm.salesperson_id')->where('pocomos_company_office_users.active', 1)->where('pj.active', 1)->where('pm.team_id', $request->assign_to)->pluck('pocomos_company_office_users.user_id')->toArray();

        foreach ($sender_office as $assign_to) {
            $user = OrkestraUser::where('id', $request->assign_to)->first();
            $sender = OrkestraUser::where('id', $request->assignd_by)->first();
            $user->notify(new TaskAddNotification($request, $user, $sender));

            $find_assigned_by_id = PocomosCompanyOfficeUser::where('user_id', $request->assignd_by)->where('office_id', $request->office_id)->first();
            $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $assign_to)->where('office_id', $request->office_id)->first();

            $input['name'] = 'Task';
            $input['description'] = $request->description;
            $input['priority'] = $request->priority;
            $input['status'] = 'Posted';
            $input['type'] = 'ToDo';
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

        return $this->sendResponse(true, 'The task has been created successfully.');
    }


    /* Add new ToDO Task to branch*/
    public function taskBranch(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'required|exists:pocomos_company_offices,id',
            'description' => 'required',
            'priority' => 'required',
            'dateDue' => 'required|date',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sender_office = PocomosCompanyOfficeUser::join('orkestra_users as pj', 'pocomos_company_office_users.user_id', 'pj.id')->where('pocomos_company_office_users.active', 1)->where('pj.active', 1)->where('pocomos_company_office_users.office_id', $request->office_id)->pluck('pocomos_company_office_users.user_id')->toArray();

        foreach ($sender_office as $assign_to) {
            $user = OrkestraUser::where('id', $request->assign_to)->first();
            $sender = OrkestraUser::where('id', $request->assignd_by)->first();
            $user->notify(new TaskAddNotification($request, $user, $sender));

            $find_assigned_by_id = PocomosCompanyOfficeUser::where('user_id', $request->assignd_by)->where('office_id', $request->office_id)->first();
            $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $assign_to)->where('office_id', $request->office_id)->first();

            $input['name'] = 'Task';
            $input['description'] = $request->description;
            $input['priority'] = $request->priority;
            $input['status'] = 'Posted';
            $input['type'] = 'ToDo';
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

        return $this->sendResponse(true, 'The task has been created successfully.');
    }

    /* Add new ToDO to task Everyone*/
    public function taskEveryone(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'nullable|exists:orkestra_users,id',
            'description' => 'required',
            'priority' => 'required',
            'dateDue' => 'required|date',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sender_office =  PocomosCompanyOfficeUser::join('orkestra_users as pj', 'pocomos_company_office_users.user_id', 'pj.id')->where('pocomos_company_office_users.active', 1)->where('pj.active', 1)->where('pocomos_company_office_users.office_id', $request->office_id)->pluck('pocomos_company_office_users.user_id')->toArray();

        foreach ($sender_office as $assign_to) {
            $user = OrkestraUser::where('id', $request->assign_to)->first();
            $sender = OrkestraUser::where('id', $request->assignd_by)->first();
            $user->notify(new TaskAddNotification($request, $user, $sender));

            $find_assigned_by_id = PocomosCompanyOfficeUser::where('user_id', $request->assignd_by)->where('office_id', $request->office_id)->first();
            $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $assign_to)->where('office_id', $request->office_id)->first();

            $input['name'] = 'Task';
            $input['description'] = $request->description;
            $input['priority'] = $request->priority;
            $input['status'] = 'Posted';
            $input['type'] = 'ToDo';
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

        return $this->sendResponse(true, 'The task has been created successfully.');
    }


    /* List of TODO Task */
    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:orkestra_users,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $request->user_id)->where('office_id', $request->office_id)->pluck('id')->toArray();

        $alert = PocomosOfficeAlert::whereHas(
            'todo_details',
            function ($query) {
                $query->where('status', '!=', 'Completed');
            }
        )->whereIn('assigned_to_user_id', $find_assigned_by_to)->with('todo_details', 'assigned_by_details', 'assigned_to_details');

        if ($request->search) {
            $search = $request->search;
            $date = date('Y-m-d', strtotime($search));

            $sql = "SELECT pa.id
            FROM pocomos_office_alerts AS poa
            JOIN pocomos_alerts AS pa ON poa.alert_id = pa.id
            LEFT JOIN pocomos_company_office_users AS pcou ON poa.assigned_by_user_id = pcou.id
            LEFT JOIN orkestra_users AS ou ON pcou.user_id = ou.id where (pa.name LIKE '%$search%' OR pa.description LIKE '%$search%' OR pa.status LIKE '%$search%' OR pa.priority LIKE '%$search%' OR ou.first_name LIKE '%$search%' OR ou.last_name LIKE '%$search%' OR pa.date_due LIKE '%$date%') ";
            $alertTeampIds = DB::select(DB::raw($sql));

            $alertIds = array_map(function ($value) {
                return $value->id;
            }, $alertTeampIds);
            $alert->whereIn('alert_id', $alertIds);
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $alert->count();
        $alert->skip($perPage * ($page - 1))->take($perPage);

        $alert = $alert->get();

        return $this->sendResponse(true, 'List', [
            'alert' => $alert,
            'count' => $count,
        ]);
    }

    /*APi for change status of Task*/
    public function changeTaskStatus(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'nullable|exists:orkestra_users,id',
            'task_id' => 'required|exists:pocomos_alerts,id',
            'status' => 'required|in:In Progress,Completed',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $task = PocomosAlert::where('id', $request->task_id)->first();

        $task->status = $request->status;
        $task = $task->save();

        return $this->sendResponse(true, 'Task status updated', $task);
    }

    public function completedTaskHistory(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'user_id' => 'required|exists:orkestra_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $request->user_id)->where('office_id', $request->office_id)->pluck('id')->toArray();

        $alert = PocomosOfficeAlert::whereHas(
            'alert_todo_details'
        )->orWhereHas(
            'alert_history_details'
        )->whereIn('assigned_to_user_id', $find_assigned_by_to)->with('alert_todo_details', 'alert_history_details', 'assigned_by_details', 'assigned_to_details');

        if ($request->search) {
            $search = $request->search;
            $date = date('Y-m-d', strtotime($search));

            $sql = "SELECT pa.id
            FROM pocomos_office_alerts AS poa
            JOIN pocomos_alerts AS pa ON poa.alert_id = pa.id
            LEFT JOIN pocomos_company_office_users AS pcou ON poa.assigned_by_user_id = pcou.id
            LEFT JOIN orkestra_users AS ou ON pcou.user_id = ou.id where (pa.name LIKE '%$search%' OR pa.description LIKE '%$search%' OR pa.status LIKE '%$search%' OR pa.priority LIKE '%$search%' OR ou.first_name LIKE '%$search%' OR ou.last_name LIKE '%$search%' OR pa.date_due LIKE '%$date%') ";
            $alertTeampIds = DB::select(DB::raw($sql));

            $alertIds = array_map(function ($value) {
                return $value->id;
            }, $alertTeampIds);
            $alert->whereIn('alert_id', $alertIds);
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $alert->count();
        $alert->skip($perPage * ($page - 1))->take($perPage);

        $alert = $alert->get();

        return $this->sendResponse(true, 'List', [
            'alert' => $alert,
            'count' => $count,
        ]);
    }

    /**
     * API for list of Teams
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function teamlist(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruitStatus = PocomosTeam::where('office_id', $request->office_id)->get();

        if (!$PocomosRecruitStatus) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Team']));
        }

        return $this->sendResponse(true, 'List of Recruiting Region.', $PocomosRecruitStatus);
    }

    /**
     * API for list of Branch
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function branchlist(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeid = PocomosCompanyOffice::where('id', $request->office_id)->first();

        if (!$officeid) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Office']));
        }

        if ($officeid->parent_id != null) {
            $request->office_id = $officeid->parent_id;
        }

        $branches = PocomosCompanyOffice::where('active', true)->whereId($request->office_id)->orWhere('parent_id', $request->office_id)->get();

        return $this->sendResponse(true, 'List of Recruiting Region.', $branches);
    }


    /**
     * API for list of Users for someone list
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    /* public function someonelist(Request $request)
    {
        $find_users_id_from_office = PocomosCompanyOfficeUser::where('office_id', $request->office_id)
            ->whereDeleted(0)->get()->toArray();
        $user_ids = array_column($find_users_id_from_office, "user_id");
        // $profile_ids = array_column($find_users_id_from_office, "profile_id");

        $roles = ['ROLE_CUSTOMER'];

        $primaryRoles = OrkestraGroup::whereNotIn('role', $roles)->pluck('id');

        $OrkestraUser = OrkestraUser::with(['permissions' => function ($query) use ($primaryRoles) {
            $query->whereIn('group_id', $primaryRoles);
        }])->whereIn('id', $user_ids);

        $OrkestraUser = $OrkestraUser->orderBy('id', 'desc')->where('active', '1')->get();

        $res = array(
            'alerts_enabled' => array(),
            'alerts_disabled' => array()
        );
        foreach ($OrkestraUser as $value) {
            foreach ($value->permissions as $permissions) {
                //`Show Alerts on Message Board` role granted then user is enable other wise disabled
                if ($permissions['permission']['role'] == 'ROLE_TACKBOARD_ALERTS') {
                    $res['alerts_enabled'][] = $value;
                } else {
                    $res['alerts_disabled'][] = $value;
                }
                //Now not need to give permissions list, if need then remove it
                unset($value['permissions']);
            }
        }
        return $this->sendResponse(true, 'List of User.', $res);
    } */

    public function someonelist(Request $request)
    {
        $v = validator($request->all(), [
            'type' => 'required|in:alert,todo',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        $typeNames = array(
            'alert' => 'Alert',
            'todo' => 'Task'
        );

        $results = PocomosCompanyOfficeUser::select('*', 'ou.id')
            ->join('orkestra_users as ou', 'pocomos_company_office_users.user_id', 'ou.id')
            ->join('orkestra_user_groups as oug', 'ou.id', 'oug.user_id')
            ->join('orkestra_groups as og', 'oug.group_id', 'og.id')
            ->where('og.role', '!=', 'ROLE_CUSTOMER')
            ->where('pocomos_company_office_users.office_id', $officeId)
            ->where('pocomos_company_office_users.active', 1)
            ->where('ou.active', 1)
            ->groupBy('ou.id')
            ->orderBy('ou.first_name')
            ->orderBy('ou.last_name')
            ->get();

        $name = $typeNames[$request->type];

        $enabledKey = $name . 's enabled';
        $disabledKey = $name . 's disabled';
        $groupName = 'Show ' . $name . 's on Message Board';

        $choices = array(
            strtolower(str_replace(' ', '_', $enabledKey)) => array(),
            strtolower(str_replace(' ', '_', $disabledKey)) => array()
        );

        $q = 0;
        $w = 0;
        foreach ($results as $user) {
            // return 11;
            $permissions = $user->user_details->permissions;

            foreach ($permissions as $p) {

                $name = $p->permission->name;

                if ($groupName == $name) {
                    $choices[strtolower(str_replace(' ', '_', $enabledKey))][$q] = $user;
                } else {
                    $choices[strtolower(str_replace(' ', '_', $disabledKey))][$w] = $user;
                }
            }

            if ($groupName == $name) {
                $q++;
            } else {
                $w++;
            }
        }

        return $this->sendResponse(true, 'List of employees', $choices);
    }
}
