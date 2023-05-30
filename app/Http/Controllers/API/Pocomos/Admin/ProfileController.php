<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosNotificationQueue;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosTimezone;
use App\Models\Pocomos\PocomosBlogPost;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Orkestra\OrkestraUserPreference;
use Hash;

class ProfileController extends Controller
{
    use Functions;

    public function editProfile($id)
    {
        $user = OrkestraUser::findOrFail($id);
        $userPreference = OrkestraUserPreference::whereUserId($id)->first();

        $userId = PocomosCompanyOfficeUser::whereUserId($id)->firstorfail()->id;
        $notificationQueue = PocomosNotificationQueue::whereUserId($userId)->first();

        $timezones = PocomosTimezone::whereActive(1)->get();
        /*
        delivery types:
        Real-time
        Digest
        Off
        */

        return $this->sendResponse(true, 'Profile', [
            'user' => $user,
            'user_timezone' => $userPreference ? $userPreference->timezone : null,
            'delivery_type' => $notificationQueue ? $notificationQueue->delivery_type : null,
            'timezones' => $timezones,
        ]);
    }


    public function updateProfile(Request $request, $id)
    {
        $v = validator($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'timezone' => 'required',
            'delivery_type' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }


        OrkestraUser::findOrFail($id)->update([
            'email' => $request->email,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
        ]);

        $userPreference = OrkestraUserPreference::updateOrCreate(
            [
            'user_id' => $id
            ],
            [
            'timezone' => $request->timezone,
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'active' => 1,
        ]
        );

        $userId = PocomosCompanyOfficeUser::whereUserId($id)->firstorfail()->id;

        $notificationQueue = PocomosNotificationQueue::updateOrCreate(
            [
            'user_id' => $userId
            ],
            [
            'delivery_type' => $request->delivery_type,
            'messages' => '[]',
            'active' => 1,
        ]
        );

        return $this->sendResponse(true, __('strings.update', ['name' => 'Profile']));
    }


    public function changePassword(Request $request, $id)
    {
        $v = validator($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $orkestraUser = OrkestraUser::findOrFail($id);
        $hashedPassword = $orkestraUser->password;

        if (!Hash::check($request->current_password, $hashedPassword)) {
            throw new \Exception(__('strings.message', ['message' => 'Current password is not correct']));
        }

        $orkestraUser->update(['password'=> Hash::make($request->confirm_password)]);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Password']));
    }

    public function updateSignature(Request $request, $profileId)
    {
        if ($request->signature) {
            $orkFileId = $this->uploadFile($request->signature);

            PocomosCompanyOfficeUserProfile::whereId($profileId)->firstorfail()->update([
                'signature_id'=> $orkFileId
            ]);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'User profile updated']));
    }

    public function whatsNew()
    {
        $blogPosts = PocomosBlogPost::whereActive(1)->where('date_posted', '<', date("Y-m-d", strtotime('tomorrow')))
                ->orderByDesc('date_posted')->get();

        //content = body
        return array(
            'blog_posts' => $blogPosts,
        );
    }
}
