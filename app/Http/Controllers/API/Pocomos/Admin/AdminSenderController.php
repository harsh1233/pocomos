<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosSmsUsage;
use App\Models\Pocomos\PocomosSmsReceivedMessageLog;
use DB;

class AdminSenderController extends Controller
{
    use Functions;

    /**
     * API for list of Admin Sender
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'status' => 'nullable|in:active,inactive', //import_type
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPhoneNumber = PocomosPhoneNumber::whereType('sender');

        if ($request->status == 'active') {
            $PocomosPhoneNumber = $PocomosPhoneNumber->whereActive(true);
        } elseif ($request->status == 'inactive') {
            $PocomosPhoneNumber = $PocomosPhoneNumber->whereActive(false);
        }

        $status = 10;
        if (stripos('active', $request->search)  !== false) {
            $status = 1;
        } elseif (stripos('inactive', $request->search) !== false) {
            $status = 0;
        }

        if ($request->search) {
            $PocomosPhoneNumber->where(function ($query) use ($request, $status) {
                $query->where('alias', 'like', '%' . $request->search . '%')
                    ->orWhere('number', 'like', '%' . $request->search . '%')
                    ->orWhere('active', 'like', '%' . $status . '%');
            });
        }

        /**For pagination */
        $count = $PocomosPhoneNumber->count();
        if($request->page && $request->perPage){
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosPhoneNumber->skip($perPage * ($page - 1))->take($perPage);
        }
        $PocomosPhoneNumber = $PocomosPhoneNumber->get();

        return $this->sendResponse(true, 'List of Admin Sender.', [
            'senders' => $PocomosPhoneNumber,
            'count' => $count,
        ]);

        // $PocomosPhoneNumber = $PocomosPhoneNumber->get();

        // return $this->sendResponse(true, 'List of Admin Sender.', $PocomosPhoneNumber);
    }

    /**
     * API for details of Admin Sender
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosPhoneNumber = PocomosPhoneNumber::find($id);
        if (!$PocomosPhoneNumber) {
            return $this->sendResponse(false, 'Admin Sender Not Found');
        }
        return $this->sendResponse(true, 'Admin Sender details.', $PocomosPhoneNumber);
    }

    /**
     * API for create of Admin Sender
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'alias' => 'required',
            'type' => 'required|in:Sender', //import_type
            'number' => 'required|numeric',
        ], [
            'number.numeric' => 'The Phone Number must be a number.'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('alias', 'type', 'number') + ['active' => 1];

        $PocomosPhoneNumber =  PocomosPhoneNumber::create($input_details);

        /**End manage trail */
        return $this->sendResponse(true, 'Admin Sender created successfully.', $PocomosPhoneNumber);
    }

    /**
     * API for update of Admin Sender
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'sms_sender_id' => 'required|exists:pocomos_phone_numbers,id',
            'alias' => 'required',
            'type' => 'required|in:Sender', //import_type
            'number' => 'required|numeric',
        ], [
            'number.numeric' => 'The Phone Number must be a number.'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPhoneNumber = PocomosPhoneNumber::find($request->sms_sender_id);

        if (!$PocomosPhoneNumber) {
            return $this->sendResponse(false, 'Admin Sender not found.');
        }

        $input_details = $request->only('alias', 'type', 'number');

        $PocomosPhoneNumber->update($input_details);

        return $this->sendResponse(true, 'Admin Sender updated successfully.', $PocomosPhoneNumber);
    }

    /* API for changeStatus of  Admin Sender */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'sms_sender_id' => 'required|exists:pocomos_phone_numbers,id',
            'active' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPhoneNumber = PocomosPhoneNumber::find($request->sms_sender_id);

        if (!$PocomosPhoneNumber) {
            return $this->sendResponse(false, 'Reason not found');
        }

        $PocomosPhoneNumber->update([
            'active' => $request->active
        ]);

        return $this->sendResponse(true, 'Status changed successfully.', $PocomosPhoneNumber);
    }

    /* API for admin SMS Usage List */
    public function adminSMSUsage(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'offices' => 'required',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        /*
        $PocomosSmsUsage = PocomosSmsUsage::query();

        $date = $request->start_date;
        $end_date = $request->end_date;

        $PocomosSmsUsage = $PocomosSmsUsage->where('office_id', $request->office_id)->where(function ($query) use ($date, $end_date) {
            $query->whereDate('date_created', '>=', $date);
            $query->whereDate('date_created', '<=', $end_date);
        });

        $PocomosSmsUsage_count = $PocomosSmsUsage->count();

        $PocomosSmsUsage = $PocomosSmsUsage
            ->orderBy('id', 'DESC')
            ->get();

        $data = [
            'PocomosSmsUsage' => $PocomosSmsUsage,
            'count' => $PocomosSmsUsage_count
        ];
        */

        $offices = implode(',', $request->offices);

        // $sql = 'SELECT o.name, COUNT(u.id) as total FROM pocomos_sms_usage u
        //             JOIN pocomos_company_offices o ON u.office_id = o.id
        //             WHERE u.date_created BETWEEN "'.$request->start_date.'" AND "'.$request->end_date.'"
        //             AND u.office_id IN ('.$offices.')';

        $sql = 'select *,total from (SELECT o.name,COUNT(u.id) as total FROM pocomos_sms_usage u
                    JOIN pocomos_company_offices o ON u.office_id = o.id
                    WHERE u.date_created BETWEEN "' . $request->start_date . '" AND "' . $request->end_date . '"
                    AND u.office_id IN (' . $offices . ')';

        if ($request->search) {
            $search = '"%' . $request->search . '%"';
            $sql .= ' GROUP BY o.id ) test';
            $sql .= ' where (name like ' . $search . '';
            $sql .= ' or total like ' . $search . ')';
        } else {
            $sql .= ' GROUP BY o.id ) test';
        }

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";


        $data = DB::select(DB::raw(($sql)));

        return $this->sendResponse(true, 'Admin SMS Usage Details.', [
            'data' => $data,
            'count' => $count,
        ]);
    }

    /**
     * API for list of Admin Sender
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function receivedmessagelog(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosSmsReceivedMessageLog = PocomosSmsReceivedMessageLog::where('active', 1)->orderBy('id', 'desc');

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosSmsReceivedMessageLog->count();
        $PocomosSmsReceivedMessageLog->skip($perPage * ($page - 1))->take($perPage);
        $PocomosSmsReceivedMessageLog = $PocomosSmsReceivedMessageLog->get();

        return $this->sendResponse(true, 'List of sms received nessageL log.', [
            'received_sms_logs' => $PocomosSmsReceivedMessageLog,
            'count' => $count,
        ]);
    }
}
