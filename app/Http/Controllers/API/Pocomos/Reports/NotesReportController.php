<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class NotesReportController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $PocomosCompanyOfficeUsers = PocomosCompanyOfficeUser::with('user_details:id,first_name,last_name')
            ->whereOfficeId($officeId)->whereActive(1)->get();

        return $this->sendResponse(true, 'Notes Report filters', [
            'office_users'    => $PocomosCompanyOfficeUsers,
        ]);
    }

    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $noteTypes = $request->note_types ? $request->note_types : [];

        $customerStatus = $request->customer_status;

        $officeUser = $request->office_user;

        $searchTerm = $request->search_term ? $request->search_term : null;

        $sql = "SELECT n.*,
                    c.id AS customer_id,
                    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS employee_name
                    FROM pocomos_notes AS n
                    JOIN pocomos_customers_notes AS cn ON n.id = cn.note_id
                    JOIN pocomos_customers AS c ON cn.customer_id = c.id
                    JOIN pocomos_customer_sales_profiles AS csp ON c.id = csp.customer_id
                    JOIN orkestra_users AS u ON n.user_id = u.id
                    WHERE c.active = 1 AND n.active = 1
                    AND csp.active = 1 AND csp.office_id = " . $officeId . " ";

        if ($request->search) {
            $search = "'%" . $request->search . "%'";
            $sql .= ' AND c.first_name LIKE ' . $search . ' OR c.last_name LIKE ' . $search . ' OR u.first_name LIKE ' . $search . ' OR u.last_name LIKE ' . $search . ' OR n.interaction_type LIKE ' . $search . ' OR n.date_created LIKE ' . $search . ' OR n.summary LIKE ' . $search . '';
        }

        // if ($request->search_term) {
        //     $searchTerm = "'".$request->search_term."'";
        //     $sql .= ' AND (u.first_name LIKE '.$searchTerm.' OR u.last_name LIKE '.$searchTerm.' OR u.username LIKE '.$searchTerm.' OR CONCAT(u.first_name, \' \', u.last_name) LIKE '.$searchTerm.')';
        // }

        if ($noteTypes) {
            $noteTypesStr = implode(',', $noteTypes);
            $sql .= ' AND n.interaction_type IN ('.$noteTypesStr.')';
        }

        if ($customerStatus) {
            $customerStatus = implode(',', $customerStatus);
            $sql .= ' AND c.status IN ('.$customerStatus.')';
        }

        if ($startDate) {
            $sql .= ' AND n.date_created >= "'.$startDate.'" ';
        }

        if ($endDate) {
            $sql .= ' AND n.date_created <= "'.$endDate.'" ';
        }

        if ($officeUser) {
            $sql .= ' AND n.user_id = '.$officeUser.' ';
        }

        // if($searchTerm !== null && strlen($searchTerm) > 0) {
        //     $sql .= " AND (
        //                     CONCAT(c.first_name, ' ', c.last_name) LIKE '%$searchTerm%'
        //                     OR n.summary LIKE '%$searchTerm%'
        //                 )";
        // }

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $result = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'List', [
            'Notes' => $result,
            'count' => $count,
        ]);
    }
}
