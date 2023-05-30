<?php

namespace App\Http\Controllers\API\Pocomos\Reports\AccountStatus;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\PocomosPest;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Pocomos\PocomosTag;
use App\Models\Pocomos\PocomosCustomFieldConfiguration;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use Excel;
use App\Exports\ExportAccountStatusReport;

class AccountStatusReportController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required'
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

        if ($officeId) {
            $parentId = PocomosCompanyOffice::whereId($officeId)->first()->parent_id;
            $officeOrParentId = $parentId ? $parentId : $officeId;
        }
        // return $officeOrParentId;
        $PocomosSalesStatus = PocomosSalesStatus::whereActive(true)->whereOfficeId($officeOrParentId)->get(['id','name']);

        // $salesStatusIds = implode(',',$PocomosSalesStatus->pluck('id')->toArray());

        // $jobStatus = $request->job_status ? implode(',',$request->job_status) : null;

        // $ids            = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereActive(true)->pluck('id');
        // $userIds        = PocomosSalesPeople::whereIn('user_id', $ids)->whereActive(true)->pluck('user_id');
        // $OfficeUserIds  = PocomosCompanyOfficeUser::whereIn('id',$userIds)->pluck('user_id');
        // $salesPeople    = OrkestraUser::whereIn('id',$OfficeUserIds)->whereActive(true)->get(['id','first_name','last_name']);
        // $salesPeopleIds = implode(',',$salesPeople->pluck('id')->toArray());

        // $officeIds = $request->office_ids ? $request->office_ids : implode(',',$branches->pluck('id')->toArray());

        // $results = $this->getData($officeIds, $dateType, $startDate, $endDate, $salesPeopleIds, $salesStatusIds, $jobStatus, $techniciansIds);

        $pocomosPests        = PocomosPest::with('company_details')->whereOfficeId($officeId)->whereType('Regular')->whereActive(true)->get();
        $pocomosSpecialPests = PocomosPest::with('company_details')->whereOfficeId($officeId)->whereType('Specialty')->whereActive(true)->get();

        $agreements = PocomosAgreement::whereOfficeId($request->office_id)->whereActive(true)->get(['id','name']);

        return $this->sendResponse(
            true,
            'Account Status Report',
            [
            'branches'      => $branches,
            'sales_status'  => $PocomosSalesStatus,
            // 'sales_people'  => $salesPeople,
            'pests' => $pocomosPests,
            'special_pests' => $pocomosSpecialPests,
            'agreements' => $agreements,
            ]
        );
    }

    public function findTagsByOfficeAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'office_ids' => 'required|array',

        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $tags = PocomosTag::whereIn('office_id', $request->office_ids)->whereActive(1)->get();

        return $this->sendResponse(true, 'tags', $tags);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'office_ids' => 'required|array',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1'
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

        $officeIdsArr = $request->office_ids;

        $officeIds = implode(',', $request->office_ids);

        $dateType = $request->date_type;

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // $salesPeopleIds = $request->salespeople_ids ? implode(',',$request->salespeople_ids) : $salesPeopleIds;
        $salesPeopleIds = $request->salespeople_ids ? implode(',', $request->salespeople_ids) : [];

        // $PocomosSalesStatus = PocomosSalesStatus::whereActive(true)->whereOfficeId($officeOrParentId)->get(['id','name']);
        // $salesStatusIds = implode(',',$PocomosSalesStatus->pluck('id')->toArray());
        $salesStatusIdsArr = $request->sales_status_ids;

        $agreements = $request->agreements;

        $tags = $request->tags;

        $pestsIdsArr = $request->pests_ids;

        $specialPestIdsArr = $request->special_pests_ids;

        if ($dateType === 'initial') {
            $dateConstraint = 'COALESCE(j.date_completed, j.date_scheduled) BETWEEN "'.$startDate.'" AND "'.$endDate.'"';
        } elseif ($dateType === 'contract') {
            $dateConstraint = 'co.date_start BETWEEN "'.$startDate.'" AND "'.$endDate.'"';
        } else {
            $dateConstraint = 'csp.date_signed_up BETWEEN "'.$startDate.'" AND "'.$endDate.'"';
        }

        //   return  $officeIdsArr;
        $customFieldConfigs = PocomosCustomFieldConfiguration::select('pocomos_custom_field_configuration.*')
                ->join('pocomos_pest_office_settings as ppos', 'pocomos_custom_field_configuration.office_configuration_id', 'ppos.id')
                ->whereIn('ppos.office_id', $officeIdsArr)
                ->where('pocomos_custom_field_configuration.show_on_acct_status', true)
                ->where('pocomos_custom_field_configuration.active', true)
                ->get();

        $i = 0;
        $configSqls = array();
        foreach ($customFieldConfigs as $config) {
            $configId = $config->id;

            $configSqls[] = '(SELECT cf.value FROM pocomos_custom_fields cf JOIN pocomos_custom_field_configuration cfc ON cf.custom_field_configuration_id = cfc.id AND cfc.id = ' . $configId . ' WHERE cf.pest_control_contract_id = pcc.id) AS config' . $i . '_value';
            $configSqls[] = '(SELECT cfc.label FROM pocomos_custom_field_configuration cfc WHERE cfc.id = ' . $configId . ') AS config' . $i . '_label';
            $i++;
        }

        $sql = 'SELECT 
                       pcc.id                                                                     as pcc_id,
                       pcc.contract_id                                                            as contract_id,
                       c.id                                                                       as customer_id,
                       c.first_name                                                               as customer_first_name,
                       c.last_name                                                                as customer_last_name,
                       c.status                                                                   as customer_status,
                       p.number                                                                   as customer_phone,
                       c.external_account_id                                                      as customer_external_account_id,
                       CONCAT(ca.street, " ",  ca.city, ", ",reg.code, " ", ca.postal_code)       as customer_contact_address,
                       DATE_FORMAT(co.date_start,"%c/%d/%Y")                                      as contract_date,
                       DATE_FORMAT(csp.date_signed_up,"%c/%d/%Y")                                 as account_sign_up_start_date,
                       ss.name                                                                    as sales_status,
                       pcc.initial_price,
                       pcc.recurring_price,
                       cus.balance_overall                                                        as balance,
                       cus.days_past_due,
                       cus.card_on_file,
                       COALESCE(j.date_completed, j.date_scheduled)                               as job_date,
                       DATE_FORMAT(COALESCE(j.date_completed, j.date_scheduled),"%c/%d/%Y")       as initial_date,
                       a.name                                                                     as contract_name,
                       ppcst.name                                                                 as service_type,
                       pcc.service_frequency                                                      as service_frequency,
                       pmt.name                                                                   as marketing_type,
                       pcc.modifiable_original_value                                              as original_contract_value,
                       pcc.first_year_contract_value                                              as first_year_contract_value,

                       cus.balance_credit                                                         as balance_credit,
                       csp.autopay,
                       sp.pay_level                                                               as pay_level,
                       CONCAT(u.first_name, " ",u.last_name)                                      as salesperson_name,
                       o.list_name                                                                as branch_name,
                       ou.profile_id as qqq
                    ';

                    //    oup.profile_external_id,


        $sql .= !empty($configSqls) ? ',' .  implode(',', $configSqls) : '';

        $sql .= ' from pocomos_pest_contracts pcc
                       JOIN pocomos_contracts co                        on pcc.contract_id = co.id
                       JOIN pocomos_pest_agreements pca                 on pcc.agreement_id = pca.id
                       JOIN pocomos_agreements a                        on a.id = pca.agreement_id
                       JOIN pocomos_company_offices o                   on o.id = a.office_id
                       JOIN pocomos_customer_sales_profiles csp         on csp.id = co.profile_id
                       JOIN pocomos_customers c                         on csp.customer_id = c.id
                       JOIN pocomos_customer_state cus                  on cus.customer_id = c.id
                       JOIN pocomos_addresses ca                        on c.contact_address_id = ca.id
                       JOIN orkestra_countries_regions reg              on ca.region_id = reg.id
                       LEFT JOIN pocomos_sales_status ss                on co.sales_status_id = ss.id
                       LEFT JOIN pocomos_jobs j                         on j.contract_id = pcc.id AND j.type = "Initial"
                       JOIN pocomos_reports_contract_states cs          on cs.contract_id = co.id
                       JOIN pocomos_salespeople s                       on s.id = co.salesperson_id
                       JOIN pocomos_company_office_users ou             on s.user_id = ou.id
                       JOIN pocomos_company_office_user_profiles oup    on ou.profile_id = oup.id
                       JOIN pocomos_salesperson_profiles sp             on sp.office_user_profile_id = oup.id
                       JOIN orkestra_users u                            on ou.user_id = u.id
                       JOIN pocomos_pest_contract_service_types ppcst   on pcc.service_type_id = ppcst.id
                       JOIN pocomos_marketing_types pmt                 on co.found_by_type_id = pmt.id
                       LEFT JOIN pocomos_customers_phones AS cp ON csp.id = cp.profile_id
                       JOIN pocomos_phone_numbers AS p ON cp.phone_id = p.id
                       LEFT JOIN pocomos_pest_contracts_pests pcp            on pcc.id = pcp.contract_id
                       LEFT JOIN pocomos_pest_contracts_specialty_pests pcsp on pcc.id = pcsp.contract_id
                       LEFT JOIN pocomos_pest_contracts_tags pt on pcc.id = pt.contract_id
                       LEFT JOIN pocomos_agreements pa on co.agreement_id = pa.id
                       WHERE ' . $dateConstraint . '
                       AND o.id in ('.$officeIds.') AND c.active = 1'
        ;

        // return $salesPeopleIds;
        if ($salesPeopleIds) {
            // return $salesPeopleIds;
            $sql .= ' AND oup.id IN ('.$salesPeopleIds.')';
        }

        if ($salesStatusIdsArr) {
            $salesStatusIds = implode(',', $salesStatusIdsArr);
            $sql .= ' AND ss.id IN ('.$salesStatusIds.')';
        }

        if (count($pestsIdsArr)) {
            $pestsIds = implode(',', $pestsIdsArr);
            $sql .= ' AND pcp.pest_id IN ('.$pestsIds.')';
        }

        if (count($specialPestIdsArr)) {
            $specialPestIds = implode(',', $specialPestIdsArr);
            $sql .= ' AND pcsp.pest_id IN ('.$specialPestIds.')';
        }

        if ($agreements) {
            $agreements = implode(',', $agreements);
            $sql .=  ' AND pa.id IN ('.$agreements.')';
        }

        if ($tags) {
            $tags = implode(',', $tags);
            // return $tags;
            $sql =  ' AND pt.tag_id IN ('.$tags.')';
        }

        $sql .=  ' GROUP BY pcc_id';

        if ($request->search) {
            $search = $request->search;
            if ($search == 'Y') {
                $search = 1;
            } elseif ($search == 'N') {
                $search = 0;
            }
            $sql .= " AND (c.first_name LIKE '%$search%' OR c.last_name LIKE '%$search%' OR ca.street 
                LIKE '%$search%' OR ca.city LIKE '%$search%' OR reg.code LIKE '%$search%' OR ca.postal_code 
                LIKE '%$search%' OR co.date_start LIKE '%$search%' OR csp.date_signed_up 
                LIKE '%$search%' OR j.date_completed LIKE '%$search%' OR j.date_scheduled 
                LIKE '%$search%' OR ss.name LIKE '%$search%' OR pcc.initial_price 
                LIKE '%$search%' OR pcc.recurring_price LIKE '%$search%' OR pcc.modifiable_original_value 
                LIKE '%$search%' OR cus.balance_overall LIKE '%$search%' OR csp.autopay 
                LIKE '%$search%' OR sp.pay_level LIKE '%$search%' OR u.first_name 
                LIKE '%$search%' OR u.last_name LIKE '%$search%') ";
        }

    //    return DB::select(DB::raw($sql));

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));
        /**If result data are from DB::row query then `true` else `false` normal laravel get listing */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";
        /**End */

        $results = DB::select(DB::raw($sql));

        // $totalValue = array_sum(array_map(function ($result) {
        //     return $result->original_contract_value;
        // }, $results));

        if ($request->showOnlyFirstYear) {
            // return 11;
            foreach ($results as $res) {
                // return $res->contract_id;
                // return $this->getFirstYearContract($res->contract_id);
                $res->first_year_contract_value = $this->getFirstYearContract($res->contract_id);
            }

            $totalValue = array_sum(array_map(function ($result) {
                return $result->first_year_contract_value;
            }, $results));
        } else {
            $totalValue = array_sum(array_map(function ($result) {
                return $result->original_contract_value;
            }, $results));
        }

        $totalCount = count($results);

        if ($request->download) {
            return Excel::download(new ExportAccountStatusReport($results), 'ExportAccountStatusReport.csv');
        }

        return $this->sendResponse(true, 'Account Status Report', [
            'results'     => $results,
            'total_value' => $totalValue,
            'total_count' => $totalCount,
            'custom_fields' => $customFieldConfigs,
            'count' => $count
        ]);
    }
}
