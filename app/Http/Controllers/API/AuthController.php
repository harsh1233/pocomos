<?php

namespace App\Http\Controllers\API;

use Hash;
use Excel;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Orkestra\OrkestraUser;
use App\Notifications\ForgotPassword;
use Illuminate\Support\Facades\Crypt;
use App\Models\Orkestra\OrkestraGroup;
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;

class AuthController extends Controller
{
    use Functions;

    /** Login user with username and password */
    public function login(Request $request)
    {
        $v = validator($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $user = OrkestraUser::where('username', $request->username)->first();

        if (!$user) {
            return $this->sendResponse(false, __('Invalid credential'));
        }
        if (!Hash::check($request->password, $user->password)) {
            return $this->sendResponse(false, __('Invalid credential'));
        }
        //Delete all old tokens
        // $user->tokens()->delete();
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            $authUser = Auth::user();
            //Create new token
            $success['token'] =  $authUser->createToken('MyAuthApp')->plainTextToken;

            // if(!isset($user->pocomos_company_office_users[0])){
            //     return $this->sendResponse(false, __('strings.something_went_wrong'));
            // }

            $data[config('constants.ACTIVE_OFFICE_ID')] = ($user->pocomos_company_office_users ? (isset($user->pocomos_company_office_users[0]) ? $user->pocomos_company_office_users[0]['office_id'] : 'N/A') : 'N/A');
            $data[config('constants.ACTIVE_OFFICE_USER_ID')] = ($user->pocomos_company_office_users ? (isset($user->pocomos_company_office_users[0]) ? $user->pocomos_company_office_users[0]['id'] : 'N/A') : 'N/A');
            $data[config('constants.ACTIVE_THEME_KEY')] = ($user->pocomos_company_office_users[0]['company_details']['office_settings'] ? $user->pocomos_company_office_users[0]['company_details']['office_settings']['theme'] : 'N/A');
            $request->session()->put($data);
            $request->session()->save();

            $office = PocomosCompanyOffice::whereId($data[config('constants.ACTIVE_OFFICE_ID')])->first();
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
            $user->last_login = date('Y-m-d H:i:s');
            $user->save();

            $user->offices_details = $allOffices;
            unset($user['pocomos_company_office_users']);
            $success['user'] =  $user;

            return $this->sendResponse(true, __('strings.sucess', ['name' => 'Login']), $success);
        }
    }

    /** Logout */
    public function logout()
    {
        //Delete all old tokens
        Auth::user()->tokens()->delete();

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Logout']));
    }

