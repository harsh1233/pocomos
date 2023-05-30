<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosEmailMessage;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosEmailTypeSetting;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use Excel;
use App\Exports\ExportCancelledReport;

class UnansweredMessageController extends Controller
{
    use Functions;

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'seen' => 'required|boolean',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $seenCondition = '';
        if ($request->seen == 0) {
            $seenCondition = ' AND seen = 0';
        }

        $sql = 'SELECT sms.*, c.id AS customer_id, CONCAT(c.first_name, \' \', c.last_name) as customer_name
                FROM pocomos_sms_usage AS sms
                JOIN (
                      SELECT max(id) as id
                      FROM pocomos_sms_usage
                     WHERE office_id = ' . $officeId . ' AND answered = 0  ' . $seenCondition . ' GROUP BY sender_phone_id
                  ) as sms2 ON sms.id = sms2.id
                JOIN pocomos_customers_phones AS cp ON sms.sender_phone_id = cp.phone_id
                JOIN pocomos_customer_sales_profiles AS csp ON cp.profile_id = csp.id
                JOIN pocomos_customers AS c ON csp.customer_id = c.id
                WHERE sms.inbound = 1 
               ';

        if ($request->search) {
            $search = '"%' . $request->search . '%"';
            $sql .= ' AND (
                         c.first_name LIKE ' . $search . '
                        OR c.last_name LIKE ' . $search . '
                        OR sms.message_part LIKE ' . $search . '
                        OR sms.date_created LIKE ' . $search . '
                        )';
        }

        $sql .= " ORDER BY sms.date_created DESC";

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $list = DB::select(DB::raw(($sql)));

        return $this->sendResponse(true, 'List', [
            'Unanswered_Message' => $list,
            'count' => $count,
        ]);
    }

    public function markMessageAsRead(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $sql = 'UPDATE `pocomos_sms_usage` SET seen = 1 WHERE office_id = ' . $officeId . ' ';

        if ($request->message_id) {
            $sql .= ' AND id = ' . $request->message_id . ' ';
        }

        DB::select(DB::raw($sql));

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Message marked as read']));
    }


    public function viewFullConversation(Request $request, $custId, $phoneId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $customer = PocomosCustomer::with('contact_address')->whereId($custId)->first();

        $phone = PocomosPhoneNumber::whereId($phoneId)->first();

        $pocomosPestContract = PocomosPestContract::join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->where('pcu.id', $custId)
            ->orderBy('pc.status', 'ASC')
            ->first();

        return $this->sendResponse(
            true,
            __('strings.details', ['name' => 'Conversation']),
            [
                'customer' => $customer,
                'phone' => $phone,
                'contract' => $pocomosPestContract,
            ]
        );
    }
}
