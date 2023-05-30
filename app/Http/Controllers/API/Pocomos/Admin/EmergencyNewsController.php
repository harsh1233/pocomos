<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosEmergencyNews;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use Carbon\Carbon;
use DB;
use App\Models\Orkestra\OrkestraUser;

class EmergencyNewsController extends Controller
{
    use Functions;

    /**
     * API for list of The Emergency News
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $emergencyNews = PocomosEmergencyNews::with('office_user.user_details');

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $emergencyNews->count();
        $emergencyNews->skip($perPage * ($page - 1))->take($perPage);

        $emergencyNews = $emergencyNews->get();

        return $this->sendResponse(true, 'List of Emergency news.', [
            'emergency_news' => $emergencyNews,
            'count' => $count,
        ]);
    }

    /**
     * API for details of The Emergency News
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosEmergencyNews = PocomosEmergencyNews::find($id);
        if (!$PocomosEmergencyNews) {
            return $this->sendResponse(false, 'The Emergency News Not Found');
        }
        return $this->sendResponse(true, 'The Emergency News details.', $PocomosEmergencyNews);
    }

    /**
     * API for create of The Emergency News
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'note' => 'required',
            'expire_at' => 'required',
            'created_by_user_id' => 'required|exists:pocomos_company_office_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('note');

        $Minutes = $request->expire_at;

        $input_details['expire_at'] = Carbon::now()->addMinutes($Minutes);
        $input_details['expire_at_minute'] = $request->expire_at;
        $input_details['created_by_user_id'] = $request->created_by_user_id;
        $input_details['active'] = 1;

        $PocomosEmergencyNews =  PocomosEmergencyNews::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'The Emergency News created successfully.', $PocomosEmergencyNews);
    }

    /**
     * API for update of The Emergency News
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'news_id' => 'required|exists:pocomos_emergency_news,id',
            'note' => 'required',
            'expire_at' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosEmergencyNews = PocomosEmergencyNews::find($request->news_id);

        if (!$PocomosEmergencyNews) {
            return $this->sendResponse(false, 'The Emergency News not found.');
        }

        $input_details = $request->only('note');

        $Minutes = $request->expire_at;

        $input_details['expire_at'] = Carbon::now()->addMinutes($Minutes);
        $input_details['expire_at_minute'] = $request->expire_at;

        $PocomosEmergencyNews->update($input_details);

        return $this->sendResponse(true, 'The Emergency News updated successfully.', $PocomosEmergencyNews);
    }

    /* API for changeStatus of The Emergency News */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'news_id' => 'required|exists:pocomos_emergency_news,id',
            'active' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosEmergencyNews = PocomosEmergencyNews::find($request->news_id);

        if (!$PocomosEmergencyNews) {
            return $this->sendResponse(false, 'Unable to find Emergency News');
        }

        $PocomosEmergencyNews->update([
            'active' => $request->active
        ]);

        return $this->sendResponse(true, 'Status changed successfully.', $PocomosEmergencyNews);
    }
}
