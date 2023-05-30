<?php

namespace App\Http\Controllers\API\Pocomos\Reports\AccountStatus;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTeam;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\PocomosMembership;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use App\Exports\ExportProductionReport;
use Excel;

class ProductionReportController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'group_by'  => 'in:branch,salespeople-by-branches,branches-by-company,salespeople-by-company,team'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id', 'name']);

        if ($request->group_by == 'team') {
            $teams = PocomosTeam::whereOfficeId($officeId)->whereActive(true)->get(['id', 'office_id', 'name']);
        } elseif ($request->group_by == 'salespeople-by-branches') {
            $officeIdsArr = $request->office_ids ? $request->office_ids : $branches->pluck('id')->toArray();
            $officeIds    = implode(',', $officeIdsArr);

            $sql = 'SELECT DISTINCT ou.profile_id as id, CONCAT(u.first_name, \' \', u.last_name) as name
                                FROM pocomos_salespeople s
                                JOIN pocomos_company_office_users ou ON s.user_id = ou.id
                                JOIN orkestra_users u ON ou.user_id = u.id ';

            $sql .= 'WHERE ou.office_id IN (' . $officeIds . ')';

            if ($request->search_term) {
                $searchTerm = "'" . $request->search_term . "'";
                $sql .= ' AND (u.first_name LIKE ' . $searchTerm . ' OR u.last_name LIKE ' . $searchTerm . ' OR u.username LIKE ' . $searchTerm . ' OR CONCAT(u.first_name, \' \', u.last_name) LIKE ' . $searchTerm . ')';
            }

            $sql .= ' ORDER BY name';

            $salesPeople = DB::select(DB::raw($sql));
        } elseif ($request->group_by == 'salespeople-by-company') {
            $officeIdsArr = $branches->pluck('id')->toArray();
            $officeIds    = implode(',', $officeIdsArr);

            $sql = 'SELECT DISTINCT ou.profile_id as id, CONCAT(u.first_name, \' \', u.last_name) as name
                                FROM pocomos_salespeople s
                                JOIN pocomos_company_office_users ou ON s.user_id = ou.id
                                JOIN orkestra_users u ON ou.user_id = u.id ';

            $sql .= 'WHERE ou.office_id IN (' . $officeIds . ')';

            if ($request->search_term) {
                $searchTerm = "'" . $request->search_term . "'";
                $sql .= ' AND (u.first_name LIKE ' . $searchTerm . ' OR u.last_name LIKE ' . $searchTerm . ' OR u.username LIKE ' . $searchTerm . ' OR CONCAT(u.first_name, \' \', u.last_name) LIKE ' . $searchTerm . ')';
            }

            $sql .= ' ORDER BY name';

            $salesPeople = DB::select(DB::raw($sql));
        }

        return $this->sendResponse(
            true,
            'Production Report',
            [
                'branches'          => $branches,
                'teams'             => isset($teams) ? $teams : null,
                // 'sales_people'      => isset($salesPeople) ? $salesPeople : null,
            ]
        );
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'date_type' => 'in:initial,signup,account-creation',
            'group_by'  => 'in:branch,salespeople-by-branches,branches-by-company,salespeople-by-company,team',
            'office_ids'  => 'required|array',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $officeId = $pocomosCompanyOffice->id;
        $parentId = PocomosCompanyOffice::whereId($officeId)->first()->parent_id;
        $officeId = $parentId ? $parentId : $officeId;

        $branchIds = $request->office_ids ? implode(',', $request->office_ids) : null;

        $dateType = $request->date_type;

        // $view = $request->view;
        $view = 'asOf';

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $groupBy = $request->group_by;

        $showRookies = $request->show_rookies;
        $showVeterans = $request->show_veterans;

        $teamId = $request->team_id ? $request->team_id : null;

        $salesPeopleIds = $request->sales_people_ids ? implode(',', $request->sales_people_ids) : null;
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $search = $request->search;

        $results = $this->getData($view, $dateType, $startDate, $endDate, $branchIds, $salesPeopleIds, $groupBy, $showRookies, $showVeterans, $teamId, $page, $perPage, $search);

        if ($request->download) {
            return Excel::download(new ExportProductionReport($results['data']), 'ExportProductionReport.csv');
        }

        $data = [
            'reports' => $results['data'],
            'count' => $results['count']
        ];

        return $this->sendResponse(true, 'Production Report search result', $data);
    }

    public function getData($view, $dateType, $startDate, $endDate, $branchIds, $salesPeopleIds, $groupBy, $showRookies, $showVeterans, $teamId, $page, $perPage, $search)
    {
        $qualifiers = "and ou.office_id in (".$branchIds.")" . ($salesPeopleIds ? " and oup.id in (".$salesPeopleIds.")" : "");
        $group = null;

        switch ($groupBy) {
            case 'team':
                if ($showRookies) {
                    if (!$showVeterans) {
                        $qualifiers .= ' and sp.experience <= 1';
                    }
                } elseif ($showVeterans) {
                    $qualifiers .= ' and sp.experience > 1';
                } else {
                    // return array();
                    $qualifiers .= ' ';
                }

                // return $teamId;
                if ($teamId) {
                    $team = PocomosTeam::whereId($teamId)->first();
                }

                $salesPeopleIds = [];
                if (isset($team)) {
                    $q = PocomosMembership::with('ork_user_details')->whereTeamId($teamId)->get()->toArray();
                    foreach ($q as $e) {
                    //    return $e['ork_user_details']['office_user_details']['user_details']['id'];
                        $salesPeopleIds[] = $e['ork_user_details']['office_user_details']['user_details']['id'];
                    }
                //    return $salesPeopleIds;
                } else {
                    // return array();
                }

                break;

            case 'salespeople-by-company':
                if ($showRookies) {
                    if (!$showVeterans) {
                        $qualifiers .= ' and sp.experience <= 1';
                    }
                } elseif ($showVeterans) {
                    $qualifiers .= ' and sp.experience > 1';
                } else {
                    // return array();
                    $qualifiers .= ' ';
                }

                break;
        }

        if ($dateType === 'initial') {
            $dateConstraint = 'COALESCE(j.date_completed, j.date_scheduled) BETWEEN "' . $startDate . '" AND "' . $endDate . '"';
            $dateTypeColumn = 'COALESCE(j.date_completed, j.date_scheduled)';
        } elseif ($dateType === 'signup') {
            $dateConstraint = 'c.date_start BETWEEN "' . $startDate . '" AND "' . $endDate . '"';

            $dateTypeColumn = 'c.date_start';
        } else {
            $dateConstraint = 'csp.date_signed_up BETWEEN "' . $startDate . '" AND "' . $endDate . '"';

            $dateTypeColumn = 'csp.date_signed_up';
        }

        if ($salesPeopleIds) {
            // $parameters['salesperson'] = $salespeople;
            // $types['salesperson'] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
        }

        // return $endDate;
        $year = date('Y', strtotime($endDate));
        $month = date('n', strtotime($endDate));
        $week = date('W', strtotime($endDate));
        $day = date('j', strtotime($endDate));

        if ($view == 'asOf') {
            $sql = "SELECT
                office_name,
                salesperson_name,
                CASE WHEN month is null THEN 'Year'
                     WHEN week is null THEN 'Month'
                     WHEN day is null THEN 'Week'
                     ELSE 'Day'
                END as duration,
                total_sold,
                total_serviced

                from (
                    SELECT CONCAT(u.first_name, ' ', u.last_name) as salesperson_name,
                    o.name                                  as office_name,
                    count(c.id) 						      as total_sold,
                    sum(if(ss.serviced = 1, 1, 0)) 		  as total_serviced,
                    csp.date_signed_up 					  as signup_date,
                    DATE_FORMAT(c.date_start,\"%c/%d/%Y\") as contract_date,
                    COALESCE(j.date_completed, j.date_scheduled) as job_date,
                    DATE_FORMAT(COALESCE(j.date_completed, j.date_scheduled),\"%c/%d/%Y\") as initial_date,
                    YEAR($dateTypeColumn)  as year,
                    MONTH($dateTypeColumn) as month,
                    WEEK($dateTypeColumn, 1) as week,
                    DAY($dateTypeColumn)   as day

                    from pocomos_contracts c
                    join pocomos_customer_sales_profiles csp on c.profile_id = csp.id
                    join pocomos_salespeople s on c.salesperson_id = s.id
                    join pocomos_company_office_users ou on s.user_id = ou.id
                    join pocomos_company_office_user_profiles oup on ou.profile_id = oup.id
                    join pocomos_salesperson_profiles sp on oup.id = sp.office_user_profile_id
                    join pocomos_company_offices o on ou.office_id = o.id
                    join orkestra_users u on ou.user_id = u.id
                    LEFT JOIN pocomos_sales_status ss on c.sales_status_id = ss.id
                    LEFT JOIN pocomos_pest_contracts pcc ON pcc.contract_id = c.id
                    LEFT JOIN pocomos_jobs j ON j.contract_id = pcc.id AND j.type = \"Initial\"
                   WHERE $dateConstraint
                   " . $qualifiers . "
                   GROUP BY " . ($groupBy == 'branches-by-company' ? 'office_name,' : '') . " salesperson_name,
                            year,
                            month,
                            week,
                            day with ROLLUP
                    ) t WHERE ((t.year = ".$year." and t.month = ".$month." and t.day = ".$day.")
                    OR (t.year = ".$year." and t.month = ".$month." and t.week = ".$week." and t.day is null)
                    OR (t.year = ".$year." and t.month = ".$month." and t.week is null and  t.day is null)
                    OR (t.year is null and t.month is null and t.week is null and  t.day is null))
                    AND salesperson_name IS NOT NULL
                    and
                    (salesperson_name LIKE '%$search%' OR total_sold LIKE '%$search%' OR
                                                            total_serviced LIKE '%$search%') 
                    ORDER BY salesperson_name, FIELD(duration, 'Day','Week','Month','Year')
                ";
        } else {
            $sql = "SELECT
                    o.name                                        as office_name,
                    CONCAT(u.first_name, ' ', u.last_name) as salesperson_name,
                    count(c.id)                                   as total_sold,
                    sum(if(ss.serviced = 1, 1, 0))                as total_serviced,
                    j.date_completed, j.date_scheduled,
                    DATE(SUBDATE($dateTypeColumn, WEEKDAY($dateTypeColumn)+1)) as duration
                    from pocomos_contracts c
                   LEFT join pocomos_customer_sales_profiles csp on c.profile_id = csp.id
                   LEFT join pocomos_salespeople s on c.salesperson_id = s.id
                   LEFT join pocomos_company_office_users ou on s.user_id = ou.id
                   LEFT join pocomos_company_office_user_profiles oup on ou.profile_id = oup.id
                   LEFT join pocomos_salesperson_profiles sp on oup.id = sp.office_user_profile_id
                   LEFT join pocomos_company_offices o on ou.office_id = o.id
                   LEFT join orkestra_users u on ou.user_id = u.id
                   LEFT JOIN pocomos_sales_status ss on c.sales_status_id = ss.id
                   LEFT JOIN pocomos_pest_contracts pcc ON pcc.contract_id = c.id
                   LEFT JOIN pocomos_jobs j ON j.contract_id = pcc.id AND j.type = \"Initial\"
                    WHERE
                    (salesperson_name LIKE '%$search%' OR total_sold LIKE '%$search%' OR total_serviced LIKE '%$search%')
                      $dateConstraint
                    " . $qualifiers . "
                    GROUP BY salesperson_name, YEARWEEK($dateTypeColumn)";
        }
        $count = count(DB::select(DB::raw($sql)));

        $sql .= " LIMIT $perPage offset $page";

        $results = DB::select(DB::raw($sql));

        // $results[] = [
        //     "office_name"=> "Pocomos Software 934",
        //     "salesperson_name"=> "first_name last_name",
        //     "total_sold"=> 1,
        //     "total_serviced"=> "1",
        //     "date_completed"=> null,
        //     "date_scheduled"=> "2022-03-09",
        //     "duration"=> "2022-03-06"
        // ];

        $w=0;
        $data = [];
        foreach ($results as $row) {
            $row = (array)$row;
            $office = $row['office_name'];
            $salesperson = $row['salesperson_name'];
            $sold = $row['total_sold'];
            $serviced = $row['total_serviced'];
            $duration = $row['duration'];
            $durations = ['Year', 'Month', 'Day', 'Week'];

            if ($groupBy == 'branches-by-company') {
                $key = $office;
            } else {
                $key = $salesperson;
            }

            // if (!array_key_exists($key, $data)) {
            //     $data[$key] = array();
            // }

            // if (!array_key_exists($duration, $data)) {
            //     $data[$duration] = array('Sold' => 0, 'Serviced' => 0);
            // }

            // if (array_key_exists($duration, $data[$key])) {
            //     $data[$key][$duration]['sold'] += $sold;
            //     $data[$key][$duration]['serviced'] += $serviced;
            // } else {
            //     $data[$key][$duration] = array('sold' => $sold, 'serviced' => $serviced);
            // }

            // $i = 0;
            foreach ($durations as $d) {
                if ($duration == $d) {
                    $data[$w]['name'] = $key;
                    $data[$w][$d] = array('sold' => $sold, 'serviced' => $serviced);
                } else {
                    $data[$w][$d] = array('sold' => 0, 'serviced' => 0);
                }
                // $data[1]['name'] = 'David';
                // $data[1][$q] = array('sold' => 0, 'serviced' => 0);
            }

            // return $data[$duration];
            // $data[$duration]['Sold'] += $sold;
            // $data[$duration]['Serviced'] += $serviced;

            $w++;
        }

        return array("data" => $data, "count" => $count);
    }
}
