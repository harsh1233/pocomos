<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosQRCodeBatch;
use App\Models\Pocomos\PocomosQRCode;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosQRCodeScanSession;
use App\Models\Pocomos\PocomosQRCodeScanSessionScan;

class BatchController extends Controller
{
    use Functions;

    /**
     * API for list of Sales Status
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request, $id)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $QRCodeBatchs = PocomosQRCodeBatch::where('office_id', $id)->where('active', true)->with('code_details');

        if ($request->search) {
            $search = $request->search;

            $QRCodeBatchs = $QRCodeBatchs->where(function ($q) use ($search) {
                $q->where('id', 'like', '%'.$search.'%');
                $q->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $QRCodeBatchs->count();
        $QRCodeBatchs = $QRCodeBatchs->skip($perPage * ($page - 1))->take($perPage)->orderBy('id', 'desc')->get();

        $data = [
            'qr_code_batchs' => $QRCodeBatchs,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Qr code batch']), $data);
    }


    /**
     * API for create of QR code
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'number_of_code' => 'required|numeric',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input = [];
        $input['name'] = $request->name;
        $input['office_id'] = $request->office_id;
        $input['active'] = '1';

        $create_batch = PocomosQRCodeBatch::create($input);

        for ($i = 1; $i <= $request->number_of_code; $i++) {
            $pass = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 6);
            $qr_batch = [];
            $qr_batch['batch_id'] = $create_batch->id;
            $qr_batch['data'] = $pass;
            $qr_batch['active'] = '1';
            $qr_batch['image_data'] = '';
            $qr_batch['identifier'] = '';
            PocomosQRCode::create($qr_batch);
        }
        return $this->sendResponse(true, 'QR codes created successfully.', $create_batch);
    }

    /**
     * API for create of Sales Status
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function assignQRCode(Request $request)
    {
        $v = validator($request->all(), [
            'group_id' => 'required|exists:pocomos_qr_code_groups,id',
            'user_id' => 'required|exists:orkestra_users,id',
            'code_id' => 'required',
            'note' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        //find batch id from code id
        $find_code = PocomosQRCode::where('data', $request->code_id)->first();

        $find_last_identifier = PocomosQRCode::where('batch_id', $find_code->batch_id)->where('group_id', $request->group_id)->orderBy('identifier', 'ASC')->first();
        if ($find_last_identifier) {
            $identifier = (int)($find_last_identifier->identifier) + 1;
        } else {
            $identifier = 1;
        }

        // Update Identifier value and group id into pocomos_qr_codes table
        $find_code->identifier = $identifier;
        $find_code->group_id = $request->group_id;
        $find_code->save();

        // find office_user_id from user id
        $find_id = PocomosCompanyOfficeUser::where('user_id', $request->user_id)->pluck('id')->first();

        // find session data for today, If session is already created then use it's id or else create data
        $session_create = PocomosQRCodeScanSession::where('group_id', $request->group_id)->where('date', date('Y-m-d'))->first();
        if (!$session_create) {
            // Insert scan data into pocomos_qr_code_scan_sessions
            $input =  [];
            $input['group_id'] = $request->group_id;
            $input['office_user_id'] = $find_id;
            $input['active'] = '1';
            $input['date'] = date('Y-m-d');

            $session_create = PocomosQRCodeScanSession::create($input);
        }
        //insert into pocomos_qr_code_scan_session_scans
        $input_scans = [];
        $input_scans['session_id'] = $session_create->id;
        $input_scans['code_id'] =  $find_code->id;
        $input_scans['note'] = $request->note;
        $input_scans['address_id'] = '45662';
        $input_scans['time_scanned'] = date('H:i:s');
        $input_scans['active'] = '1';
        $create_scans = PocomosQRCodeScanSessionScan::create($input_scans);

        return $this->sendResponse(true, 'QR code scaned successfully.', $create_scans);
    }
}
