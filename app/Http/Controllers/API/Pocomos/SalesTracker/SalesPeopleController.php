<?php

namespace App\Http\Controllers\API\Pocomos\SalesTracker;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosNote;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Orkestra\OrkestraGroup;
use App\Models\Pocomos\PocomosTimezone;
use App\Models\Orkestra\OrkestraUserGroup;
use App\Models\Pocomos\PocomosOfficeAlert;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Notifications\TaskAddNotification;
use App\Models\Pocomos\PocomosCommissionBonuse;
use App\Models\Pocomos\PocomosCommissionSetting;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosSalespersonProfile;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCommissionDeduction;
use App\Models\Pocomos\PocomosCompanyOfficeUserNote;
use App\Models\Pocomos\PocomosVtpCertificationLevel;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Pocomos\PocomosSalestrackerOfficeSetting;

class SalesPeopleController extends Controller
{
    use Functions;

    /**
     * API for list of Office Bonuse
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $sql = 'Select ou.id as ou_id,
                       u.id as u_id,
                       sp.id as profile_id,
                       CONCAT(u.first_name," ",u.last_name) as name,
                       u.username,
                       u.email,
                       g.name as groupname,
                       ou.active & u.active as active,
                       sp.pay_level,
                       oup.id as office_profile_id,
                       sp.experience
                FROM pocomos_company_office_users ou
                JOIN orkestra_users u ON ou.user_id = u.id
                JOIN pocomos_company_office_user_profiles oup on ou.profile_id = oup.id
                JOIN pocomos_salesperson_profiles sp on sp.office_user_profile_id = oup.id
                LEFT JOIN orkestra_user_groups ug ON ug.user_id = u.id
               LEFT JOIN orkestra_groups g ON ug.group_id = g.id
                WHERE g.role IN ("ROLE_SALESPERSON","ROLE_SALES_MANAGER","ROLE_ROUTE_MANAGER")
                AND ou.office_id = ' . $officeId . '
                AND ou.deleted = 0';


        if ($request->search) {
            $search = '"%' . $request->search . '%"';

            $sql .= ' AND (CONCAT(u.first_name," ",u.last_name) LIKE ' . $search . '
                    OR u.username LIKE ' . $search . '
                    OR u.email LIKE ' . $search . '
                    OR g.name LIKE ' . $search . '
                    )';
        }

        if ($request->filter == 'active') {
            $sql .= ' AND u.active = 1 AND ou.active = 1';
        } elseif ($request->filter == 'inactive') {
            $sql .= ' AND (u.active = 0 OR ou.active = 0)';
        }

        $sql .= ' GROUP BY u.id';

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $result = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'List', [
            'List' => $result,
            'count' => $count,
        ]);
    }

    public function getFormData(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id', 'name']);

        $PocomosTimezones = PocomosTimezone::whereActive(1)->get();

        $roles = OrkestraGroup::whereIn('role', ['ROLE_SALESPERSON', 'ROLE_SALES_MANAGER', 'ROLE_ROUTE_MANAGER'])->get();

        return $this->sendResponse(true, 'Form data', [
            'offices' => $branches,
            'roles' => $roles,
            'timezones' => $PocomosTimezones,
        ]);
    }


    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id'         => 'required',
            'first_name'        => 'required',
            'last_name'         => 'required',
            'username'         => 'required|unique:orkestra_users',
            'email'         => 'nullable|email|unique:orkestra_users',
            'password'         => 'required',
            'active'         => 'required',
            'experience'         => 'numeric',
            'role_id'         => 'required',
        ], [
            'username.unique' => 'Username already exists',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $input['first_name'] = $request->first_name;
        $input['last_name'] = $request->last_name;
        $input['email'] = $request->email;
        $input['active'] =  $request->active;
        $input['expired'] = 0;
        $input['locked'] = 0;
        $password = $request->password;
        $input['password'] = bcrypt($password);
        $salt = '10';
        $input['salt'] = md5($salt . $password);
        $input['username'] = $request->username;
        $OrkestraUser =  OrkestraUser::create($input);
        $OrkestraUser['token'] =  $OrkestraUser->createToken('MyAuthApp')->plainTextToken;

        // if ($OrkestraFile && $PocomosPhoneNumber) {
        //     $input = [];
        // $input['photo_id'] = $OrkestraFile->id;
        // $input['phone_id'] = $PocomosPhoneNumber->id;
        $input['user_id'] = $OrkestraUser->id;
        $input['active'] = 1;
        // $input['default_office_user_id'] = $request->office_id;
        $UserProfile =  PocomosCompanyOfficeUserProfile::create($input);
        // }

        // if ($UserProfile) {
        // $input = [];
        $input['office_id'] = $request->office_id;
        $input['profile_id'] = $UserProfile->id;
        $input['user_id'] = $OrkestraUser->id;
        $input['active'] = 1;
        $input['deleted'] = 0;
        $OfficeUsers =  PocomosCompanyOfficeUser::create($input);
        // }

        $CertificationLevelId = PocomosVtpCertificationLevel::whereActive(true)->whereOfficeId($request->office_id)->firstOrFail()->id;

        $profile['office_user_profile_id'] = $UserProfile->id;
        $profile['experience'] = $request->experience;
        $profile['pay_level'] = $request->pay_level;
        $profile['active'] = 1;
        $profile['tagline'] = '';
        $profile['certification_level_id'] = $CertificationLevelId;
        PocomosSalespersonProfile::create($profile);

        $input['user_id'] = $OrkestraUser->id;
        $input['group_id'] = $request->role_id;
        OrkestraUserGroup::create($input);

        return $this->sendResponse(true, 'Sales person created successfully.', $OrkestraUser);
    }

    public function delete(Request $request, $ouId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        $officeUser = PocomosCompanyOfficeUser::with('user_details')->whereId($ouId)->first();
        $officeUser->active = 0;
        $officeUser->deleted = 1;
        $officeUser->save();

        if ($orkUser = $officeUser->user_details) {
            $orkUser->active = 0;
            $orkUser->email = null;
            $orkUser->username = md5($orkUser->username);
            $orkUser->save();
        }

        return $this->sendResponse(true, __('strings.delete', ['name' => 'Salesperson']));
    }

    public function edit(Request $request, $profileId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $PocomosSalespersonProfile = PocomosSalespersonProfile::with('certificateLevel:id,name')->whereId($profileId)->first();

        $PocomosVtpCertificationLevel = PocomosVtpCertificationLevel::whereActive(true)->whereOfficeId($request->office_id)->get();

        return $this->sendResponse(true, 'Form data', [
            'salesperson_profile' => $PocomosSalespersonProfile,
            'certificate_levels' => $PocomosVtpCertificationLevel,
        ]);
    }


    public function update(Request $request, $id)
    {
        $v = validator($request->all(), [
            'experience'        => 'required',
            'pay_level'       => 'required',
            'certification_level_id'   => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosSalespersonProfile = PocomosSalespersonProfile::whereId($id)->first();
        if (!$PocomosSalespersonProfile) {
            return $this->sendResponse(false, 'Salesperson Profile Not Found');
        }

        $PocomosSalespersonProfile->experience              = $request->experience;
        $PocomosSalespersonProfile->pay_level               = $request->pay_level;
        $PocomosSalespersonProfile->certification_level_id  = $request->certification_level_id;

        $PocomosSalespersonProfile->save();

        return $this->sendResponse(true, 'Salesperson profile updated successfully.');
    }


    public function show(Request $request, $uid)
    {
        $OfficeUser =  PocomosCompanyOfficeUser::with([
            'salespeople',
            'user_details',
            'profile_details.photo_details',
            'profile_details.phone_details',
            'profile_details.salesPersonProfile',
            'user_details.permissions',
            'company_details'
        ])->whereUserId($uid)->first();

        $offices = PocomosCompanyOfficeUser::with('company_details')->whereUserId($uid)
            ->whereActive(1)->get();

        return $this->sendResponse(true, 'User details', [
            'user' => $OfficeUser,
            'offices' => $offices,
        ]);
        /*
        api id = user>user_id
        if profile_details.signature_id ? On file : Not on file
        */
    }

    public function showAlerts(Request $request, $id)
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
        )->where('assigned_to_user_id', $find_assigned_by_to)->with('alert_details', 'assigned_by_details', 'assigned_to_details')->get();

        return $this->sendResponse(true, 'Alerts', $alerts);
    }

    public function addAlert(Request $request)
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

    public function updateAlert($id)
    {
        $alert = PocomosAlert::whereId($id)->first();
        $alert->status = 'Completed';
        $alert->save();

        return $this->sendResponse(true, 'Alert updated successfully');
    }

    public function showTasks(Request $request, $id)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:orkestra_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $request->user_id)->where('office_id', $request->office_id)->pluck('id')->toArray();
        // $alert = PocomosOfficeAlert::whereIn('assigned_to_user_id', $find_assigned_by_to)->with('todo_details', 'assigned_by_details', 'assigned_to_details')->get();

        // $all_leads = collect($alert);
        // // $leadsCollection = $all_leads->filter(function ($value) {
        // //     return !is_null($value->todo_details);
        // // })->values();
        // $all_leads->all();
        // return $this->sendResponse(true, 'Alerts Listeninig', $all_leads);

        $alerts = PocomosOfficeAlert::whereHas(
            'todo_details',
            function ($query) {
                $query->where('status', '!=', 'Completed');
            }
        )->where('assigned_to_user_id', $find_assigned_by_to)->with('todo_details', 'assigned_by_details', 'assigned_to_details')->get();

        return $this->sendResponse(true, 'Alerts', $alerts);
    }

    /* API for add taks */
    public function addTask(Request $request)
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

    public function updateTaskStatus(Request $request, $alertId)
    {
        $v = validator($request->all(), [
            // 'task_id' => 'required',
            'status' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $task = PocomosAlert::where('id', $alertId)->first();
        $task->status = $request->status;
        $task = $task->save();
        return $this->sendResponse(true, 'Task status updated', $task);
    }

    public function showNotes(Request $request, $id)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $notes = PocomosCompanyOfficeUserNote::where('office_user_id', $id)->join('pocomos_notes', 'pocomos_office_user_notes.note_id', '=', 'pocomos_notes.id')->join('orkestra_users', 'pocomos_notes.user_id', '=', 'orkestra_users.id')->orderBy('pocomos_notes.date_created', 'desc');

        if ($request->search) {
            $search = $request->search;
            $notes->where(function ($query) use ($search) {
                $query->where('summary', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('body', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $notes->count();
        $notes->skip($perPage * ($page - 1))->take($perPage);

        $notes = $notes->get();

        return $this->sendResponse(true, 'List', [
            'Notes' => $notes,
            'count' => $count,
        ]);
    }

    public function addNote(Request $request)
    {
        $v = validator($request->all(), [
            'assigned_by' => 'required|exists:orkestra_users,id',
            'assigned_to' => 'required|exists:orkestra_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'summary' => 'required',
            'body' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        // $input = [];
        $input['user_id'] = $request->assigned_by;
        $input['summary'] = $request->summary;
        $input['body'] = $request->body;
        $input['active'] = 1;
        $input['interaction_type'] = 'Other';
        $addNote = PocomosNote::create($input);
        $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $request->assigned_to)->where('office_id', $request->office_id)->first();
        // $pocomos_office_user_create = [];
        $pocomos_office_user_create['office_user_id'] = $find_assigned_by_to->id;
        $pocomos_office_user_create['note_id'] = $addNote->id;
        $pocomos_office_user_notes = PocomosCompanyOfficeUserNote::create($pocomos_office_user_create);
        return $this->sendResponse(true, 'Note added successfully', $pocomos_office_user_notes);
    }

    public function deleteNote($id)
    {
        $PocomosNote = PocomosNote::find($id);
        if (!$PocomosNote) {
            return $this->sendResponse(false, 'Unable to find the note.');
        }

        $PocomosCompanyOfficeUserNote = PocomosCompanyOfficeUserNote::where('note_id', $id)->delete();
        $PocomosNote->delete();

        return $this->sendResponse(true, 'Note deleted successfully.');
    }

    /**Get sales person user statistics details */
    public function getStatisticsReportDetails(Request $request)
    {
        $v = validator($request->all(), [
            'type' => 'required|in:salesperson,branch,company',
            'office_id' => 'nullable|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $type = $request->type;
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->whereUserId(auth()->user()->id)->first();
        $salesperson = PocomosSalesPeople::whereUserId($officeUser->id)->first();
        $office = PocomosCompanyOffice::find($request->office_id);
        $res = array();

        switch ($type) {
            case "salesperson":
                $res = $this->getSalespersonState($salesperson);
                break;
            case "branch":
                $res = $this->getBranchState($office);
                break;
            case "company":
                $res = $this->getCompanyState($office);
                break;
            default:
                break;
        }

        return $this->sendResponse(true, __('strings.list', ['name' => 'Statistics']), $res);
    }

    /**Edit sales person profile */
    public function editSalesProfile(Request $request)
    {
        $v = validator($request->all(), [
            'profile_pic' => 'nullable|mimes:png,jpg,jpeg',
            'tagline' => 'nullable',
            'summar_goal' => 'nullable',
            'daily_doal' => 'nullable',
            'weekly_goal' => 'nullable',
            'monthly_goal' => 'nullable',
            'commission_rate' => 'nullable',
            'experience' => 'nullable',
            'salespeople_id' => 'nullable|exists:pocomos_salespeople,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if ($request->salespeople_id) {
            $salesPeople = PocomosSalesPeople::find($request->salespeople_id);
            $officeUser = $salesPeople->office_user_details;
            $userProfile = $officeUser->profile_details;
        } else {
            $userId = auth()->user()->id;
            $officeUser = PocomosCompanyOfficeUser::whereUserId($userId)->firstOrFail();
            $salesPeople = PocomosSalesPeople::whereUserId($officeUser->id)->firstOrFail();
            $userProfile = PocomosCompanyOfficeUserProfile::whereUserId($userId)->firstOrFail();
        }

        $salesPersonProfile = PocomosSalespersonProfile::where('office_user_profile_id', $userProfile->id)->first();
        $commisionSetting = PocomosCommissionSetting::where('salesperson_id', $salesPeople->id)->first();

        $profileId = null;
        if ($request->profile_pic) {
            $profileId = $this->uploadFileOnS3('salesperson', $request->profile_pic);
            $profileDetails['profile_pic_id'] = $profileId;
        }

        $profileDetails['office_user_profile_id'] = $userProfile->id;
        $profileDetails['experience'] = $request->experience ?? '';
        $profileDetails['pay_level'] = 0;
        $profileDetails['active'] = true;
        $profileDetails['tagline'] = $request->tagline;

        if ($salesPersonProfile) {
            $salesPersonProfile->update($profileDetails);
        } else {
            PocomosSalespersonProfile::create($profileDetails);
        }

        $commisionDetail = [
            'goal' => $request->summar_goal,
            'daily_goal' => $request->daily_doal,
            'weekly_goal' => $request->weekly_goal,
            'monthly_goal' => $request->monthly_goal,
            'commission_percentage' => $request->commission_rate ?? 0,
        ];
        if ($commisionSetting) {
            $commisionSetting->update($commisionDetail);
        } else {
            $commisionDetail['salesperson_id'] = $salesPeople->id;
            $commisionDetail['last_day_summer'] = '2022-08-31 00:00:00';
            $commisionDetail['commission_percentage'] = 0.0;
            $commisionDetail['active'] = true;
            PocomosCommissionSetting::create($commisionDetail);
        }
        return $this->sendResponse(true, __('strings.update', ['name' => 'Sales profile']));
    }
    /**Get sales person profile */
    public function getSalesProfile(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'nullable|exists:pocomos_company_offices,id',
            'salespeople_id' => 'nullable|exists:pocomos_salespeople,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if ($request->salespeople_id) {
            $salesPeople = PocomosSalesPeople::find($request->salespeople_id);
            $officeUser = $salesPeople->office_user_details;
            $userProfile = $officeUser->profile_details;
        } else {
            $userId = auth()->user()->id;
            $officeUser = PocomosCompanyOfficeUser::whereUserId($userId)->firstOrFail();
            $salesPeople = PocomosSalesPeople::whereUserId($officeUser->id)->firstOrFail();
            $userProfile = PocomosCompanyOfficeUserProfile::whereUserId($userId)->firstOrFail();
        }
        $salesPersonProfile = PocomosSalespersonProfile::with('profile_pic', 'certificateLevel')->where('office_user_profile_id', $userProfile->id)->first();
        $commisionSetting = PocomosCommissionSetting::where('salesperson_id', $salesPeople->id)->first();
        $salestrackerOfficeSettings = PocomosSalestrackerOfficeSetting::whereOfficeId($request->office_id)->first();

        $salesPeople->user_details = $officeUser->user_details;
        $salesPeople->profile = $salesPersonProfile;
        $salesPeople->commision_setting = $commisionSetting;
        $salesPeople->salestracker_office_settings = $salestrackerOfficeSettings;

        return $this->sendResponse(true, __('strings.details', ['name' => 'Sales profile']), $salesPeople);
    }

    /**
     * Displays settings and form to manipulate calculation data
     *
     * @Secure(roles="ROLE_SALESPERSON")
     * @param Request $request
     * @return array
     */
    public function saveAndGetCalculatorDetail(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'current_accounts' => 'nullable',
            'average_initial' => 'nullable',
            'average_contract' => 'nullable',
            'commission_percentage' => 'nullable',
            'goal' => 'nullable',
            'last_day_of_summer' => 'nullable|date_format:Y-m-d',
            'bonuses' => 'array',
            'bonuses.*.name' => 'nullable',
            'bonuses.*.bonus_value' => 'nullable',
            'bonuses.*.accounts_needed' => 'nullable',
            'show_only_first_year' => 'boolean'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $userId = auth()->user()->id;
        $office_id = $request->office_id;
        $officeUser = PocomosCompanyOfficeUser::whereUserId($userId)->firstOrFail();
        $salesPeople = PocomosSalesPeople::whereUserId($officeUser->id)->firstOrFail();

        try {
            DB::beginTransaction();

            /**Update commission details */
            if ($request->last_day_of_summer || $request->goal || $request->commission_percentage) {
                $commisionDetails['last_day_summer'] = $request->last_day_of_summer ? date('Y-m-d H:i:s', strtotime($request->last_day_of_summer)) : date('Y-m-d H:i:s');
                $commisionDetails['goal'] = $request->goal ?? 0;
                $commisionDetails['commission_percentage'] = $request->commission_percentage ?? 0;
                PocomosCommissionSetting::where('salesperson_id', $salesPeople->id)->update($commisionDetails);
            }
            /**End */
            $commisionSetting = PocomosCommissionSetting::with('bonuse_details')->where('salesperson_id', $salesPeople->id)->first();

            /**Delete & Update commission bonus details */
            if ($request->bonuses) {
                $bonuses = array();
                $i = 0;
                PocomosCommissionBonuse::where('commission_settings_id', $commisionSetting->id)->delete();
                foreach ($request->bonuses as $value) {
                    $bonuses[$i]['commission_settings_id'] = $commisionSetting->id;
                    $bonuses[$i]['name'] = $value['name'];
                    $bonuses[$i]['accounts_needed'] = $value['accounts_needed'];
                    $bonuses[$i]['bonus_value'] = $value['bonus_value'];
                    $bonuses[$i]['active'] = true;
                    $bonuses[$i]['date_created'] = date("Y-m-d H:i:s");
                    $i = $i + 1;
                }
                PocomosCommissionBonuse::insert($bonuses);
            }

            $salestrackerOfficeSettings = PocomosSalestrackerOfficeSetting::whereOfficeId($request->office_id)->first();

            $bonuses = DB::select(DB::raw("SELECT ob.*
            FROM pocomos_office_bonuses AS ob
            WHERE ob.office_id = $office_id"));

            $numberOfCurrentAccounts = $request->current_accounts;
            $averageContractValue = $request->average_contract;

            $defaultSchedule = DB::select(DB::raw("SELECT s.*
            FROM pocomos_schedules AS s
            WHERE s.office_id = $office_id"));
            $defaultSchedule = $defaultSchedule[0] ?? array();

            $calculations = $this->calculateCommissions($commisionSetting, $numberOfCurrentAccounts, $averageContractValue, $defaultSchedule);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        $data = array(
            'commision_setting' => $commisionSetting,
            'calculations' => $calculations,
            'bonuses' => $bonuses,
        );

        return $this->sendResponse(true, __('strings.list', ['name' => 'Calcualte details']), $data);
    }

    /**
     * Lists all of the current user's Reserved Slots and Available Spots
     * @Secure(roles="ROLE_SALESPERSON")
     * @param Request $request
     * @return array
     */
    public function salesPersonSpots(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'date' => 'required|date_format:Y-m-d'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $userId = auth()->user()->id;
        $office_id = $request->office_id;
        $office = PocomosCompanyOffice::find($request->office_id);
        $config = PocomosPestOfficeSetting::whereOfficeId($request->office_id)->firstOrFail();
        $officeUser = PocomosCompanyOfficeUser::whereUserId($userId)->firstOrFail();
        $salesPeople = PocomosSalesPeople::whereUserId($officeUser->id)->firstOrFail();

        $date = $request->date;
        $dateNext = $this->getNextBusinessDayAfter($office, $date);

        $team = $this->getSalesPersonTeam($userId);

        $currentSpots = $this->getAvailableSpots($team, $date);
        $nextSpots = $this->getAvailableSpots($team, $dateNext);

        $blockedSpots = $this->getReservedSpots($officeUser);

        return $this->sendResponse(
            true,
            __('strings.list', ['name' => 'Spots']),
            array(
                'team' => $team,
                'date' => $date,
                'date_next' => $dateNext,
                'spots' => $currentSpots,
                'next_spots' => $nextSpots,
                'enable_blocking' => $config->enable_blocked_spots,
                'blocked_spots' => $blockedSpots,
            )
        );
    }

    /**
     * Gets the next open business day after the given date, within $limit days
     */
    public function getNextBusinessDayAfter($office, $date, $limit = 7)
    {
        $date = new \DateTime($date);

        for ($i = 0; $i < $limit; $i++) {
            $date->modify('+1 day');

            if ($this->getOfficeOverrideSchedule($office, $date) && $this->getOfficeOverrideSchedule($office, $date)->open) {
                return $date;
            }
        }

        return null;
    }

    public function completedAlertTaskHistory(Request $request, $id)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_assigned_by_to = PocomosCompanyOfficeUser::whereId($id)->first();
        $completedAlertsTasks = array();

        if ($find_assigned_by_to) {
            $completedAlertsTasks = PocomosOfficeAlert::has('alert_history_details', 'task_history_details')
                ->where('assigned_to_user_id', $find_assigned_by_to->id)
                ->with(
                    'alert_details_any',
                    'todo_details_any',
                    'alert_history_details',
                    'assigned_by_details',
                    'assigned_to_user_details'
                );
        }

        if ($request->search) {
            $search = $request->search;
            $date = date('Y-m-d', strtotime($search));

            $sql = "SELECT pa.id
            FROM pocomos_office_alerts AS poa
            JOIN pocomos_alerts AS pa ON poa.alert_id = pa.id
            LEFT JOIN pocomos_company_office_users AS pcou ON poa.assigned_by_user_id = pcou.id
            LEFT JOIN orkestra_users AS ou ON pcou.user_id = ou.id where (pa.name LIKE '%$search%' OR pa.description LIKE '%$search%'  OR pa.priority LIKE '%$search%' OR ou.first_name LIKE '%$search%' OR ou.last_name LIKE '%$search%' OR pa.date_due LIKE '%$date%') ";
            $alertTeampIds = DB::select(DB::raw($sql));

            $alertIds = array_map(function ($value) {
                return $value->id;
            }, $alertTeampIds);
            $completedAlertsTasks->whereIn('alert_id', $alertIds);
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $completedAlertsTasks->count();
        $completedAlertsTasks->skip($perPage * ($page - 1))->take($perPage);

        $completedAlertsTasks = $completedAlertsTasks->get();

        return $this->sendResponse(true, 'List', [
            'History' => $completedAlertsTasks,
            'count' => $count,
        ]);
    }

    /**Get sales person user statistics details */
    public function getStatisticsDetails(Request $request)
    {
        $v = validator($request->all(), [
            'salespeople_id' => 'required|exists:pocomos_salespeople,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $salesperson = PocomosSalesPeople::find($request->salespeople_id);

        $res = $this->getSalespersonState($salesperson);

        return $this->sendResponse(true, __('strings.list', ['name' => 'Statistics']), $res);
    }

    /**Configured Deductions APIs */

    /* API for list of Configured Deductions*/
    public function listCommissionDeduction(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'commission_settings_id' => 'required|exists:pocomos_commission_settings,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $commissionDeduction = PocomosCommissionDeduction::where('commission_settings_id', $request->commission_settings_id)->where('active', true);

        /**For pagination */
        $count = $commissionDeduction->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $commissionDeduction = $commissionDeduction->skip($perPage * ($page - 1))->take($perPage);
        }
        $commissionDeduction = $commissionDeduction->orderBy('id', 'desc')->get();

        $data = [
            'commission_deductions' => $commissionDeduction,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'commission deduction']), $data);
    }

    /* API for create Configured Deductions */
    public function createCommissionDeduction(Request $request)
    {
        $v = validator($request->all(), [
            'commission_settings_id' => 'required|exists:pocomos_commission_settings,id',
            'name' => 'required',
            'amount' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('commission_settings_id', 'name', 'amount') + ['active' => true];

        $commissionDeduction =  PocomosCommissionDeduction::create($input_details);

        return $this->sendResponse(true, __('strings.create', ['name' => 'Commission deduction']), $commissionDeduction);
    }

    /* API for update of Configured Deductions*/
    public function updateCommissionDeduction(Request $request, $id)
    {
        $v = validator($request->all(), [
            'name'          => 'required',
            'amount'        => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $commissionDeduction    = PocomosCommissionDeduction::findOrFail($id);
        $updateDetail           = $request->only('name', 'amount');

        $commissionDeduction->update($updateDetail);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Commission deduction']), $commissionDeduction);
    }

    /* API for delete of Configured Deductions*/
    public function deleteCommissionDeduction($id)
    {
        $commissionDeduction = PocomosCommissionDeduction::findOrFail($id)->delete();

        return $this->sendResponse(true, __('strings.delete', ['name' => 'Commission deduction']));
    }

    /**Configured Deductions APIs */



    public function regenerateStateAction($ouId)
    {
        // return $ouId;
        // $office = $this->getCurrentOffice();
        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        $user = PocomosCompanyOfficeUser::WhereId($ouId)->whereOfficeId($officeId)->first();

        if (!$user) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate User']));
        }

        $salespeople = PocomosSalesPeople::join('pocomos_company_office_users as pcou', 'pocomos_salespeople.user_id', 'pcou.id')
                ->where('pcou.profile_id', $user->profile_id)
                ->get();

        /* $ids = array_map(function (Salesperson $salesperson) {
            return $salesperson->getId();
        }, $salespeople);

        $resque = $this->get('terramar.resque');

        $job = new SalespersonStateJob();
        $job->queue = 'default';
        $job->args['ids'] = $ids;
        $resque->enqueue($job);

        return new JsonSuccessResponse('Successfully queued reporting job'); */
    }
}
