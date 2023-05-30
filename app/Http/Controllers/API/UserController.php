<?php

namespace App\Http\Controllers\API;

use DB;
use Auth;
use Hash;
use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Pocomos\PocomosEmail;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Orkestra\OrkestraGroup;
use App\Models\Pocomos\PocomosAddress;
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosRecruiter;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Orkestra\OrkestraUserGroup;
use App\Models\Orkestra\OrkestraCountry;
use App\Models\Pocomos\PocomosOfficeAlert;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosRecruiterRegions;
use App\Models\Pocomos\PocomosCommissionSetting;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosSalespersonProfile;
use App\Models\Pocomos\PocomosTechnicianLicenses;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosPestContractServiceType;
use App\Models\Pocomos\PocomosRecruitingOfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosRecruitOffice;
use App\Models\Pocomos\Recruitement\PocomosRegion;

class UserController extends Controller
{
    use Functions;

    /**
     * API for list of Employee
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    // public function __construct()
    //   {
    //       $this->middleware('auth');
    //   }

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // $find_users_id_from_office = PocomosCompanyOfficeUser::where('office_id', $request->office_id)
        //                                 ->whereDeleted(0)->get()->toArray();

        // $profile_ids = array_column($find_users_id_from_office, "profile_id");

        $user_ids = PocomosCompanyOfficeUser::where('office_id', $request->office_id)
            ->whereDeleted(0)->pluck('user_id')->toArray();

        $roles = ['ROLE_CUSTOMER'];

        // $roles = [
        //     'ROLE_ADMIN', 'ROLE_OWNER', 'ROLE_BRANCH_MANAGER', 'ROLE_SECRETARY', 'ROLE_TECHNICIAN',
        //     'ROLE_SALES_MANAGER', 'ROLE_SALES_ADMIN', 'ROLE_ROUTE_MANAGER', 'ROLE_COLLECTIONS', 'ROLE_TACKBOARD_HISTORY', 'ROLE_RECRUITER', 'ROLE_SALESPERSON'
        // ];

        $primaryRoles = OrkestraGroup::whereIn('role', $roles);

        $orkestraUsers = OrkestraUser::whereIn('id', $user_ids);

        if ($request->filter) {
            if ($request->filter == "active") {
                $orkestraUsers = $orkestraUsers->where('active', '1');
            }
            if ($request->filter == "Inactive") {
                $orkestraUsers = $orkestraUsers->where('active', '0');
            }
        }

        if ($request->search) {
            $search = $request->search;

            $primaryRoles = $primaryRoles->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('role', 'like', '%' . $search . '%');
            });

            if ($search == 'Active') {
                $search = 1;
            } elseif ($search == 'In Active') {
                $search = 0;
            }
            $searcArr = explode(' ', $search);
            $firstSearch = $searcArr[0] ?? $search;
            $secondSearch = $searcArr[1] ?? $search;

            $orkestraUsers = $orkestraUsers->where(function ($q) use ($search, $firstSearch, $secondSearch) {
                $q->where('first_name', 'like', '%' . $firstSearch . '%');
                $q->orWhere('last_name', 'like', '%' . $secondSearch . '%');
                $q->orWhere('last_login', 'like', '%' . $search . '%');
                $q->orWhere('email', 'like', '%' . $search . '%');
                $q->orWhere('active', 'like', '%' . $search . '%');
                $q->orWhere('username', 'like', '%' . $search . '%');
            });
        }

        $primaryRoles = $primaryRoles->pluck('id')->toArray();

        $groupUsrIds = OrkestraUserGroup::whereNotIn('group_id', $primaryRoles)->pluck('user_id')->toArray();
        $groupUsrIds = array_unique($groupUsrIds);

        $orkestraUsers = $orkestraUsers->with('permissions');

        if (OrkestraUser::with('permissions')->whereIn('id', $groupUsrIds)->count()) {
            $orkestraUsers = $orkestraUsers->whereIn('id', $groupUsrIds);
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $orkestraUsers->count();
        $orkestraUsers->skip($perPage * ($page - 1))->take($perPage);
        /**End */

        $orkestraUsers = $orkestraUsers->orderBy('id', 'desc')->get();

        $orkestraUsers->map(function ($user) {
            $findSalesPerson = PocomosSalesPeople::where('user_id', $user->id)->first();
            if ($findSalesPerson) {
                $user['isSalesPerson'] = true;
            } else {
                $user['isSalesPerson'] = false;
            }

            $PocomosTechnician = PocomosTechnician::where('user_id', $user->id)->first();
            if ($PocomosTechnician) {
                $user['isTechnician'] = true;
            } else {
                $user['isTechnician'] = false;
            }

            $recruiter = PocomosRecruiter::where('user_id', $user->id)->first();
            if ($PocomosTechnician) {
                $user['isRecruiter'] = true;
            } else {
                $user['isRecruiter'] = false;
            }

            $find_profile_id = PocomosCompanyOfficeUser::where('user_id', $user->id)->get('profile_id')->first();
            $find_profile_data = PocomosSalespersonProfile::where('office_user_profile_id', $find_profile_id->profile_id)->first();
            $user['other_data'] = $find_profile_data;
        });

        $data = [
            'users' => $orkestraUsers,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Users']), $data);
    }

    /* Add new Employee */
    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'username' => 'required|unique:orkestra_users,username',
            'email' => 'nullable',
            'phone_number' => 'nullable',
            'phone_type' => 'nullable',
            'password' => 'required|max:255',
            'photo' => 'nullable',
            'signature' => 'nullable',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input = [];
        $input['first_name'] = $request->first_name;
        $input['last_name'] = $request->last_name;
        $input['email'] = $request->email;
        $input['locked'] = 0;
        $input['active'] =  1;
        $input['expired'] = 0;
        $password = $request->password;
        $input['password'] = bcrypt($password);
        $salt = '10';
        $input['salt'] = md5($salt . $password);
        $input['username'] = $request->username ?? null;
        $OrkestraUser =  OrkestraUser::create($input);
        $OrkestraUser['token'] =  $OrkestraUser->createToken('MyAuthApp')->plainTextToken;

        if ($request->file('photo')) {
            $file = $request->file('photo');
            //store file into document folder
            $input_details['path'] = $file->store('public/files');

            //store your file into database
            $input_details['filename'] = $file->getClientOriginalName();
            $input_details['mime_type'] = $file->getMimeType();
            $input_details['file_size'] = $file->getSize();
            $input_details['active'] = 1;
            $input_details['md5_hash'] =  md5_file($file->getRealPath());
            $OrkestraFile =  OrkestraFile::create($input_details);
            $inputOfficeUserProfile['photo_id'] = $OrkestraFile->id;
        }

        if ($request->file('signature')) {
            $signature = $request->file('signature');                //store file into document folder
            $sign_detail['path'] = $signature->store('public/files');

            $sign_detail['filename'] = $signature->getClientOriginalName();
            $sign_detail['mime_type'] = $signature->getMimeType();
            $sign_detail['file_size'] = $signature->getSize();
            $sign_detail['active'] = 1;
            $sign_detail['md5_hash'] =  md5_file($signature->getRealPath());
            $agreement_sign =  OrkestraFile::create($sign_detail);
            $signed = true;
            $inputOfficeUserProfile['signature_id'] = $agreement_sign->id;
        }

        if (isset($request->phone_number)) {
            $inputphone = [];
            $inputphone['type'] = $request->phone_type ?? 'Mobile';
            $inputphone['number'] = $request->phone_number;
            $inputphone['alias'] = $request->alias ?? 'null';
            $inputphone['active'] = 1;
            $PocomosPhoneNumber =  PocomosPhoneNumber::create($inputphone);
            $inputOfficeUserProfile['phone_id'] = $PocomosPhoneNumber->id;
        }

        $inputOfficeUserProfile['user_id'] = $OrkestraUser->id;
        $inputOfficeUserProfile['active'] = 1;

        $UserProfile =  PocomosCompanyOfficeUserProfile::create($inputOfficeUserProfile);

        // if (isset($UserProfile)) {
        $input = [];
        $input['office_id'] = $request->office_id;
        $input['profile_id'] = $UserProfile->id;
        $input['user_id'] = $OrkestraUser->id;
        $input['active'] = 1;
        $input['deleted'] = 0;
        $OfficeUsers =  PocomosCompanyOfficeUser::create($input);
        $inputOfficeUserProfile['default_office_user_id'] = $OfficeUsers->id;
        // }