    /**Create version switch dependencies */
    public function createVersionSwitchDependencies(Request $request)
    {
        $v = validator($request->all(), [
            'office_user_id' => 'required|exists:pocomos_company_office_users,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeUser = PocomosCompanyOfficeUser::findOrFail($request->office_user_id);
        $authUser = OrkestraUser::findOrFail($officeUser->user_id);

        $success['user'] =  $authUser;
        //Create new token
        $success['token'] =  $authUser->createToken('MyAuthApp')->plainTextToken;
        $success['redirectUrl'] = config('constants.REDIRECT_URL');

        if (!isset($authUser->pocomos_company_office_users[0])) {
            return $this->sendResponse(false, __('strings.something_went_wrong'));
        }

        $data[config('constants.ACTIVE_OFFICE_ID')] = ($authUser->pocomos_company_office_users ? (isset($authUser->pocomos_company_office_users[0]) ? $authUser->pocomos_company_office_users[0]['office_id'] : 'N/A') : 'N/A');
        $data[config('constants.ACTIVE_OFFICE_USER_ID')] = ($authUser->pocomos_company_office_users ? (isset($authUser->pocomos_company_office_users[0]) ? $authUser->pocomos_company_office_users[0]['id'] : 'N/A') : 'N/A');
        $data[config('constants.ACTIVE_THEME_KEY')] = ($authUser->pocomos_company_office_users[0]['company_details']['office_settings'] ? $authUser->pocomos_company_office_users[0]['company_details']['office_settings']['theme'] : 'N/A');
        $request->session()->put($data);
        $request->session()->save();

        Session::put('activeUserToken', $success['token'] ?? 'N/A');
        Session::put('isFromOldVersion', true);

        $office = PocomosCompanyOffice::whereId($data[config('constants.ACTIVE_OFFICE_ID')])->first();
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
        $authUser->last_login = date('Y-m-d H:i:s');
        $authUser->save();

        $authUser->offices_details = $allOffices;
        unset($authUser['pocomos_company_office_users']);
        $success['user'] =  $authUser;

        return $this->sendResponse(true, __('strings.create', ['name' => 'Version switch dependencies']), $success);
    }

    /**Check version switch user is exist or not and do accordingly */
    public function checkVersionSwitchedUser(Request $request)
    {
        $isFromOldVersion = Session::get('isFromOldVersion') ?? false;

        if (isset($isFromOldVersion)) {
            $officeUserId = Session::get(config('constants.ACTIVE_OFFICE_USER_ID'));
            $officeUser = PocomosCompanyOfficeUser::findOrFail($officeUserId);
            $authUser = OrkestraUser::findOrFail($officeUser->user_id);
        } else {
            $authUser = Auth::user();
        }

        if (!isset($authUser->pocomos_company_office_users[0])) {
            return $this->sendResponse(false, __('strings.something_went_wrong'));
        }

        $success['user'] =  $authUser;
        //Create new token
        $success['token'] =  Session::get('activeUserToken') ?? 'N/A';

        $data[config('constants.ACTIVE_OFFICE_ID')] = ($authUser->pocomos_company_office_users ? (isset($authUser->pocomos_company_office_users[0]) ? $authUser->pocomos_company_office_users[0]['office_id'] : 'N/A') : 'N/A');
        $data[config('constants.ACTIVE_OFFICE_USER_ID')] = ($authUser->pocomos_company_office_users ? (isset($authUser->pocomos_company_office_users[0]) ? $authUser->pocomos_company_office_users[0]['id'] : 'N/A') : 'N/A');
        $data[config('constants.ACTIVE_THEME_KEY')] = ($authUser->pocomos_company_office_users[0]['company_details']['office_settings'] ? $authUser->pocomos_company_office_users[0]['company_details']['office_settings']['theme'] : 'N/A');
        Session::put($data);
        Session::save();

        $office = PocomosCompanyOffice::whereId($data[config('constants.ACTIVE_OFFICE_ID')])->first();
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
        $authUser->last_login = date('Y-m-d H:i:s');
        $authUser->save();

        $authUser->offices_details = $allOffices;
        unset($authUser['pocomos_company_office_users']);
        $success['user'] =  $authUser;

        return $this->sendResponse(true, __('strings.details', ['name' => 'Version switched user']), $success);
    }

    /**Get logged in user details */
    public function getUserDetails(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:orkestra_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $authUser = OrkestraUser::findOrFail($request->user_id);

        $office = PocomosCompanyOffice::whereId($officeId)->first();
        $allOffices = $office->getChildWithParentOffices();

        $i = 0;
        foreach ($allOffices as $office) {
            $is_default_selected = false;
            if ($officeId == $office['id']) {
                $is_default_selected = true;
            }
            $allOffices[$i]['is_default_selected'] = $is_default_selected;
            $i = $i + 1;
        }
        $authUser->offices_details = $allOffices;
        $success['user'] =  $authUser;

        return $this->sendResponse(true, __('strings.details', ['name' => 'User']), $success);
    }

    /**
     * Login remote completion user
     */
    public function loginRemoteCompletionUser(Request $request)
    {
        $v = validator($request->all(), [
            'hash' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user_id = Crypt::decryptString($request->hash);

        if (!$user_id) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate the user.']));
        }

        $authUser = OrkestraUser::findOrFail($user_id);

        $success['user'] =  $authUser;
        //Create new token
        $success['token'] =  $authUser->createToken('MyAuthApp')->plainTextToken;
        return $this->sendResponse(true, __('strings.details', ['name' => 'Remote user']), $success);
    }

    /**Get role groups */
    public function getRoleGroups()
    {
        $groups = OrkestraGroup::get();
        return $this->sendResponse(true, __('strings.list', ['name' => 'Role grouos']), $groups);
    }

    /**Get system all roles */
    public function getSystemAllRoles(Request $request)
    {
        $v = validator($request->all(), [
            'tree' => 'nullable|boolean'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $roles = config('roles');
        if (!$request->tree) {
            $allRoles = array();
            foreach ($roles as $key => $val) {
                $allRoles[] = $key;
                foreach ($val as $role) {
                    $allRoles[] = $role;

                    if (isset($roles[$role]) && count($roles[$role])) {
                        $allRoles = array_merge($allRoles, $roles[$role]);
                    }
                }
            }
            $roles = $allRoles;
        }
        $res['roles'] = $roles;
        return $this->sendResponse(true, __('strings.list', ['name' => 'System all roles']), $res);
    }

    public function createChargeTest()
    {
        $data = [
            'requestType'=>'sale',
            'amount' => '3',
            'accountType' => 'R',
            'transactionIndustryType' => 'RE',
            'holderType'=>'P',
            'holderName'=>'John Smith',
            'accountNumber'=>'5499740000000057',
            'accountAccessory'=>'0423',
            'street'=>'12 Main St',
            'city'=>'Denver',
            'state'=>'CO',
            'zipCode'=>'30301',
            'customerAccountCode'=>'0000000001',
            'transactionCode'=>'0000000001',
        ];
        return $this->createCharge($data);
    }

    /**Forgot password */
    public function forgotPassword(Request $request)
    {
        $v = validator($request->all(), [
            // 'email' => 'required|exists:orkestra_users,email',
            'username' => 'required|exists:orkestra_users,username'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = OrkestraUser::whereUsername($request->username)->first();

        if (filter_var($user->email, FILTER_VALIDATE_EMAIL) == false) {
            throw new \Exception(__('strings.message', ['message' => 'Email is Required or Valid, Please check in your profile!']));
        }

        $hash = Crypt::encryptString($request->username);
        $user->notify(new ForgotPassword($user, $hash));

        return $this->sendResponse(true, __('strings.message', ['message' => 'Please check your email for further instructions']));
    }

    /**Reset password */
    public function resetPassword(Request $request)
    {
        $v = validator($request->all(), [
            'hash' => 'required',
            'password' => 'required|confirmed',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $username = Crypt::decryptString($request->hash);

        $user = OrkestraUser::whereUsername($username)->first();
        if (!$user) {
            return $this->sendResponse(false, __('strings.message', ['message' => 'User not found!']));
        }
        $user->password = Hash::make($request->password);
        $user->save();

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Passoword reset']));
    }
}
