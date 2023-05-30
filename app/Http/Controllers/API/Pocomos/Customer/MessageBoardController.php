<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosQRCodeGroup;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosQRCodeScanSession;
use App\Models\Pocomos\PocomosQRCode;
use App\Models\Pocomos\PocomosCustomerAlert;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\PocomosCustomer;
use App\Notifications\TaskAddNotification;
use DB;

class MessageBoardController extends Controller
{
    use Functions;

    /**
     * API for create alert
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'required|exists:pocomos_customers,id',
            'description' => 'required',
            'priority' => 'required|in:Low,Normal,High',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomos_office_alert_create = $this->createToDo($request, 'Alert', 'Alert', 'Customer');

        return $this->sendResponse(true, 'Alert Created Successfully!', $pocomos_office_alert_create);
    }

    /* API for Alerts Listing*/
    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:orkestra_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_assigned_by_to = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->where('office_id', $request->office_id)->pluck('id')->toArray();

        $alerts = PocomosCustomerAlert::whereHas(
            'alert_details',
            function ($query) {
                $query->where('status', '!=', 'Completed');
            }
        )->whereIn('assigned_to_id', $find_assigned_by_to)->with('alert_details', 'assigned_by_details', 'assigned_to_details')->get();

        return $this->sendResponse(true, 'Alerts', $alerts);
    }

    /* API for add taks */
    public function addTask(Request $request)
    {
        $v = validator($request->all(), [
            'assignd_by' => 'required|exists:orkestra_users,id',
            'assign_to' => 'required|exists:pocomos_customers,id',
            'description' => 'required',
            'priority' => 'required|in:Low,Normal,High',
            'dateDue' => 'required|date',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = PocomosCustomer::where('id', $request->assign_to)->first();

        $sender = OrkestraUser::where('id', $request->assignd_by)->first();

        $user->notify(new TaskAddNotification($request, $user, $sender));

        $data = $this->createToDo($request, 'ToDo', 'Task', 'Customer');

        return $this->sendResponse(true, 'The task has been created successfully.', $data);
    }

    // API for task listing
    /* List of TODO Task */
    public function taskList(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:orkestra_users,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $find_assigned_by_to = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->where('office_id', $request->office_id)->pluck('id')->toArray();

        $alert = PocomosCustomerAlert::whereHas(
            'todo_details',
            function ($query) {
                $query->where('status', '!=', 'Completed');
            }
        )->whereIn('assigned_to_id', $find_assigned_by_to)->with('todo_details', 'assigned_by_details', 'assigned_to_details');

        if ($request->search) {
            $search = $request->search;
            $date = date('Y-m-d', strtotime($search));

            $sql = "SELECT pa.id
            FROM pocomos_customer_alerts AS poa
            JOIN pocomos_alerts AS pa ON poa.alert_id = pa.id
            LEFT JOIN pocomos_company_office_users AS pcou ON poa.assigned_by_id = pcou.id
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

    public function taskhistory(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:orkestra_users,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $find_assigned_by_to = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->where('office_id', $request->office_id)->pluck('id')->toArray();

        $alert = PocomosCustomerAlert::whereHas(
            'completed_todo_details'
        )->orWhereHas(
            'completed_alert_details'
        )->whereIn('assigned_to_id', $find_assigned_by_to)->with('completed_alert_details', 'completed_todo_details', 'assigned_by_details', 'assigned_to_details');

        if ($request->search) {
            $search = $request->search;
            $date = date('Y-m-d', strtotime($search));
            $find_assigned_by_to = $this->convertArrayInStrings($find_assigned_by_to);

            $sql = "SELECT pa.id
            FROM pocomos_customer_alerts AS poa
            LEFT JOIN pocomos_alerts AS pa ON poa.alert_id = pa.id
            LEFT JOIN pocomos_company_office_users AS pcou ON poa.assigned_by_id = pcou.id
            LEFT JOIN orkestra_users AS ou ON pcou.user_id = ou.id
            LEFT JOIN pocomos_customer_sales_profiles AS pcsp ON poa.assigned_to_id = pcsp.id
            LEFT JOIN pocomos_customers AS pc ON pcsp.customer_id = pc.id

            where (pa.name LIKE '%$search%' OR pa.type LIKE '%$search%' OR pa.date_modified LIKE '%$search%' OR pa.priority LIKE '%$search%' OR ou.first_name LIKE '%$search%' OR ou.last_name LIKE '%$search%' OR pa.date_due LIKE '%$date%' OR pc.first_name LIKE '%$search%' OR pc.last_name LIKE '%$search%' ) and pa.status ='Completed' and poa.assigned_to_id IN ($find_assigned_by_to) ";

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
}
