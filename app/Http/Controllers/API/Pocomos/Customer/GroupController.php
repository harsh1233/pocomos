<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosQRCodeGroup;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosQRCodeScanSession;
use App\Models\Pocomos\PocomosQRCode;

class GroupController extends Controller
{
    use Functions;

    /**
     * API for create of QR-code group
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'customer_profile_id' => 'required|exists:pocomos_customer_sales_profiles,id',
            'name' => 'required',
            'description' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // find customer profile
        $find_customer = PocomosCustomerSalesProfile::where('id', $request->customer_profile_id)->first();
        if (!$find_customer) {
            return $this->sendResponse(false, 'Customer profile not found.');
        }

        $input = [];
        $input['profile_id'] = $request->customer_profile_id;
        $input['name'] = $request->name;
        $input['description'] = $request->description ?? '' ;
        $input['active'] = '1';
        $input['autoinc_identifier'] = '1';

        $create_group = PocomosQRCodeGroup::create($input);
        return $this->sendResponse(true, 'Group Created Successfully!', $create_group);
    }

    /**
     * API for edit of QR-code group
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $v = validator($request->all(), [
            'group_id' => 'required|exists:pocomos_qr_code_groups,id',
            'name' => 'required',
            'description' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // find QR-Code Group
        $find_qr_group = PocomosQRCodeGroup::where('id', $request->group_id)->first();
        if (!$find_qr_group) {
            return $this->sendResponse(false, 'QR Code Group not found.');
        }

        $find_qr_group['name'] = $request->name ?? '';
        $find_qr_group['description'] = $request->description ?? '';
        $find_qr_group->save();
        return $this->sendResponse(true, 'Group Edited Successfully!', $find_qr_group);
    }

    /**
     * API for List of QR-code group
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'customer_profile_id' => 'required|exists:pocomos_customer_sales_profiles,id',
            'user_id' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        // find customer profile
        $find_customer = PocomosCustomerSalesProfile::where('id', $request->customer_profile_id)->first();
        if (!$find_customer) {
            return $this->sendResponse(false, 'Customer profile not found.');
        }
        // find QR-Code Group
        $find_qr_group = PocomosQRCodeGroup::where('profile_id', $request->customer_profile_id)->get();

        // find scanned qr code based on group
        $find_qr_group->map(function ($data) use ($request) {
            $scan_session = PocomosQRCode::where('group_id', $data['id'])->with('scanDetails.session_details.user_details')->get();
            // $scan_session = PocomosQRCodeScanSession::where('group_id',$data['id'])->where('office_user_id',$request->user_id)->with('scan_details','user_details')->get();
            // $total_scanned_data = PocomosQRCodeScanSessionScan::whereIn('session_id',$scan_session)->groupBy('address_id')->get();
            $data['records'] = $scan_session;
        });

        return $this->sendResponse(true, 'List of QR code groups', $find_qr_group);
    }
    /**
     * API for Delete QR-code group
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        // find QR-Code Group
        $find_qr_group = PocomosQRCodeGroup::where('id', $id)->first();
        if (!$find_qr_group) {
            return $this->sendResponse(false, 'QR Code Group not found.');
        }
        $find_qr_group->delete();
        return $this->sendResponse(true, 'QR code group Deleted Successfully', $find_qr_group);
    }

    /**
     * API for Delete QR-code group
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function history($id)
    {
        $v = validator($request->all(), [
            'group_id' => 'required|exists:pocomos_qr_code_groups,id',
            'user_id' => 'required|exists:pocomos_company_office_users,id',
            'code_id' => 'required|exists:pocomos_qr_codes,id',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        // Scanned history based on code id
        $scan_session = PocomosQRCodeScanSession::where('group_id', $request->group_id)->where('office_user_id', $request->user_id)->with('user_details')
            ->join('pocomos_qr_code_scan_session_scans', 'pocomos_qr_code_scan_sessions.id', '=', 'pocomos_qr_code_scan_session_scans.session_id')
            ->where('pocomos_qr_code_scan_session_scans.code_id', $request->code_id)
            ->get();
        // $total_scanned_data = PocomosQRCodeScanSessionScan::whereIn('session_id',$scan_session)->where('code_id',$request->code_id)->get();
        // $data['records'] = $scan_session;

        return $this->sendResponse(true, 'List of QR code groups', $scan_session);
    }
}
