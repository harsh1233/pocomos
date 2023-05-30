<?php

namespace App\Http\Controllers\API\Pocomos\SalesTracker;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosJobProduct;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosCommissionSetting;
use App\Models\Pocomos\PocomosOfficeBonuse;
use App\Models\Pocomos\PocomosCommissionBonuse;
use App\Models\Pocomos\PocomosCommissionDeduction;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\PocomosReportContractSnapshot;
use App\Models\Orkestra\OrkestraUser;
use DB;

class CommissionReportController extends Controller
{
    use Functions;
    public const LAST_SEARCH_KEY = '__commission_report_last_search';
    public const QUALIFIES_FOR_COMMISION = '__qualifies_for_commission';
    /**
     * API for Commission Report
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function getFilters(Request $request)
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

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id','name']);

        return $this->sendResponse(true, 'Commission Report filters', [
            'branches' => $branches,
        ]);
    }

    public function salesPeopleByOffice($id)
    {
        $sql = 'SELECT s.id, CONCAT(u.first_name, \' \', u.last_name) as name
                    FROM pocomos_salespeople s
                    JOIN pocomos_company_office_users ou ON s.user_id = ou.id AND ou.office_id ='.$id.'
                    JOIN orkestra_users u ON ou.user_id = u.id WHERE 1 = 1 ';

        $sql .= ' ORDER BY u.first_name, u.last_name';

        $salesPeople = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'Salespeople office wise', $salesPeople);
    }

    public function calculate(Request $request)
    {
        $v = validator($request->all(), [
            'office_id'         => 'required',
            'start_date'        => 'required',
            'end_date'          => 'required',
            'statistics_as_of'  => 'required',
            'salesperson'       =>'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $commission_setting = PocomosCommissionSetting::whereSalespersonId($request->salesperson)->firstorfail();
        $commissionRate = $commission_setting->commission_percentage;

        // find bonus from pocomos_office_bonuses table
        $bonuses = PocomosOfficeBonuse::where('office_id', $request->office_id)->get()->toArray();

        // find bonus based on commission setting
        $commission_setting_bonus = PocomosCommissionBonuse::where('commission_settings_id', $commission_setting->id)->get()->toArray();

        $bonuses = array_merge($bonuses, $commission_setting_bonus);

        usort($bonuses, function ($a, $b) {
            return ($a['accounts_needed'] - $b['accounts_needed']) + ($a['bonus_value'] - $b['bonus_value']);
        });

        // find deductions from pocomos_commission_deductions
        $deductions = PocomosCommissionDeduction::where('commission_settings_id', $commission_setting->id)->get();

        //find data from contract table based on filter
        $contractIds = PocomosContract::where('salesperson_id', $request->salesperson)
                            ->whereBetween('date_start', [$request->start_date,$request->end_date])
                            ->get()->pluck('id');

        // return $contractIds;

        // find pocomos_sales_status based on office and active status
        $salesStatusNames = PocomosSalesStatus::where('office_id', $request->office_id)->where('active', true)->get();

        /*
        $salesStatusNames = $salesStatusNames->map(function($salesStatusName) {
            $salesStatusName = str_replace(' ','_',$salesStatusName);
            return $salesStatusName;
        });

        $salesStatusNames = $salesStatusNames->toArray();
        */

        $snapshots = PocomosReportContractSnapshot::with([
                'contract.sales_status',
                'contract.pest_contract_details',
            ])->whereIn('contract_id', $contractIds)
            ->where('pocomos_reports_contract_snapshots.date_created', '<=', $request->statistics_as_of)
            ->orderBy('pocomos_reports_contract_snapshots.date_created', 'DESC')
            ->get();

        // return $snapshots;

        $totalAccountsSold =
        $totalAccountsSoldValue = 0;
        $pastDueAccounts =
        $pastDueAccountsValue =
        $autopayOn =
        $autopayOnValue = 0;

        // $salesStatusNames = array_filter($salesStatusNames, function ($salesStatus) {
        //     return $salesStatus['name'];
        // });

        // return $salesStatusNames;

        // $salesStatusStatistics = array_fill_keys($salesStatusNames, array('count' => 0, 'value' => 0));

        // return $salesStatusNames;

        $i = 0;
        foreach ($salesStatusNames as $q) {
            $salesStatusStatistics[$i]['status'] = $q->name;
            $salesStatusStatistics[$i]['count'] = 0;
            $salesStatusStatistics[$i]['value'] = 0;

            $i++;
        }

        $salesStatusStatistics[$i]['status'] = self::QUALIFIES_FOR_COMMISION;
        $salesStatusStatistics[$i]['count'] = 0;
        $salesStatusStatistics[$i]['value'] = 0;

        // return $salesStatusStatistics;

        $q = 0;
        foreach ($snapshots as $snapshot) {
            $salesStatus = $snapshot->contract->sales_status;

            $value = $snapshot->contract->pest_contract_details->modifiable_original_value;

            $totalAccountsSold++;
            $totalAccountsSoldValue += $value;

            // if (!$salesStatus) {
                //     $salesStatus = $defaultSalesStatus;
            // }
            if ($salesStatus) {
                $salesStatusStatistics[$q]['count'] += 1;
                $salesStatusStatistics[$q]['value'] += $value;
                if ($salesStatus->paid == 1) {
                    $salesStatusStatistics[$i]['count'] += 1;
                    $salesStatusStatistics[$i]['value'] += $value;
                }
            } else {
                continue;
            }

            if ($snapshot->past_due == 1) {
                $pastDueAccounts++;
                $pastDueAccountsValue += $value;
            }

            if ($snapshot->autopay == 1 && $salesStatus->paid == 1) {
                $autopayOn++;
            }

            $q++;
        }

        $grossCommissions = ($salesStatusStatistics[$i]['value'] - $pastDueAccountsValue) * ($commissionRate / 100);
        if ($salesStatusStatistics[$i]['count'] > 0) {
            $autopayPercentage = ($autopayOn / $salesStatusStatistics[$i]['count']) * 100;
        } else {
            $autopayPercentage = 0;
        }

        $bonusTotal = 0;
        $deductionTotal = 0;

        foreach ($bonuses as $bonus) {
            if ($totalAccountsSold >= $bonus['accounts_needed']) {
                $bonusTotal += $bonus['bonus_value'];
            }
        }

        foreach ($deductions as $deduction) {
            $deductionTotal += $deduction->amount;
        }

        $grandTotal = $grossCommissions + $bonusTotal - $deductionTotal;

        $calculations = array(
            'statistics' => array(
                // 'repName' => $salesperson->__toString(),
                'salesStatusStatistics' => $salesStatusStatistics,
                'totalAccountsSold' => $totalAccountsSold,
                'totalAccountsSoldValue' => $totalAccountsSoldValue,
                'pastdueAccounts' => $pastDueAccounts,
                'pastdueAccountsValue' => $pastDueAccountsValue,
            ),
            'settings' => array(
                'commissionRate' => $commissionRate,
                'autopay' => $autopayPercentage,
                'grossCommissions' => $grossCommissions,
                'firstPayment' => ($grossCommissions * .70),
                'secondPayment' => ($grossCommissions * .30),
            ),
            'adjustments' => array(
                'bonuses' => $bonuses,
                'deductions' => $deductions,
            ),
            'totals' => array(
                'total' => $grandTotal,
                'firstPayment' => ($grandTotal * .70),
                'secondPayment' => ($grandTotal * .30),
            ),
        );


        return $this->sendResponse(true, 'Data of reports.', $calculations);
    }
}
