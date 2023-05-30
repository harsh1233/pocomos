<?php

namespace App\Http\Controllers\API\Pocomos\Routing;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;

use DB;

class MonthlyCalendarController extends Controller
{
    use Functions;

    public function get(Request $request, $year, $month)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        $officeId = $request->office_id;
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $start_time = strtotime("01-" . $month . "-" . $year);
        $end_time = strtotime("+1 month", $start_time);
        $dateStart = date('Y-m-d', $start_time);
        $dateEnd = date('Y-m-d', $end_time);

        $sql = "SELECT r.date_scheduled,
               SUM(IF(t.network = 'Card' AND csp.autopay = 0,t.amount,0)) as creditCollected,
               SUM(IF(t.network = 'ACH',t.amount,0)) as achCollected,
               SUM(IF(t.network in ('Card', 'ACH') AND csp.autopay = 1,t.amount,0)) as autopayCollected,
               SUM(IF(t.network not in ('Card','ACH') , IF(t.network = 'Points', t.amount/100,t.amount),0)) as otherCollected,
               SUM(IF(i.status <> 'Paid' AND csp.autopay = 0 AND acct.ccOnFile,i.balance,0)) as creditOutstanding,
               SUM(IF(i.status <> 'Paid' AND csp.autopay = 0 AND acct.achOnFile AND NOT acct.ccOnFile,i.balance,0)) as achOutstanding,
               SUM(IF(i.status <> 'Paid' AND csp.autopay = 1,i.balance,0)) as autopayOutstanding,
               SUM(IF(i.status <> 'Paid' AND ((acct.ccOnFile is NULL) OR (acct.ccOnFile = 0 AND acct.achOnFile = 0)) ,i.balance,0)) as otherOutstanding,
               COUNT(DISTINCT j.id) as services
                            FROM pocomos_routes r
                                JOIN pocomos_route_slots rs ON rs.route_id = r.id
                                JOIN pocomos_jobs j ON j.slot_id = rs.id
                                JOIN pocomos_invoices i ON j.invoice_id = i.id
                                JOIN pocomos_contracts c ON i.contract_id = c.id
                                JOIN pocomos_agreements a ON c.agreement_id = a.id
                                JOIN pocomos_company_offices o ON a.office_id = o.id
                                JOIN pocomos_customer_sales_profiles csp on c.profile_id = csp.id
                                LEFT JOIN pocomos_user_transactions it on (i.id = it.invoice_id and it.type = 'Invoice')
                                LEFT JOIN orkestra_transactions t on it.transaction_id = t.id
                                LEFT JOIN (select profile_id, MAX(IF(acc.type = 'CardAccount',1,0)) as ccOnFile, MAX(IF(acc.type = 'BankAccount',1,0)) as achOnFile
                                              from pocomos_customers_accounts ca
                                              join orkestra_accounts acc on ca.account_id = acc.id AND acc.active = 1
                                              WHERE acc.type in ('BankAccount', 'CardAccount')
                                              GROUP BY profile_id ) acct on acct.profile_id = csp.id
                            WHERE o.id = ".$officeId."
                                AND r.date_scheduled BETWEEN ".$dateStart." AND ".$dateEnd."
                                AND i.status NOT IN ('Cancelled')
                            GROUP BY r.date_scheduled";

        $result = DB::select(DB::raw($sql));

        $dailyData = $weeklyData = array();
        $monthlyData = array(
            'revenue' => 0,
            'services' => 0,
        );


        for ($i = $start_time; $i < $end_time; $i += 86400) {
            $weekNumber = date('W', $i);
            $date = date('Y-m-d', $i);
            $weekDay = date('D', $i);

            if (!isset($weeklyData[$weekNumber])) {
                $weeklyData[$weekNumber] = array(
                    'revenue' => 0,
                    'services' => 0,
                );
                $weeklyData[$weekNumber]['start'] = $date;
            }

            if ($weekDay === 'Sat') {
                $weeklyData[$weekNumber]['end'] = $date;
            }

            $q=0;
            foreach ($result as $key => $row) {
                // if ($row->date_scheduled === $date) {
                $dayRevenue = $row->creditCollected + $row->creditOutstanding +
                    $row->autopayCollected + $row->autopayOutstanding +
                    $row->achCollected + $row->achOutstanding +
                    $row->otherCollected + $row->otherOutstanding;

                $monthlyData['revenue'] += $dayRevenue;
                $monthlyData['services'] += $row->services;

                $weeklyData[$weekNumber]['revenue'] += $dayRevenue;
                $weeklyData[$weekNumber]['services'] += $row->services;

                $dailyData[$q] = array(
                    'date' => $row->date_scheduled,
                    'revenue' => number_format($dayRevenue, 2),
                    'credit' => array('total' => number_format($row->creditCollected + $row->creditOutstanding, 2), 'collected' => number_format($row->creditCollected, 2), 'outstanding' => number_format($row->creditOutstanding, 2)),
                    'autopay' => array('total' => number_format($row->autopayCollected + $row->autopayOutstanding, 2), 'collected' => number_format($row->autopayCollected, 2), 'outstanding' => number_format($row->autopayOutstanding, 2)),
                    'ach' => array('total' => number_format($row->achCollected + $row->achOutstanding, 2), 'collected' => number_format($row->achCollected, 2), 'outstanding' => number_format($row->achOutstanding, 2)),
                    'other' => array('total' => number_format($row->otherCollected + $row->otherOutstanding, 2), 'collected' => number_format($row->otherCollected, 2), 'outstanding' => number_format($row->otherOutstanding, 2)),
                    'services' => number_format($row->services),
                    'status' => 'Below Capacity',
                );
                unset($result[$key]);
                $q++;
                // }
            }
        }

        foreach ($weeklyData as $weekNumber => $data) {
            $weeklyData[$weekNumber]['revenue'] = number_format($weeklyData[$weekNumber]['revenue'], 2);
            $weeklyData[$weekNumber]['services'] = number_format($weeklyData[$weekNumber]['services']);


//            This is in case the last week doesn't have an end date.
            if (!isset($weeklyData[$weekNumber]['end'])) {
                $weeklyData[$weekNumber]['end'] = date('Y-m-d', strtotime("-1 day", $end_time));
            }

//            Because Freedom, we need to make the weeks start on Sunday. But we need to not touch the first week.
            if (!(strtotime($weeklyData[$weekNumber]['start']) == $start_time)) {
                $weeklyData[$weekNumber]['start'] = date('Y-m-d', strtotime($weeklyData[$weekNumber]['start'] . ' -1 day'));
            }

            // This is to check if there is a bugged week that is 30 days long. If it exists = we kill it. It happens if the first day of the month is a sunday.
            $date1 = date_create($weeklyData[$weekNumber]['start']);
            $date2 = date_create($weeklyData[$weekNumber]['end']);
            $diff = date_diff($date1, $date2)->format("%R%a");

            if ($diff > 8) {
                unset($weeklyData[$weekNumber]);
            }
        }


        $monthlyData['revenue'] = number_format($monthlyData['revenue'], 2);
        $monthlyData['services'] = number_format($monthlyData['services']);

        $weeks = array_values($weeklyData);

        return $this->sendResponse(true, __('strings.details', ['name' => 'Monthly calendar']), [
            'weeks' => $weeks,
            'days' => $dailyData,
            'month' => $monthlyData,
        ]);
    }
}
