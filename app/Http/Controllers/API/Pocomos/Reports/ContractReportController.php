<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosJobProduct;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class ContractReportController extends Controller
{
    use Functions;

    /**
     * API for update Form Letter
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function salesStatus(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pocomosSalesStatus = PocomosSalesStatus::whereActive(true)->whereOfficeId($request->office_id)->get(['id','name']);

        return $this->sendResponse(true, 'Sales statuses', $pocomosSalesStatus);
    }

    public function getSalesPeopleByOffice(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'salesperson_ids' => 'array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $exclude = $request->salesperson_ids ? $request->salesperson_ids : [];

        $sql = 'SELECT s.id, CONCAT(u.first_name, \' \', u.last_name) as name
                                FROM pocomos_salespeople s
                                JOIN pocomos_company_office_users ou ON s.user_id = ou.id AND ou.office_id = ' . $request->office_id . '
                                JOIN orkestra_users u ON ou.user_id = u.id ';
        if ($exclude) {
            $sql .= ' AND s.id NOT IN (' . implode(',', $exclude) . ')';
        }

        if ($request->search) {
            $search = "%".$request->search."%";

            $sql .= ' AND (u.first_name LIKE "'.$search.'" OR u.last_name LIKE "'.$search.'" OR u.username LIKE "'.$search.'" OR CONCAT(u.first_name, \' \', u.last_name) LIKE "'.$search.'")';
        }

        $sql .= ' ORDER BY u.first_name, u.last_name';

        $count = count(DB::select(DB::raw($sql)));
        /**If result data are from DB::row query then `true` else `false` normal laravel get listing */
        $paginateDetails = $this->getPaginationDetails($page, $perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $salesPeople = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'Salespeople', [
            'salesPeople' => $salesPeople,
            'count' => $count
        ]);
    }

    public function summary($id)
    {
        $pocomosSalesPeople = PocomosSalesPeople::with('office_user_details.user_details')->whereId($id)->first();

        if (!$pocomosSalesPeople) {
            return $this->sendResponse(false, 'Sales Person not found.');
        }

        $salesPersonName = $pocomosSalesPeople->office_user_details->user_details->full_name;
        $salesPersonId = $pocomosSalesPeople->id;

        // return $this->getSalespersonSummary($pocomosSalesPeople, $salesPersonId);

        $results = array(
            'stats' => $this->getSalesPersonSummary($pocomosSalesPeople, $salesPersonId),
            'name'  => $salesPersonName,
            'id'    => $salesPersonId
        );

        return $this->sendResponse(true, 'Contract Report', $results);
    }

    public function getSalesPersonSummary($pocomosSalesPeople, $salesPersonId)
    {
        $officeId = $pocomosSalesPeople->office_user_details->office_id;

        if ($officeId) {
            $parentId = PocomosCompanyOffice::whereId($officeId)->first()->parent_id;
            $officeId = $parentId ? $parentId : $officeId;
        } else {
            $officeId = 0;
        }

        $sql = "SELECT
                    ss.name AS name,
                    ss.paid AS paid,
                    ss.serviced AS serviced,
                    COUNT(c.sales_status_id) AS count,
                    c.active AS active
                FROM pocomos_sales_status ss
                LEFT OUTER JOIN pocomos_contracts c ON c.sales_status_id = ss.id AND c.salesperson_id = ".$salesPersonId."
                WHERE ss.office_id = ".$officeId."
                GROUP BY name, active
                ORDER BY ss.id ASC";

        $stats = DB::select(DB::raw($sql));

        $totalInactive = 0;
        $total = 0;
        $paid = 0;
        $serviced = 0;
        foreach ($stats as $key => $stat) {
            $count = (int)$stat->count;
            $total += $count;
            if ((int)$stat->active === 0) {
                $totalInactive += $count;
                if (array_key_exists($key - 1, $stats) && $stats[$key - 1]->name === $stat->name) {
                    $stats[$key - 1]['count'] = (int)$stats[$key - 1]['count'] + $count;
                    unset($stats[$key]);
                } else {
                    unset($stats[$key]->active);
                }
            } else {
                if (isset($stat->paid) && $stat->paid) {
                    $paid += $count;
                }
                if (isset($stat->serviced) && $stat->serviced) {
                    $serviced += $count;
                }
                unset($stats[$key]->active);
            }
        }

        $stats[] = array('name' => 'total', 'count' => $total);
        $stats[] = array('name' => 'totalActive', 'count' => $total - $totalInactive);
        $stats[] = array('name' => 'paid', 'count' => $paid);
        $stats[] = array('name' => 'serviced', 'count' => $serviced);

        foreach ($stats as $stat) {
            $stat = (array)$stat;
            $results[str_replace(' ', '_', $stat['name'])] = $stat['count'];
        }

        return $results;
    }


    public function reportcontractsummary(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'office_id' => 'integer|min:1',
            'salesperson_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;

        $pocomosnote = PocomosJobProduct::join('booking_process_payment_details as bpd', 'bpd.booking_process_id', '=', 'booking_process_course_details.booking_process_id')
                ->where('j.dateCompleted BETWEEN :startDate AND :endDate')
                ->andWhere('csp.office = :office')
                ->groupBy('ap.product')
                ->setParameter(':startDate', $data['startDate'])
                ->setParameter(':endDate', $data['endDate']->modify('+23 hours, 59 minutes, 59 seconds'))
                ->setParameter('office', $office);

        if ($request->input('types')) {
            $pocomosnote = $pocomosnote->whereIn('interaction_type', $request->input('types'));
        }

        if ($request->input('officeUser')) {
            $pocomosnote = $pocomosnote->where('user_id', $request->input('officeUser'));
        }

        $pocomosnotecount = $pocomosnote->count();

        /**For pagination */
        if ($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $pocomosnote->skip($perPage * ($page - 1))->take($perPage);
        }

        $pocomosnote = $pocomosnote
            ->orderBy('id', 'DESC')
            ->get();

        $data = [
            'pocomosnote' => $pocomosnote,
            'count' => $pocomosnotecount
        ];

        return $this->sendResponse(true, 'Data of reports.', $data);
    }
}
