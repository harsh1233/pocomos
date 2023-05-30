<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosMassNotification;
use App\Models\Pocomos\Admin\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use App\Models\Orkestra\OrkestraUser;
use App\Jobs\MassNotificationJob;

class MassNotificationController extends Controller
{
    use Functions;

    /**
     * API for list of Mass Notifications
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function getBranches($officeId)
    {
        $office = PocomosCompanyOffice::findorfail($officeId);

        if ($office->parent_id) {
            $officeId = $office->parent_id;
        }

        $branches = PocomosCompanyOffice::whereActive(true)->where(function ($q) use ($officeId) {
            $q->whereId($officeId)
                    ->orWhere('parent_id', $officeId);
        });

        $branches = $branches->get();

        return $this->sendResponse(true, 'List of branches', $branches);
    }


    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        /*
        $PocomosMassNotification = DB::table('pocomos_company_office_users as ca')
            ->leftJoin('pocomos_mass_notifications as sa', 'ca.id', '=', 'sa.assigned_by_user_id')
            ->orderBy('sa.id', 'desc')
            ->where('sa.active', 1)
            ->get();

        $PocomosMassNotification->map(function ($status) use ($request) {

            if ($status->user_id) {
                $status->assigned_by = OrkestraUser::where('id', $status->user_id)->select('first_name', 'last_name')->get();
            }
        });

        $data = [
            'records' => $PocomosMassNotification,
        ];
        */

        $massNotifications = PocomosMassNotification::with('office_user.user_details')->whereActive(1);

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $massNotifications->count();
        $massNotifications->skip($perPage * ($page - 1))->take($perPage);

        $massNotifications = $massNotifications->get();

        return $this->sendResponse(true, 'List of Mass Notifications.', [
            'mass_notifications' => $massNotifications,
            'count' => $count,
        ]);
    }

    /**
     * API for create of Mass Notifications
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'title' => 'required',
            // 'current_office_user' => 'required|exists:pocomos_company_office_users,id',  //logged in user
            'offices' => 'required|array|exists:pocomos_company_offices,id',
            'roles' => 'required|array|in:Administrator,Owner,Branch Manager,Secretary,Sales Manager,Sales Admin,Salesperson Route Manager,Technician,Recruiter,Collections Agent',
            'note' => 'required',
            'alert_priority' => 'required|in:Low,Normal,High,Error,Success',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // return $request;

        MassNotificationJob::dispatch($request->all());

        /**End manage trail */
        return $this->sendResponse(true, 'Users will be notified soon.');
    }
}
