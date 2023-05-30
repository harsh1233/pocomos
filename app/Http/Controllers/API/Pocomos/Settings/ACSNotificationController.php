<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAcsNotification;
use App\Models\Pocomos\PocomosAcsEvent;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class ACSNotificationController extends Controller
{
    use Functions;

    /* API for list of ACS Job Event */

    public function list(Request $request, $id)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosAcsNotification = DB::table('pocomos_acs_notifications as pan')
            ->select('*', 'pfl.title as email_title', 'psfl.title as sms_title', 'pc.first_name', 'pc.last_name', 'pc.id as customer_id', 'pan.id as acs_notification_id')
            ->join('pocomos_acs_events as pae', 'pan.acs_event_id', 'pae.id')
            ->join('pocomos_customers as pc', 'pan.customer_id', 'pc.id')
            ->leftJoin('pocomos_form_letters as pfl', 'pan.form_letter_id', 'pfl.id')
            ->leftJoin('pocomos_sms_form_letters as psfl', 'pan.sms_form_letter_id', 'psfl.id')
            ->where('pae.enabled', 1)
            ->where('pan.sent', 0)
            ->where('pan.active', 1)
            ->where('pae.office_id', $id);

        // ->where('pae.active', 1)
        // ->where('acsn.active', 1)

        if ($request->search) {
            $search = $request->search;
            $PocomosAcsNotification->where(function ($query) use ($search) {
                $query->where('pc.first_name', 'like', '%' . $search . '%')
                    ->orWhere('pc.last_name', 'like', '%' . $search . '%')
                    ->orWhere('pfl.title', 'like', '%' . $search . '%')
                    ->orWhere('psfl.title', 'like', '%' . $search . '%')
                    ->orWhere('notification_time', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosAcsNotification->count();
        $PocomosAcsNotification->skip($perPage * ($page - 1))->take($perPage);

        $PocomosAcsNotification = $PocomosAcsNotification->get();

        return $this->sendResponse(true, 'List', [
            'ACS_Job_Event' => $PocomosAcsNotification,
            'count' => $count,
        ]);
    }

    /* API for delete of ACS Job Event */

    public function delete($officeid, $notificationid)
    {
        $PocomosAcsNotification = DB::table('pocomos_acs_notifications as acsn')
            ->join('pocomos_acs_events as acse', 'acse.id', '=', 'acsn.acs_event_id')
            ->where('acse.active', 1)
            ->where('acsn.active', 1)
            ->where('acsn.id', $notificationid)
            ->where('acse.office_id', $officeid)
            ->first();

        if (!$PocomosAcsNotification) {
            return $this->sendResponse(false, 'Unable to find The ACS Job Event.');
        }

        PocomosAcsNotification::find($notificationid)->update(['active' => 0]);

        return $this->sendResponse(true, 'ACS Job Event deleted successfully.');
    }
}
