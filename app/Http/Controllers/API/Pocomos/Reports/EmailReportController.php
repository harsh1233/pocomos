<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosEmailMessage;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosEmailTypeSetting;
use App\Models\Pocomos\PocomosEmail;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use Excel;
use App\Exports\ExportEmailReport;
use App\Jobs\ExportEmailReportJob;

class EmailReportController extends Controller
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
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $emailTypes = PocomosEmailTypeSetting::whereOfficeId($officeId)->whereActive(1)->get();

        return $this->sendResponse(true, 'Usage Report filters', [
            'email_types'      => $emailTypes,
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
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $emailType = $request->email_type;
        $emailStatus = $request->email_status;

        // $result = $this->getEmailHistory($startDate, $endDate, $emailType, $emailStatus, $request);

        $query = PocomosEmailMessage::select(DB::raw('
                                                    pocomos_email_messages.email_id as id,
                                                    pocomos_email_messages.recipient,
                                                    CONCAT(sou.first_name, \' \', sou.last_name) as sent_by,
                                                    pc.id as customer_id,
                                                    CONCAT(pc.first_name, \' \', pc.last_name) as customer_name,
                                                    rou.id as receiving_office_user_id,
                                                    CONCAT(rou.first_name, \' \', rou.last_name) as receiving_office_user,
                                                    pe.type,
                                                    pe.date_created as sent_date,
                                                    pocomos_email_messages.status,
                                                    pocomos_email_messages.date_modified as status_date
                                                '))
            ->join('pocomos_emails as pe', 'pocomos_email_messages.email_id', 'pe.id')
            ->leftJoin('pocomos_company_office_users as pcou', 'pe.office_user_id', 'pcou.id')
            ->leftJoin('orkestra_users as sou', 'pcou.user_id', 'sou.id')
            ->leftJoin('pocomos_company_office_users as pcour', 'pe.receiving_office_user_id', 'pcour.id')
            ->leftJoin('orkestra_users as rou', 'pcour.user_id', 'rou.id')
            ->leftJoin('pocomos_customer_sales_profiles as pcsp', 'pe.customer_sales_profile_id', 'pcsp.id')
            ->leftJoin('pocomos_customers as pc', 'pcsp.customer_id', 'pc.id')
            ->whereBetween('pe.date_created', [$startDate, $endDate])
        ;

        if ($emailType) {
            $query->where('pe.type', $emailType);
        }

        if ($emailStatus) {
            $query->where('pocomos_email_messages.status', $emailStatus);
        }

        if ($request->search) {
            // return 88;
            $search = $request->search;

            $formatDate = date('Y/m/d', strtotime($request->search));
            $date = '%'.str_replace("/", "-", $formatDate).'%';

            $query->where(function ($query) use ($search, $date) {
                $query->where('pocomos_email_messages.email_id', 'like', '%' . $search . '%')
                    ->orWhere('pocomos_email_messages.recipient', 'like', '%' . $search . '%')
                    ->orWhere('sou.first_name', 'like', '%' . $search . '%')
                    ->orWhere('sou.last_name', 'like', '%' . $search . '%')
                    ->orWhere(DB::raw('CONCAT(sou.first_name, \' \', sou.last_name)'), 'like', $search)
                    ->orWhere('rou.first_name', 'like', '%' . $search . '%')
                    ->orWhere('rou.last_name', 'like', '%' . $search . '%')
                    ->orWhere(DB::raw('CONCAT(rou.first_name, \' \', rou.last_name)'), 'like', $search)
                    ->orWhere('pe.type', 'like', '%' . $search . '%')
                    ->orWhere('pe.date_created', 'like', $date)
                    ->orWhere('pocomos_email_messages.status', 'like', '%' . $search . '%')
                    ->orWhere('pocomos_email_messages.date_modified', 'like', $date);
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $query->count();
        $query->skip($perPage * ($page - 1))->take($perPage);

        $results = $query->get();

        $user_transaction = [];
        $user_transaction['results'] = $results;
        $user_transaction['count'] = $count;

        $result = $user_transaction;

        if ($request->download) {
            // return Excel::download(new ExportEmailReport($data), 'ExportEmailReport.csv');
            $data = $this->exportEmailHistory($officeId, $startDate, $endDate, $emailType, $emailStatus);
            ExportEmailReportJob::dispatch($data);
            return $this->sendResponse(true, 'Email history export has started. You will be notified when it completes.');
        }

        return $this->sendResponse(true, 'List', [
            'Email' => $result['results'],
            'count' => $result['count'],
        ]);
    }

    public function view(request $request, $id)
    {
        $email = PocomosEmail::whereId($id)->whereOfficeId($request->office_id)->first();

        if (!$email) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Email Report']));
        }

        return $this->sendResponse(true, __('strings.details', ['name' => 'Email Report']), $email);
    }

    public function exportEmailHistory($officeId, $startDate, $endDate, $emailType, $emailStatus)
    {
        $sql = "SELECT m.id, m.email_id, m.recipient, m.recipient_name, m.status, m.date_created,
                CONCAT(u.first_name, ' ', u.last_name) AS sending_user, e.type AS email_type
                FROM pocomos_email_messages AS m
                JOIN pocomos_emails AS e ON m.email_id = e.id AND e.office_id = " . $officeId . "
                JOIN pocomos_company_office_users AS ou ON e.office_user_id = ou.id
                JOIN orkestra_users AS u ON ou.user_id = u.id
                WHERE e.date_created BETWEEN '" . $startDate . "' AND '" . $endDate . "' ";

        if ($emailType) {
            $sql .= ' AND e.type = "' . $emailType . '" ';
        }
        if ($emailStatus) {
            $sql .= ' AND m.status = "' . $emailStatus . '" ';
        }

        return DB::select(DB::raw($sql));
    }
}