        $message_role = ['ROLE_TACKBOARD_ALERTS', 'ROLE_TACKBOARD_TODOS', 'ROLE_TACKBOARD_NOTES', 'ROLE_TACKBOARD_HISTORY'];
        $find_message_ids = OrkestraGroup::whereIn('role', $message_role)->pluck('id');
        $user_id = $OrkestraUser->id;
        foreach ($find_message_ids as $permission) {
            $input = [];
            $input['user_id'] = $user_id;
            $input['group_id'] = $permission;
            OrkestraUserGroup::create($input);
        }
        return $this->sendResponse(true, 'Employee created successfully.', $OrkestraUser);
    }

    public function uploadPhoto(Request $request, $profileId)
    {
        if ($request->photo) {
            $orkFileId = $this->uploadFile($request->photo);

            PocomosCompanyOfficeUserProfile::whereId($profileId)->firstorfail()->update([
                'photo_id' => $orkFileId
            ]);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'User profile updated']));
    }

    public function captureSignature(Request $request, $profileId)
    {
        if ($request->signature) {
            $orkFileId = $this->uploadFile($request->signature);

            PocomosCompanyOfficeUserProfile::whereId($profileId)->firstorfail()->update([
                'signature_id' => $orkFileId
            ]);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'User profile updated']));
    }

    public function deactivate(Request $request, $profileId)
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

        $officeUserProfile = PocomosCompanyOfficeUserProfile::with('user_details')
            ->whereId($profileId)->firstorfail();

        $officeUserProfile->user_details->update(['active' => 0]);

        $userId = PocomosCompanyOfficeUser::whereProfileId($profileId)->whereOfficeId($officeId)->firstorfail()->user_id;

        $technician = PocomosTechnician::select('pocomos_technicians.id')
            ->join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
            ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            // ->where('ou.id', $userId)
            ->where('pocomos_technicians.active', 1)
            ->first();

        if ($technician) {
            $pestContracts = PocomosPestContract::join('pocomos_technicians as pt', 'pocomos_pest_contracts.technician_id', 'pt.id')
                ->join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
                ->join('pocomos_agreements as pa', 'pc.agreement_id', 'pa.id')
                ->where('pt.id', $technician->id)
                ->where('pa.office_id', $officeId)
                ->get();

            //    return  $pestContracts;

            foreach ($pestContracts as $contract) {
                $contract->update(['technician_id' => null]);
            }
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'The user has been deactivated']));
    }

    public function checkUsername(Request $request)
    {
        $v = validator($request->all(), [
            'username' => 'required',
            'profile_id' => 'nullable|exists:pocomos_company_office_user_profiles,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $profileId = $request->profile_id;

        $sql = 'SELECT u.id
                FROM orkestra_users u
                  LEFT JOIN pocomos_company_office_user_profiles oup ON oup.user_id = u.id
                WHERE u.username = "' . $request->username . '"';

        if ($profileId) {
            $sql .= ' AND oup.id <> ' . $profileId . '';
        }

        $duplicates = DB::select(DB::raw($sql));

        // return $duplicates;


        $data['results'] = (count($duplicates) ? 1 : 0);
        return $this->sendResponse(true, __('strings.details', ['name' => 'Username exist']), $data);
    }

    /* update Employee */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'profileId' => 'required|exists:pocomos_company_office_user_profiles,id',
            'profileExternalId' => 'integer|min:1',
            'first_name' => 'nullable',
            'last_name' => 'nullable',
            'email' => 'nullable',
            'phone_number' => 'nullable',
            'phone_type' => 'nullable',
            'username' => 'nullable',
            'pp_username' => 'nullable|max:10',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeUserProfile = PocomosCompanyOfficeUserProfile::whereId($request->profileId)->firstorfail();

        // return $officeUserProfile;

        $OrkestraUser = OrkestraUser::findOrFail($officeUserProfile->user_id);

        if (!$OrkestraUser) {
            return $this->sendResponse(false, 'User not found.');
        }

        $OrkestraUser->update(
            $request->only('first_name', 'last_name', 'email', 'username')
        );

        if ($request->phone_number && $officeUserProfile->phone_id == null) {
            $inputphone = [];
            $inputphone['type'] = $request->phone_type;
            $inputphone['number'] = $request->phone_number;
            $inputphone['alias'] = '';
            $inputphone['active'] = 1;
            $createdPhoneNumber =  PocomosPhoneNumber::create($inputphone);

            $officeUserProfile->update(['phone_id' => $createdPhoneNumber->id]);
        } elseif ($request->phone_number) {
            $PocomosPhoneNumber = PocomosPhoneNumber::whereId($officeUserProfile->phone_id)
                ->update([
                    'type' => $request->phone_type,
                    'number' => $request->phone_number,
                ]);
        } else {
            // if entered field is blank
            $PocomosPhoneNumber = PocomosPhoneNumber::whereId($officeUserProfile->phone_id)
                ->update([
                    'number' => '',
                ]);

            $officeUserProfile->update(['phone_id' => null]);
        }

        $officeUserProfile->update([
            // 'profile_external_id'=> $request->profileExternalId,    //column missing
            'pp_username' => $request->pp_username,
        ]);

        return $this->sendResponse(true, 'User updated successfully.', $OrkestraUser);
    }

    /* delete Employee */

    public function delete($id)
    {
        $companyOfficeUserProfile = PocomosCompanyOfficeUserProfile::where('user_id', $id)
            ->first();

        if ($companyOfficeUserProfile) {
            // return $companyOfficeUserProfile;
            $officeUser = PocomosCompanyOfficeUser::whereProfileId($companyOfficeUserProfile->id)->update([
                'active' => 0,
                'deleted' => 1
            ]);
        }

        $OrkestraUser = OrkestraUser::findOrFail($id);
        if (!$OrkestraUser) {
            return $this->sendResponse(false, 'User not found.');
        }

        $OrkestraUser->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'User deleted successfully.');
    }

    /* reset password for Employee */

    public function resetpassword(Request $request)
    {
        $v = validator($request->all(), [
            'password' => 'required',
            'confirm_password' => 'required',
            'profileId' => 'required|exists:orkestra_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $OrkestraUser = OrkestraUser::findOrFail($request->profileId);
        if (!$OrkestraUser) {
            return $this->sendResponse(false, 'User not found.');
        }

        $password = $request->password;
        $input['password'] = Hash::make($password);
        $salt = '10';
        $input['salt'] = md5($salt . $password);

        $OrkestraUser->update($input);


        return $this->sendResponse(true, 'Password reset successfully.');
    }

    /* API for change Status of user */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'profileId' => 'required|exists:orkestra_users,id',
            'active' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $OrkestraUser = OrkestraUser::findOrFail($request->profileId);
        if (!$OrkestraUser) {
            return $this->sendResponse(false, 'User not found');
        }

        if ($request->active == 1) {
            $OrkestraUser->update([
                'active' => $request->active
            ]);

            return $this->sendResponse(true, 'The user has been activated successfully.');
        }

        if ($request->active == 0) {
            $OrkestraUser->update([
                'active' => $request->active
            ]);

            return $this->sendResponse(true, 'The user has been deactivated successfully.');
        }
    }

    /* API for status of salesperson profile*/
    public function editSalesPersonProfile($ouId)
    {
        $offices = PocomosCompanyOfficeUser::with('company_details')->whereUserId($ouId)
            ->whereActive(1)->get();

        $officeUserIds = $offices->pluck('id');

        $salespeople = PocomosSalesPeople::with('commission_setting')->whereIn('user_id', $officeUserIds)->get();

        // $input = [];
        // if ($salespeople) {
        //     $input['toptwenty'] = $salespeople->toptwenty; // missing column in test server
        //     $commissionSetting =  PocomosCommissionSetting::where('salesperson_id', $salespeople->id)->first();
        //     $input['commission_percentage'] = isset($commissionSetting) ? $commissionSetting->commission_percentage : 0;
        //     $input['active'] = $salespeople->active;
        // }

        return $this->sendResponse(true, 'Salesperson Profile.', [
            'salespeople' => $salespeople,
            // 'input' => $input,
            'offices' => $offices,
        ]);
        //Salesperson for this office  = active of salespeople
    }

    /* API for change Status of Salesperson profile */
    public function changeSalesPersonProfile(Request $request, $ouId)
    {
        $v = validator($request->all(), [
            // 'toptwenty' => 'boolean',
            // 'active' => 'boolean',
            'data.*.commission_percentage' => 'required',
        ], [
            'data.*.commission_percentage.required' => 'Commission percantage field is required',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $data = $request->data;
        $roleAssign = false;
        foreach ($data as $d) {
            // return $d['id'];
            $salesPerson = PocomosSalesPeople::with('commission_setting')->where('user_id', $d['id'])->first();

            $company_office_user_profiles_id = PocomosCompanyOfficeUserProfile::where('user_id', $d['id'])
                ->pluck('id')->first();
            // dd($company_office_user_profiles_id);

            if ($salesPerson) {
                // return 1111;

                $salesPerson->update([
                    'toptwenty' => $d['toptwenty'],
                    'active' => $d['active'],
                ]);

                // return $salesPerson->commission_setting;

                $commissionSetting =  PocomosCommissionSetting::where('salesperson_id', $salesPerson->id)->first();

                if ($salesPerson->commission_setting) {
                    $salesPerson->commission_setting->update([
                        'commission_percentage' => $d['commission_percentage']
                    ]);
                // return 22;
                } else {
                    $commission = [];
                    $commission['commission_percentage'] = $d['commission_percentage'];
                    $commission['salesperson_id'] = $salesPerson->id;
                    $commission['last_day_summer'] = date('Y-m-d');
                    $commission['goal'] = 0;
                    $commission['daily_goal'] = 0;
                    $commission['weekly_goal'] = 0;
                    $commission['monthly_goal'] = 0;
                    $commission['active'] = 1;
                    $commissionSetting =  PocomosCommissionSetting::create($commission);
                }
                $messge = "updated";
            } else {
                // return 11;
                $input = [];
                $input['toptwenty'] = $d['toptwenty'];
                $input['user_id'] = $d['id'];
                $input['active'] = $d['active'];
                $SalesPerson =  PocomosSalesPeople::create($input);
                $commission = [];
                $commission['commission_percentage'] = $d['commission_percentage'];
                $commission['salesperson_id'] = $SalesPerson->id;
                $commission['last_day_summer'] = date('Y-m-d');
                $commission['goal'] = 0;
                $commission['daily_goal'] = 0;
                $commission['weekly_goal'] = 0;
                $commission['monthly_goal'] = 0;
                $commission['active'] = 1;
                $commissionSetting =  PocomosCommissionSetting::create($commission);
                $messge = "created";

                $profile = [];
                $profile['office_user_profile_id'] = $company_office_user_profiles_id;
                $profile['experience'] = '0';
                $profile['pay_level'] = '1';
                $profile['active'] = '1';
                $profile['tagline'] = '';
                $PocomosSalespersonProfile =  PocomosSalespersonProfile::create($profile);
            }

            if ($d['active']) {
                $roleAssign = true;
            }
        }

        /**ASSIGN/ UNASSIGN ROLES */
        $officeUser = PocomosCompanyOfficeUser::whereUserId($ouId)->first();
        if ($officeUser) {
            $user = $officeUser->user_details;
            if ($user) {
                if ($roleAssign) {
                    $this->assignRole($user, 'ROLE_SALESPERSON');
                    $updateTech['active'] = true;
                } else {
                    $this->unassignRole($user, 'ROLE_SALESPERSON');
                    $updateTech['active'] = false;
                }
                PocomosSalesPeople::whereId($SalesPerson->id)->update($updateTech);
            }
        }

        return $this->sendResponse(true, 'User profile ' . $messge . ' successfully.');
    }


    /* API for status of salesperson profile*/
    public function editTechnicianProfile(Request $request, $userId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $techProfiles = PocomosCompanyOfficeUser::with(
            'company_details',
            'technician_user_details',
            'technician_user_details.routing_address',
            'technician_user_details.licenses',
            'pest_contract_service_types'
        )
            ->whereUserId($userId)
            ->whereActive(1)->get();

        $bio = PocomosCompanyOfficeUserProfile::whereUserId($userId)->firstorfail()->bio;

        $countryRegions = OrkestraCountry::with('countryregion')->get();

        return $this->sendResponse(true, 'Technician Profile.', [
            'tech_profiles' => $techProfiles,
            'bio' => $bio,
            // 'country_regions' => $countryRegions,
        ]);

        /*
        company_details for multiple offices

        Technician for this office = active of pocomos_technicians
        Commission type, Commission value of pocomos_technicians

        */
    }


    /* API for change Status of Technician profile */
    public function changeTechnicianProfile(Request $request)
    {
        $v = validator($request->all(), [
            'uid' => 'required|exists:orkestra_users,id',
            'tech_profiles' => 'required|array',
            'tech_profiles.*.id' => 'required|exists:pocomos_company_office_users,id',
            'tech_profiles.*.active' => 'required|boolean',
            'tech_profiles.*.color' => 'required',
            'tech_profiles.*.commission_type' => 'required',
            'tech_profiles.*.commission_value' => 'required',
            'tech_profiles.*.region_id' => 'nullable|exists:orkestra_countries_regions,id',
            'tech_profiles.*.licenses_active' => 'required|array',
            'tech_profiles.*.licenses_active.*.service_id' => 'nullable|exists:pocomos_pest_contract_service_types,id',
            'tech_profiles.*.licenses_active.*.active' => 'required|boolean',
            'tech_profiles.*.licenses_active.*.licenses_number' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeUserProfile = PocomosCompanyOfficeUserProfile::whereUserId($request->uid)->first();
        if ($officeUserProfile) {
            $officeUserProfile->update(['bio' => $request->bio]);
        }

        $roleAssign = false;
        foreach ($request->tech_profiles as $q) {
            $PocomosTechnician = PocomosTechnician::where('user_id', $q['id'])->first();
            if ($PocomosTechnician) {
                // return 33;
                $PocomosTechnician->update([
                    'active' =>           $q['active'],
                    'color' =>            $q['color'] ?? '',
                    'commission_type' =>  $q['commission_type'],
                    'commission_value' => $q['commission_value'],
                ]);
                $PocomosAddress =  PocomosAddress::where('id', $PocomosTechnician->routing_address_id)->first();

                // $qq = $q['street'] ? $q['street'] : '';

                $PocomosAddress->update([
                    'street' =>  $q['street'] ?? '',
                    'suite' => $q['suite'] ?? '',
                    'city' => $q['city'] ?? '',
                    'postal_code' => $q['postal_code'] ?? '',
                    'region_id' => $q['region_id'] ?? null,
                    'active' => 1,
                    'valid' => 1,
                    'validated' => 1,
                ]);

                foreach ($q['licenses_active'] as $key => $license) {
                    // return $license;
                    $technicianLicense =  PocomosTechnicianLicenses::updateOrCreate(
                        [
                            'technician_id' => $PocomosTechnician->id,
                            'service_type_id' => $license['service_id'] ?? null,
                        ],
                        [
                            'license_number' =>  $license['licenses_number'] ?? '',
                            'active' => $license['active'],
                        ]
                    );
                }
                $messge = "updated";
                $techId = $PocomosTechnician->id;
            } else {
                // return 3113;

                $PocomosAddress =  PocomosAddress::create([
                    'street' =>  $q['street'] ?? '',
                    'suite' => $q['suite'] ?? '',
                    'city' => $q['city'] ?? '',
                    'postal_code' => $q['postal_code'] ?? '',
                    'region_id' => $q['region_id'] ?? null,
                    'active' => 1,
                    'valid' => 1,
                    'validated' => 1,
                ]);

                $input = [];
                $input['active'] = $q['active'];
                $input['user_id'] = $q['id'];
                $input['color'] = $q['color'];
                $input['commission_type'] = $q['commission_type'];
                $input['commission_value'] = $q['commission_value'];
                $input['routing_address_id'] = $PocomosAddress->id;
                $technicianPerson =  PocomosTechnician::create($input);
                $techId = $technicianPerson->id;

                foreach ($q['licenses_active'] as $key => $licenses) {
                    // return $technicianPerson->id;

                    $license_data = [];
                    $license_data['technician_id'] = $techId;
                    $license_data['service_type_id'] = $licenses['service_id'] ?? null;
                    $license_data['license_number'] = $licenses['licenses_number'] ?? '';
                    $license_data['active'] = $licenses['active'];
                    $technicianPerson =  PocomosTechnicianLicenses::create($license_data);
                }
                $messge = "created";
            }

            if ($q['active']) {
                $roleAssign = true;
            }
        }

        /**ASSIGN/ UNASSIGN ROLES */
        $officeUser = PocomosCompanyOfficeUser::whereUserId($request->uid)->first();
        if ($officeUser) {
            $user = $officeUser->user_details;
            if ($user) {
                if ($roleAssign) {
                    $this->assignRole($user, 'ROLE_TECHNICIAN');
                    $updateTech['active'] = true;
                } else {
                    $this->unassignRole($user, 'ROLE_TECHNICIAN');
                    $updateTech['active'] = false;
                }
                PocomosTechnician::whereId($techId)->update($updateTech);
            }
        }

        /*
        Technician for this office = active
        */
        return $this->sendResponse(true, 'User profile ' . $messge . ' successfully.');
    }

    /* API for status of Recruiter profile*/
    public function editRecruiterPersonProfile(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeName = PocomosCompanyOffice::whereId($request->office_id)->firstorfail()->name;

        $recruitConfigId = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)->firstOrFail()->id;

        $recruitOffices = PocomosRecruitOffice::whereOfficeConfigurationId($recruitConfigId)->get();

        $recruitRegions = PocomosRegion::whereOfficeConfigurationId($recruitConfigId)->get();

        $recruiter = PocomosRecruiter::where('user_id', $id)->first();

        if ($recruiter) {
            $recruiterRegions =  PocomosRecruiterRegions::where('recruiter_id', $recruiter->id)->get();
        }

        return $this->sendResponse(true, 'Recruiter Profile.', [
            'office_name' => $officeName,
            'recruiter' => $recruiter,
            'recruiter_regions' => $recruiterRegions ?? null,
            'recruit_offices' => $recruitOffices,
            'recruit_regions' => $recruitRegions,
        ]);

        /*
        Recruiter for this office will be unchecked if recruiter is null or active=0
        */
    }

    /* API for change Status of Recruiter profile */
    public function changeRecruiterProfile(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:pocomos_company_office_users,id',
            'active' => 'required|boolean',
            'default_office_id' => 'nullable|exists:pocomos_recruiting_offices,id',
            'regional' => 'required|boolean',
            'region_id' => 'nullable|array|exists:orkestra_countries_regions,id',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $recruiter = PocomosRecruiter::where('user_id', $request->user_id)->first();
        if ($recruiter) {
            // return 1;
            $recruiter->update(
                $request->only('active', 'default_office_id', 'regional')
            );
            if ($request->regional || $request->active == 0) {
                $find_regions = PocomosRecruiterRegions::where('recruiter_id', $recruiter->id)->delete();

                //pass region_id=[] if Recruiter for this office is unchecked
                foreach ($request->region_id as $region) {
                    $license_data = [];
                    $license_data['region_id'] = $region;
                    $license_data['recruiter_id'] = $recruiter->id;
                    $technicianPerson =  PocomosRecruiterRegions::create($license_data);
                }
            }
            $messge = "updated";
        } else {
            // return 111;

            $input = [];
            $input['active'] = $request->active;
            $input['user_id'] = $request->user_id;
            $input['regional'] = $request->regional;
            $input['default_office_id'] = $request->default_office_id;
            $recruiter =  PocomosRecruiter::create($input);
            if ($request->regional) {
                foreach ($request->region_id as $region) {
                    $license_data = [];
                    $license_data['region_id'] = $region;
                    $license_data['recruiter_id'] = $recruiter->id;
                    $technicianPerson =  PocomosRecruiterRegions::create($license_data);
                }
            }
            $messge = "created";
        }
        return $this->sendResponse(true, 'User profile ' . $messge . ' successfully.');
    }

    public function editAssignedOffice(Request $request, $profileId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $branches = PocomosCompanyOffice::whereId($request->office_id)->orWhere('parent_id', $request->office_id)->get(['id', 'name']);

        $officeUserProfile = PocomosCompanyOfficeUserProfile::with([
            'defaultOfficeUser.company_details',
            'pocomosuserprofiles',
        ])
            ->whereId($profileId)->first();

        return $this->sendResponse(true, 'Assigned offices', [
            'officeUserProfile' => $officeUserProfile,
            'branches' => $branches,
        ]);
    }


    /* API for assigned office */
    public function updateAssignedOffice(Request $request)
    {
        $v = validator($request->all(), [
            'default_office' => 'required|exists:pocomos_company_offices,id',
            'user_id' => 'required|exists:orkestra_users,id',
            'profile_id' => 'required|exists:pocomos_company_office_user_profiles,id',
            'offices' => 'required|array',
            'offices.*.office_id' => 'required',
            'offices.*.active' => 'required',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $defaultOffice = PocomosCompanyOfficeUserProfile::whereId($request->profile_id)->first();

        $officeUser = PocomosCompanyOfficeUser::whereProfileId($request->profile_id)->whereOfficeId($request->default_office)->first();

        if (!$officeUser) {
            $input = [];
            $input['office_id'] = $request->default_office;
            $input['active'] = 1;
            $input['user_id'] = $request->user_id;
            $input['profile_id'] = $request->profile_id;
            $input['deleted'] = 0;
            $officeUser = PocomosCompanyOfficeUser::create($input);
        }

        $defaultOffice->update(['default_office_user_id' => $officeUser->id]);

        // if ($request->default_office != $defaultOffice['default_office_user_id']) {
        //     $defaultOffice['default_office_user_id'] = $officeUser->id;
        //     $defaultOffice->save();

        //     $input = [];
        //     $input['office_id'] = $request->default_office;
        //     $input['active'] = 1;
        //     $input['user_id'] = $request->user_id;
        //     $input['deleted'] = 0;
        //     $createOffice = PocomosCompanyOfficeUser::create($input);

        //     PocomosCompanyOfficeUser::firstOrCreate(
        //         [
        //             'office_id' => $request->default_office,
        //             'user_id' => $request->user_id,
        //             'profile_id' => $request->profile_id,
        //         ],
        //         // ['title' => $request->title, 'body' => $request->body]
        //     );
        // }

        // return $request->offices;

        foreach ($request->offices as $key => $office) {
            $getOffice = PocomosCompanyOfficeUser::whereUserId($request->user_id)->whereProfileId($request->profile_id)
                ->whereOfficeId($office['office_id'])->first();
            if ($getOffice) {
                // return 2;
                $getOffice->active = $office['active'];
                $getOffice->save();
            } else {
                $input = [];
                $input['office_id'] = $office['office_id'];
                $input['active'] = $office['active'];
                $input['user_id'] = $request->user_id;
                $input['profile_id'] = $request->profile_id;
                $input['deleted'] = 0;
                $createOffice = PocomosCompanyOfficeUser::create($input);
            }
        }
        return $this->sendResponse(true, 'Office assigned successfully!');
    }

    /* API for list of all Technicians */
    public function techniciansAndSalesPerson(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $find_technicians = PocomosCompanyOfficeUser::where('office_id', $request->office_id)->with('user_details.technicians', 'user_details.salesPerson')->get();
        return $this->sendResponse(true, 'List of all technicians and sales person', $find_technicians);
    }

    /* API for Alerts*/
    public function addAlert(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'required|exists:orkestra_users,id',
            'description' => 'required',
            'priority' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $pocomos_office_alert_create = $this->createToDo($request, 'Alert', 'Alert');
        // $find_pocomos_company_office_users = PocomosCompanyOfficeUser::query();
        // $find_assigned_by_id = PocomosCompanyOfficeUser::where('user_id',$request->assignd_by)->first();
        // $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id',$request->assign_to)->first();
        // $input['name'] = 'Alert';
        // $input['description'] = $request->description;
        // $input['priority'] = $request->priority;
        // $input['status'] = 'Posted';
        // $input['type'] = 'Alert';
        // $input['active'] = 1;
        // $input['notified'] = 1;
        // $alert = PocomosAlert::create($input);

        // $pocomos_office_alert = [];
        // $pocomos_office_alert['alert_id'] = $alert->id;
        // $pocomos_office_alert['assigned_by_user_id'] = $find_assigned_by_id->id;
        // $pocomos_office_alert['assigned_to_user_id'] = $find_assigned_by_to->id;
        // $pocomos_office_alert['active'] = '1';
        // $pocomos_office_alert_create = PocomosOfficeAlert::create($pocomos_office_alert);
        return $this->sendResponse(true, 'Alert created successfully', $pocomos_office_alert_create);
    }

    /* API for Alerts Listing*/
    public function alertListing(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:orkestra_users,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $request->user_id)->first();

        $officeAlerts = PocomosOfficeAlert::has('alert_details')
            ->where('assigned_to_user_id', $find_assigned_by_to->id)
            ->with('alert_details', 'assigned_by_details', 'assigned_to_details')
            ->orderBy('id', 'desc')->get();

        // $all_leads = collect($alert);
        // $leadsCollection = $all_leads->filter(function ($value) {
        //     return !is_null($value->alert_details);
        // })->values();
        // $leadsCollection->all();

        /**For pagination */
        // $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        // $page    = $paginateDetails['page'];
        // $perPage = $paginateDetails['perPage'];
        // $count = $officeAlerts->count();
        // $officeAlerts->skip($perPage * ($page - 1))->take($perPage);

        // $officeAlerts = $officeAlerts->get();

        return $this->sendResponse(true, 'Alerts Listing.', $officeAlerts);
    }

    /* API for permission */
    public function editPermission(Request $request, $userId)
    {
        $v = validator($request->all(), [
            // 'user_id' => 'required',
            // 'permissions' => 'required|array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $roles = ['ROLE_ADMIN', 'ROLE_OWNER', 'ROLE_BRANCH_MANAGER', 'ROLE_SECRETARY', 'ROLE_SALES_MANAGER', 'ROLE_SALES_ADMIN', 'ROLE_ROUTE_MANAGER', 'ROLE_COLLECTIONS'];

        $primaryRoles = OrkestraGroup::whereIn('role', $roles)->get();

        $userPrimaryRole = OrkestraUserGroup::with('permission')->whereHas(
            'permission',
            function ($query) use ($roles) {
                $query->whereIn('role', $roles);
            }
        )->whereUserId($userId)->get();

        $userRoles = OrkestraUserGroup::with('permission')->whereUserId($userId)->get();

        $extRoles = [
            'ROLE_ROUTE_READ',
            'ROLE_ROUTE_VIEWADD',
            'ROLE_JOB_CANCEL',
            'ROLE_JOB_RESCHEDULE',
            'ROLE_GEO_CODE',
            'ROLE_EDIT_CUSTOMER_ID',
            'ROLE_RECRUIT_LINKER',
            'ROLE_RECRUIT_DELETE',
            'ROLE_ACCOUNT_NOTES',
            'ROLE_CHANGE_SERVICE_PRICE',
            'ROLE_LEAD_ALERT',
            'ROLE_HIDE_SETTING',
            'ROLE_HIDE_REPORTS',
            'ROLE_HIDE_FINANCIAL',
            'ROLE_HIDE_SALES_TRACKER',
            'ROLE_HIDE_VTP',
            'ROLE_RECEIVE_INBOUND_SMS',
            'ROLE_RECEIVE_CUSTOM_CONTRACT_ENDING_NOTIFICATION',
            'ROLE_ALERT_ONLINE_BOOKING',
            'ROLE_ALLOW_ADD_ZIPCODE',
            'ROLE_USER_VIEWADD',
            'ROLE_FULL_ACCOUNT_NUMBER',
            'ROLE_SHOW_ALL_PINS_LEAD_MAP',
            'ROLE_WDO_TOOLS',
            'ROLE_ONLINE_BOOKING',
            'ROLE_TECH_RESTRICTED'
        ];

        $extraRoles = OrkestraGroup::whereIn('role', $extRoles)->get();

        /*
            View/Add Routes page>View Only = ROLE_ROUTE_READ
            View/Add Routes page>Full Access = ROLE_ROUTE_VIEWADD
        */

        return $this->sendResponse(true, 'Permissions', [
            'user_primary_role' => $userPrimaryRole,
            'user_roles' => $userRoles,
            'primary_roles' => $primaryRoles,
            'extra_roles' => $extraRoles
        ]);
    }

    /* API for permission */
    public function updatePermission(Request $request, $userId)
    {
        $v = validator($request->all(), [
            // 'permissions' => 'required|array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $message_role = ['ROLE_TACKBOARD_ALERTS', 'ROLE_TACKBOARD_TODOS', 'ROLE_TACKBOARD_NOTES', 'ROLE_TACKBOARD_HISTORY'];
        $find_message_ids = OrkestraGroup::whereIn('role', $message_role)->pluck('id');
        $delete_old_permission = OrkestraUserGroup::whereNotIn('group_id', $find_message_ids)->where('user_id', $userId)->delete();

        if ($request->primary_role) {
            $input = [];
            $input['user_id'] = $userId;
            $input['group_id'] = $request->primary_role;
            OrkestraUserGroup::create($input);
        }

        $find_permission_ids = OrkestraGroup::whereIn('id', $request->permissions)->get();
        foreach ($find_permission_ids as $permission) {
            $input = [];
            $input['user_id'] = $userId;
            $input['group_id'] = $permission->id;
            OrkestraUserGroup::create($input);
        }
        return $this->sendResponse(true, 'Permission updated Successfully!');
    }

    /* API for permission */
    public function updatePermissionNew(Request $request, $userId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'role' => 'required|array',
            'role.role' => 'nullable',
            'role.viewAddRoutePage' => 'required|boolean',
            'role.viewAddRoutePageRole' => 'nullable|in:ROLE_ROUTE_READ,ROLE_ROUTE_VIEWADD',
            'role.manageAgreement' => 'required|boolean',
            'role.manageAgreementRole' => 'nullable|in:ROLE_FULL_AGREEMENT,ROLE_EDIT_AGREEMENT,ROLE_VIEW_AGREEMENT,ROLE_ARRANGE_ORDER_AGREEMENT',
            'role.manageServiceType' => 'required|boolean',
            'role.manageServiceTypeRole' => 'nullable|in:ROLE_ARRANGE_ORDER_SERVICE_TYPE,ROLE_FULL_SERVICE_TYPE,ROLE_VIEW_SERVICE_TYPE',
            'role.managePestProduct' => 'required|boolean',
            'role.managePestProductRole' => 'nullable|in:ROLE_FULL_PEST_PRODUCT,ROLE_VIEW_PEST_PRODUCT,ROLE_ARRANGE_ORDER_PEST_PRODUCT',
            'role.ROLE_JOB_CANCEL' => 'required|boolean',
            'role.ROLE_JOB_RESCHEDULE' => 'required|boolean',
            'role.ROLE_VTP_ADMIN' => 'required|boolean',
            'role.ROLE_GEO_CODE' => 'required|boolean',
            'role.ROLE_RECRUIT_LINKER' => 'required',
            'role.ROLE_EDIT_CUSTOMER_ID' => 'required|boolean',
            'role.ROLE_RECRUIT_DELETE' => 'required|boolean',
            'role.ROLE_ACCOUNT_NOTES' => 'required|boolean',
            'role.ROLE_CHANGE_SERVICE_PRICE' => 'required|boolean',
            'role.ROLE_LEAD_ALERT' => 'required|boolean',
            'role.ROLE_HIDE_SETTING' => 'required|boolean',
            'role.ROLE_HIDE_REPORTS' => 'required|boolean',
            'role.ROLE_HIDE_FINANCIAL' => 'required|boolean',
            'role.ROLE_HIDE_SALES_TRACKER' => 'required|boolean',
            'role.ROLE_HIDE_VTP' => 'required|boolean',
            'role.ROLE_RECEIVE_INBOUND_SMS' => 'required|boolean',
            'role.ROLE_RECEIVE_CUSTOM_CONTRACT_ENDING_NOTIFICATION' => 'required|boolean',
            'role.ROLE_ALERT_ONLINE_BOOKING' => 'required|boolean',
            'role.ROLE_ALLOW_ADD_ZIPCODE' => 'required|boolean',
            'role.ROLE_USER_VIEWADD' => 'required|boolean',
            'role.ROLE_FULL_ACCOUNT_NUMBER' => 'required|boolean',
            'role.ROLE_SHOW_ALL_PINS_LEAD_MAP' => 'required|boolean',
            'role.ROLE_WDO_TOOLS' => 'required|boolean',
            'role.ROLE_ONLINE_BOOKING' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        DB::beginTransaction();
        try {
            $roles = array(
                'ROLE_ADMIN',
                'ROLE_OWNER',
                'ROLE_BRANCH_MANAGER',
                'ROLE_SECRETARY',
                'ROLE_SALES_MANAGER',
                'ROLE_SALES_ADMIN',
                'ROLE_ROUTE_MANAGER',
                'ROLE_COLLECTIONS'
            );

            $extraRoles = array(
                'ROLE_FULL_ACCOUNT_NUMBER', 'ROLE_SHOW_ALL_PINS_LEAD_MAP', 'ROLE_JOB_CANCEL', 'ROLE_JOB_RESCHEDULE', 'ROLE_VTP_ADMIN', 'ROLE_GEO_CODE', 'ROLE_EDIT_CUSTOMER_ID', 'ROLE_RECRUIT_LINKER', 'ROLE_RECRUIT_DELETE', 'ROLE_TECH_RESTRICTED', 'ROLE_COMMISSION_REPORT', 'ROLE_USER_VIEWADD', 'ROLE_ACCOUNT_NOTES', 'ROLE_CHANGE_SERVICE_PRICE', 'ROLE_HIDE_SETTING', 'ROLE_HIDE_REPORTS', 'ROLE_HIDE_FINANCIAL', 'ROLE_HIDE_SALES_TRACKER', 'ROLE_HIDE_VTP', 'ROLE_RECEIVE_INBOUND_SMS', 'ROLE_RECEIVE_CUSTOM_CONTRACT_ENDING_NOTIFICATION', 'ROLE_WDO_TOOLS', 'ROLE_ONLINE_BOOKING', 'ROLE_ALERT_ONLINE_BOOKING', 'ROLE_ALLOW_ADD_ZIPCODE', 'ROLE_LEAD_ALERT'
            );

            $user = OrkestraUser::findOrFail($userId);

            if (!$user) {
                throw new \Exception(__('strings.message', ['message' => 'User not found!']));
            }

            $groupIds = OrkestraGroup::whereIn('role', $roles)->pluck('id')->toArray();

            $roleDetails = $request->role;
            $mainRole = $roleDetails['role'] ?? null;

            if ($mainRole) {
                $pos = array_search($mainRole, $roles);
                unset($roles[$pos]);
            }

            $groupIds = OrkestraGroup::whereIn('role', $roles)->pluck('id')->toArray();

            if ($groupIds) {
                OrkestraUserGroup::where('user_id', $userId)->whereIn('group_id', $groupIds)->delete();
            }

            // if (
            //     !in_array($mainRole, array('ROLE_ADMIN', 'ROLE_OWNER', 'ROLE_BRANCH_MANAGER', 'ROLE_SECRETARY'))
            //     && isset($roleDetails['ROLE_USER_VIEWADD']) && $roleDetails['ROLE_USER_VIEWADD']
            // ) {
            //     throw new \Exception(__('strings.message', ['message' => 'Only Owner/Branch Manager/Secretary can have access to Employees list']));
            // }

            if ($mainRole) {
                $group = OrkestraGroup::where('role', $mainRole)->firstOrFail();
                OrkestraUserGroup::updateOrCreate(['group_id' => $group->id, 'user_id' => $userId], ['group_id' => $group->id, 'user_id' => $userId]);
            }

            $selectedRole = $roleDetails['viewAddRoutePageRole'] ?? null;
            if ($roleDetails['viewAddRoutePage'] && $selectedRole) {
                if ($selectedRole == 'ROLE_ROUTE_READ') {
                    $this->assignRole($user, 'ROLE_ROUTE_READ');
                    $this->unassignRole($user, 'ROLE_ROUTE_VIEWADD');
                } elseif ($selectedRole == 'ROLE_ROUTE_VIEWADD') {
                    $this->assignRole($user, 'ROLE_ROUTE_VIEWADD');
                    $this->unassignRole($user, 'ROLE_ROUTE_READ');
                }
            } else {
                $this->unassignRole($user, 'ROLE_ROUTE_READ');
                $this->unassignRole($user, 'ROLE_ROUTE_VIEWADD');
            }

            $selectedAgreementRole = $roleDetails['manageAgreementRole'] ?? null;
            if ($roleDetails['manageAgreement'] && $selectedAgreementRole) {
                if ($selectedAgreementRole == 'ROLE_FULL_AGREEMENT') {
                    $this->assignRole($user, 'ROLE_FULL_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_EDIT_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_VIEW_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_ARRANGE_ORDER_AGREEMENT');
                } elseif ($selectedAgreementRole == 'ROLE_VIEW_AGREEMENT') {
                    $this->assignRole($user, 'ROLE_VIEW_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_EDIT_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_FULL_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_ARRANGE_ORDER_AGREEMENT');
                } elseif ($selectedAgreementRole == 'ROLE_ARRANGE_ORDER_AGREEMENT') {
                    $this->assignRole($user, 'ROLE_ARRANGE_ORDER_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_EDIT_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_VIEW_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_FULL_AGREEMENT');
                } elseif ($selectedAgreementRole == 'ROLE_EDIT_AGREEMENT') {
                    $this->assignRole($user, 'ROLE_EDIT_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_FULL_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_VIEW_AGREEMENT');
                    $this->unassignRole($user, 'ROLE_ARRANGE_ORDER_AGREEMENT');
                }
            } else {
                $this->unassignRole($user, 'ROLE_FULL_AGREEMENT');
                $this->unassignRole($user, 'ROLE_EDIT_AGREEMENT');
                $this->unassignRole($user, 'ROLE_VIEW_AGREEMENT');
                $this->unassignRole($user, 'ROLE_ARRANGE_ORDER_AGREEMENT');
            }

            $selectedServiceTypeRole = $roleDetails['manageServiceTypeRole'] ?? null;
            if ($roleDetails['manageServiceType'] && $selectedServiceTypeRole) {
                if ($selectedServiceTypeRole == 'ROLE_FULL_SERVICE_TYPE') {
                    $this->assignRole($user, 'ROLE_FULL_SERVICE_TYPE');
                    $this->unassignRole($user, 'ROLE_VIEW_SERVICE_TYPE');
                    $this->unassignRole($user, 'ROLE_ARRANGE_ORDER_SERVICE_TYPE');
                } elseif ($selectedServiceTypeRole == 'ROLE_VIEW_SERVICE_TYPE') {
                    $this->unassignRole($user, 'ROLE_FULL_SERVICE_TYPE');
                    $this->assignRole($user, 'ROLE_VIEW_SERVICE_TYPE');
                    $this->unassignRole($user, 'ROLE_ARRANGE_ORDER_SERVICE_TYPE');
                } elseif ($selectedServiceTypeRole == 'ROLE_ARRANGE_ORDER_SERVICE_TYPE') {
                    $this->unassignRole($user, 'ROLE_FULL_SERVICE_TYPE');
                    $this->unassignRole($user, 'ROLE_VIEW_SERVICE_TYPE');
                    $this->assignRole($user, 'ROLE_ARRANGE_ORDER_SERVICE_TYPE');
                }
            } else {
                $this->unassignRole($user, 'ROLE_FULL_SERVICE_TYPE');
                $this->unassignRole($user, 'ROLE_VIEW_SERVICE_TYPE');
                $this->unassignRole($user, 'ROLE_ARRANGE_ORDER_SERVICE_TYPE');
            }

            $selectedPestProductRole = $roleDetails['managePestProductRole'] ?? null;
            if ($roleDetails['managePestProduct'] && $selectedPestProductRole) {
                if ($selectedPestProductRole == 'ROLE_FULL_PEST_PRODUCT') {
                    $this->assignRole($user, 'ROLE_FULL_PEST_PRODUCT');
                    $this->unassignRole($user, 'ROLE_VIEW_PEST_PRODUCT');
                    $this->unassignRole($user, 'ROLE_ARRANGE_ORDER_PEST_PRODUCT');
                } elseif ($selectedPestProductRole == 'ROLE_VIEW_PEST_PRODUCT') {
                    $this->unassignRole($user, 'ROLE_FULL_PEST_PRODUCT');
                    $this->assignRole($user, 'ROLE_VIEW_PEST_PRODUCT');
                    $this->unassignRole($user, 'ROLE_ARRANGE_ORDER_PEST_PRODUCT');
                } elseif ($selectedPestProductRole == 'ROLE_ARRANGE_ORDER_PEST_PRODUCT') {
                    $this->unassignRole($user, 'ROLE_FULL_PEST_PRODUCT');
                    $this->unassignRole($user, 'ROLE_VIEW_PEST_PRODUCT');
                    $this->assignRole($user, 'ROLE_ARRANGE_ORDER_PEST_PRODUCT');
                }
            } else {
                $this->unassignRole($user, 'ROLE_FULL_PEST_PRODUCT');
                $this->unassignRole($user, 'ROLE_VIEW_PEST_PRODUCT');
                $this->unassignRole($user, 'ROLE_ARRANGE_ORDER_PEST_PRODUCT');
            }

            foreach ($extraRoles as $role) {
                if (
                    $mainRole !== 'ROLE_COLLECTIONS' // Collections agents may have only that one role.
                    && isset($roleDetails[$role]) && $roleDetails[$role]
                ) {
                    $this->assignRole($user, $role);
                } else {
                    $this->unassignRole($user, $role);
                }
            }

            if ($mainRole === 'ROLE_COLLECTIONS') {
                $this->disableSalesperson($user);
                $this->disableRecruiter($user);
                $this->disableTechnician($user);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        return $this->sendResponse(true, 'Permission updated Successfully!');
    }

    // API for completed alerts
    public function alertHistoryListing(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $alertHistory = $this->alertHistory($request->user_id);
        return $this->sendResponse(true, 'Alerts History Listeninig', $alertHistory);
    }

    // Return completed alerts
    public function alertTaskHistory($id)
    {
        $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $id)->first();
        $alert = PocomosOfficeAlert::where('assigned_to_user_id', $find_assigned_by_to->id)->with('alert_history_details', 'assigned_by_details', 'assigned_to_details')->get();

        $all_leads = collect($alert);
        $leadsCollection = $all_leads->filter(function ($value) {
            return !is_null($value->alert_history_details);
        })->values();
        $leadsCollection->all();

        return $leadsCollection;
    }

    // Edit SalesPeople
    public function editSalesPeople(Request $request)
    {
        $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $id)->first();
        $find_sales_profile = PocomosSalespersonProfile::where('office_user_profile_id', $find_assigned_by_to->profile_id)->first();
        $input = [];
    }

    /* API for list of Sales People*/

    public function salesPeopleList(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosSalesPeople = PocomosSalesPeople::query();

        if (isset($request->status)) {
            $PocomosSalesPeople = $PocomosSalesPeople->where('active', true);
        }

        $PocomosSalesPeople = $PocomosSalesPeople->with('office_user_details.user_details')->orderBy('id', 'desc');

        if ($request->office_id) {
            $office_id = $request->office_id;

            $userIds = OrkestraUserGroup::pluck('user_id')->toArray();
            $userIds = OrkestraUser::where('active', true)->whereIn('id', $userIds)->pluck('id')->toArray();

            $officeUsers = PocomosCompanyOfficeUser::where('deleted', false)->where('active', true)->whereOfficeId($office_id)->whereIn('user_id', $userIds)->whereNotNull('user_id')->pluck('id')->toArray();
            $PocomosSalesPeople = $PocomosSalesPeople->whereIn('user_id', $officeUsers);
        }

        $PocomosSalesPeople = $PocomosSalesPeople->where('active', true)->get()->toArray();
        $length = count($PocomosSalesPeople);
        
        for($i=0; $i<$length; $i++){
            if($PocomosSalesPeople[$i]['office_user_details']['user_details']['primary_role'] != 'ROLE_SALESPERSON'){
                unset($PocomosSalesPeople[$i]);
            }
        }

        $data = [
            'sales_peoples' => array_values($PocomosSalesPeople),
            'count' => count($PocomosSalesPeople)
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Sales People']), $data);
    }

    /**Technician users list */
    public function technicianUsersList(Request $request)
    {
        $v = validator($request->all(), [
            'active' => 'required|boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $technicians_ids = PocomosTechnician::whereActive($request->active)->pluck('user_id');
        $office_users_ids = PocomosCompanyOfficeUser::whereActive($request->active)->whereIn('id', $technicians_ids)->whereOfficeId($request->office_id)->pluck('user_id');

        $technicians = OrkestraUser::whereActive($request->active)->with('pocomos_company_office_user.technician_user_details')->whereIn('id', $office_users_ids)->get();
        return $this->sendResponse(true, __('strings.list', ['name' => 'Technicians users']), $technicians);
    }

    public function emailHistory(Request $request, $id)
    {
        $userIds = PocomosCompanyOfficeUserProfile::join('pocomos_company_office_users as pcou', 'pocomos_company_office_user_profiles.id', 'pcou.profile_id')
            ->join('pocomos_company_offices as pco', 'pcou.office_id', 'pco.id')
            ->leftJoin('pocomos_customer_sales_profiles as pcsp', 'pcou.id', 'pcsp.office_user_id')
            ->where('pocomos_company_office_user_profiles.id', $id)
            ->get();

        $office_user_id = Session::get(config('constants.ACTIVE_OFFICE_ID')) ?? null;
        if (!$office_user_id) {
            throw new \Exception(__('strings.something_went_wrong'));
        }
        $office_user_id = $this->convertArrayInStrings(array($office_user_id));

        $sql = "SELECT e.id AS email_id,
                  m.id AS message_id,
                  COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'System') AS sending_user,
                  m.recipient,
                  m.recipient_name,
                  e.type,
                  m.status,
                  m.date_status_changed
                FROM pocomos_email_messages m
                  JOIN pocomos_emails e ON m.email_id = e.id
                  LEFT JOIN pocomos_company_office_users ou ON ou.id = COALESCE(m.office_user_id, e.office_user_id)
                  LEFT JOIN orkestra_users u ON ou.user_id = u.id
                  WHERE (e.office_user_id IN ($office_user_id) OR e.receiving_office_user_id IN ($office_user_id))";

        if ($request->search) {
            $searchTerm = "%$request->search%";
            $sql .= ' AND (u.first_name LIKE ' . $searchTerm . ' OR u.last_name LIKE ' . $searchTerm . ' OR CONCAT(u.first_name, \' \', u.last_name) LIKE ' . $searchTerm . ')';
        }

        $result = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'Email History', $result);
    }

    public function viewEmail(Request $request, $id)
    {
        $email = PocomosEmail::whereId($id)->whereOfficeId($request->office_id)->firstOrFail();

        return $this->sendResponse(true, 'Email', $email);
    }

    public function activityHistory(Request $request, $profileId)
    {
        // $userIds = PocomosCompanyOfficeUserProfile::join('pocomos_company_office_users as pcou', 'pocomos_company_office_user_profiles.id', 'pcou.profile_id')
        //     ->join('pocomos_company_offices as pco', 'pcou.office_id', 'pco.id')
        //     ->leftJoin('pocomos_customer_sales_profiles as pcsp', 'pcou.id', 'pcsp.office_user_id')
        //     ->where('pocomos_company_office_user_profiles.id', $id)
        //     ->get();

        $ouIds = PocomosCompanyOfficeUserProfile::with('office_users')
            ->whereId($profileId)->get()->pluck('office_users.*.id')->collapse()->toArray();

        // return $profile->office_users;

        // $office_user_id = Session::get(config('constants.ACTIVE_OFFICE_ID')) ?? null;
        // if (!$office_user_id) throw new \Exception(__('strings.something_went_wrong'));
        // $office_user_id = $this->convertArrayInStrings(array($office_user_id));


        $ouIds = $this->convertArrayInStrings($ouIds);

        $sql = 'SELECT a.*, IF(u.id IS NULL, \'System\', CONCAT(u.first_name, \' \', u.last_name)) as office_user
                    FROM pocomos_activity_logs a
                    LEFT JOIN pocomos_company_office_users ou ON a.office_user_id = ou.id
                    LEFT JOIN orkestra_users u ON ou.user_id = u.id
                    WHERE a.office_user_id IN (' . $ouIds . ')
                    ';

        if ($request->search) {
            $search = '"%' . $request->search . '%"';
            $sql .= " AND (a.id LIKE $search
                OR a.date_created LIKE $search
                OR CONCAT(u.first_name,' ', u.last_name) LIKE $search
                OR a.type LIKE $search
                OR a.description LIKE $search
                )";
        }

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $result = DB::select(DB::raw($sql));

        return $this->sendResponse(
            true,
            'Activity History',
            [
                'result' => $result,
                'count' => $count,
            ]
        );
    }

    public function messageBoard($id)
    {
        $message_role = ['ROLE_TACKBOARD_ALERTS', 'ROLE_TACKBOARD_TODOS', 'ROLE_TACKBOARD_NOTES', 'ROLE_TACKBOARD_HISTORY'];

        $roles = OrkestraGroup::whereIn('role', $message_role)->get();

        $roleIds = $roles->pluck('id')->toArray();

        $userRoles = OrkestraUserGroup::whereUserId($id)->whereIn('group_id', $roleIds)->get();

        return $this->sendResponse(true, 'Message board', [
            'user_roles' => $userRoles,
            'roles' => $roles
        ]);
    }

    public function updateMessageBoard(Request $request, $id)
    {
        $message_role = ['ROLE_TACKBOARD_ALERTS', 'ROLE_TACKBOARD_TODOS', 'ROLE_TACKBOARD_NOTES', 'ROLE_TACKBOARD_HISTORY'];

        $roleIds = OrkestraGroup::whereIn('role', $message_role)->pluck('id')->toArray();

        OrkestraUserGroup::whereUserId($id)->whereIn('group_id', $roleIds)->delete();

        foreach ($request->role_ids as $roleId) {
            OrkestraUserGroup::create([
                'user_id' => $id,
                'group_id' => $roleId,
            ]);
        }

        return $this->sendResponse(true, 'Message board updated successfully');
    }

    // Manager listing
    public function managerList(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office_ids = PocomosCompanyOffice::whereId($request->office_id)->orWhere('parent_id', $request->office_id)->pluck('id')->toArray();
        $user_ids = PocomosCompanyOfficeUser::whereIn('office_id', $office_ids)->pluck('user_id')->toArray();

        $group = OrkestraGroup::where('role', 'ROLE_SALES_MANAGER')->firstOrFail();
        $manager_ids = OrkestraUserGroup::where('group_id', $group->id)->whereIn('user_id', $user_ids)->pluck('user_id')->toArray();

        $managers = PocomosCompanyOfficeUser::with('user_details')->whereIn('user_id', $manager_ids)->groupBy('user_id')->get();

        return $this->sendResponse(true, __('strings.list', ['name' => 'Managers']), $managers);
    }

    /**Impersonate user */
    public function switchUser(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:orkestra_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // return auth()->user()->id;

        /**Delete existing user tokens and create impersonate user token */
        $token = $this->setImpersonating($request->user_id);

        $data = array();
        $impersonateUser = OrkestraUser::findOrFail($request->user_id);

        // $officeId = Session::get(config('constants.ACTIVE_OFFICE_ID'));
        $officeId = $request->office_id;
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereUserId($request->user_id)->firstOrFail();
        $impersonateUser->is_impersonate = true;
        $success['user'] =  $impersonateUser;
        //Create new token
        $success['token'] =  $token;

        if (count($impersonateUser->pocomos_company_office_users)) {
            $data[config('constants.ACTIVE_OFFICE_ID')] = ($officeUser ? $officeUser->office_id : null);
            $data[config('constants.ACTIVE_OFFICE_USER_ID')] = ($officeUser ? $officeUser->id : null);
            $data[config('constants.ACTIVE_THEME_KEY')] = ($officeUser->company_details->office_settings ? $officeUser->company_details->office_settings->theme : 'N/A');

            Session::put($data);
            Session::save();

            $office = PocomosCompanyOffice::whereId($officeId)->first();
            $allOffices = $office->getChildWithParentOffices();

            $i = 0;
            foreach ($allOffices as $office) {
                $current_active_office = Session::get(config('constants.ACTIVE_OFFICE_ID'));
                $is_default_selected = false;
                if ($current_active_office == $office['id']) {
                    $is_default_selected = true;
                }
                $allOffices[$i]['is_default_selected'] = $is_default_selected;
                $i = $i + 1;
            }
            $impersonateUser->offices_details = $allOffices;
            unset($impersonateUser['pocomos_company_office_users']);
        } else {
            throw new \Exception(__('strings.message', ['message' => 'User need to as office user']));
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Impersonate']), $success);
    }

    /**Exit Impersonate Mode */
    public function exitImpersonateMode(Request $request)
    {
        // $v = validator($request->all(), [
        //     'user_id' => 'required|exists:orkestra_users,id',
        // ]);

        // if ($v->fails()) {
        //     return $this->sendResponse(false, $v->errors()->first());
        // }
        $pre_user_id = Session::get(config('constants.PREVIOUS_LOGGEDIN_USER'));
        // dd($pre_user_id);

        $data = array();
        $user = OrkestraUser::findOrFail($pre_user_id);
        $success['user'] =  $user;
        //Create new token

        $officeId = Session::get(config('constants.ACTIVE_OFFICE_ID'));
        $prevOfficeId = Session::get(config('constants.PREVIOUS_USER_OFFICE_ID'));
        // dd($prevOfficeId);
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($prevOfficeId)->whereUserId($pre_user_id)->firstOrFail();
        if (count($user->pocomos_company_office_users)) {
            $data[config('constants.ACTIVE_OFFICE_ID')] = ($officeUser ? $officeUser->office_id : null);
            $data[config('constants.ACTIVE_OFFICE_USER_ID')] = ($officeUser ? $officeUser->id : null);
            $data[config('constants.ACTIVE_THEME_KEY')] = ($officeUser->company_details->office_settings ? $officeUser->company_details->office_settings->theme : 'N/A');
            Session::put($data);
            Session::save();

            $office = PocomosCompanyOffice::whereId($officeId)->first();
            $allOffices = $office->getChildWithParentOffices();

            $i = 0;
            foreach ($allOffices as $office) {
                $current_active_office = Session::get(config('constants.ACTIVE_OFFICE_ID'));
                $is_default_selected = false;
                if ($current_active_office == $office['id']) {
                    $is_default_selected = true;
                }
                $allOffices[$i]['is_default_selected'] = $is_default_selected;
                $i = $i + 1;
            }
            $user->offices_details = $allOffices;
            unset($user['pocomos_company_office_users']);

            /**Delete impersonate user tokens and create previous user token */
            $token = $this->leaveImpersonatMode();
            $success['token'] =  $token;
        } else {
            throw new \Exception(__('strings.message', ['message' => 'User need to as office user']));
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Impersonate mode deactivated']), $success);
    }

    /**Fetch logged in user roles */
    public function getUserGroupRolesAndOffices($user_id)
    {
        $res = array();
        $res['user_id'] = $user_id;
        /**Get loggend in user roles */
        $res['roles'] = $this->getUserAllRoles();

        $offices = PocomosCompanyOfficeUser::whereUserId($user_id)->pluck('office_id')->toArray();
        $officesT = array();

        foreach ($offices as $value) {
            $officesTemp = PocomosCompanyOffice::findOrFail($value)->getChildWithParentOffices();
            $officesTempNew = array_map(function ($row) {
                return $row['id'];
            }, $officesTemp);
            $officesT = array_merge($officesT, $officesTempNew);
        }
        $officesTempNew = array_unique($officesT);
        $officeDetails = PocomosCompanyOffice::with('office_settings', 'logo', 'coontact_address')->whereIn('id', $officesTempNew)->get();

        $res['offices'] = $officeDetails;

        return $this->sendResponse(true, __('strings.list', ['name' => 'User groups and offices']), $res);
    }

    /**
     * Redirects to the configured default route
     *
     * @Secure(roles="ROLE_SECRETARY, ROLE_SUPPRESS_SALESPERSON_VIEW")
     */
    public function switchSalesPersonOrAdminView()
    {
        $user = auth()->user();
        $user = ($user->pocomos_company_office_users ? ($user->pocomos_company_office_users[0] ? $user->pocomos_company_office_users[0]->user_details : null) : null);

        $suppressRole = OrkestraGroup::whereRole('ROLE_SUPPRESS_SALESPERSON_VIEW')->firstOrFail();
        if ($this->isGranted('ROLE_SUPPRESS_SALESPERSON_VIEW')) {
            OrkestraUserGroup::where('user_id', $user->id)->where('group_id', $suppressRole->id)->delete();
        } else {
            OrkestraUserGroup::create(['user_id' => $user->id, 'group_id' => $suppressRole->id]);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Mode changed']));
    }

    /**Get recruiter users list */
    public function recruiterUsersList(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|optinal',
            'perPage' => 'integer|optinal'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $recruiters = PocomosRecruiter::query();

        if ($request->page && $request->perPage) {
            $page    = $request->page;
            $perPage = $request->perPage;
            $recruiters->skip($perPage * ($page - 1))->take($perPage);
        }
        $recruiters = $recruiters->with('user.user_details')->get();

        return $this->sendResponse(true, __('strings.list', ['name' => 'Recruiters']), $recruiters);
    }

    public function checkofficeuser(Request $request)
    {
        return $PocomosCompanyOfficeUsers = PocomosCompanyOfficeUser::with('user_details')
            ->whereId($id)->first();
    }

    public function checkofficeuserprofile(Request $request)
    {
        return $PocomosCompanyOfficeUsers = PocomosCompanyOfficeUserProfile::with('user_details')
            ->whereId($id)->first();
    }

    public function checktech(Request $request, $id)
    {
        $v = validator($request->all(), [
            // 'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        return $technicians = PocomosTechnician::leftjoin('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
            ->leftjoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->where('pocomos_technicians.id', $id)
            ->get();
    }

    /**Get user permissions */
    public function getUserPermission(Request $request, $user_id)
    {
        $v = validator($request->all(), [
            'role' => 'nullable|in:ROLE_ADMIN,ROLE_OWNER,ROLE_BRANCH_MANAGER,ROLE_SECRETARY,ROLE_SALES_MANAGER,ROLE_SALES_ADMIN,ROLE_ROUTE_MANAGER,ROLE_COLLECTIONS'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = OrkestraUser::findOrFail($user_id);
        //Get user base active groups
        $groups_ids = OrkestraUserGroup::where('user_id', $user_id)->pluck('group_id')->toArray();
        $user_roles = OrkestraGroup::whereIn('id', $groups_ids)->pluck('role')->toArray();
        $configured_roles = config('roles');
        $data = array();
        $data['assigned_roles'] = $user_roles;

        $allRoles = array();
        foreach ($configured_roles as $key => $val) {
            //  || in_array('ROLE_ADMIN', $user_roles)
            if (in_array($key, $user_roles)) {
                $allRoles[] = $key;
                foreach ($val as $role) {
                    if (in_array($role, $user_roles)) {
                        $allRoles[] = $role;

                        if (isset($configured_roles[$role]) && count($configured_roles[$role])) {
                            $allRoles = array_merge($allRoles, $configured_roles[$role]);
                        }
                    }
                }
            }
        }
        $res = array_values(array_unique($allRoles));

        /**Get role base default values */
        $roleValue = $request->role ?? null;
        $data['default_roles'] = $this->getRoleBaseDefaultValues($roleValue);
        return $this->sendResponse(true, __('strings.list', ['name' => 'User roles']), $data);
    }
}
