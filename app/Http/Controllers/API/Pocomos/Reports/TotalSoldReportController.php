<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosReportSummerTotalConfiguration;
use App\Models\Pocomos\PocomosReportSummerTotalConfigurationStatus;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use App\Exports\ExportTotalSold;
use Excel;
use App\Models\Pocomos\PocomosCustomFieldConfiguration;

class TotalSoldReportController extends Controller
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

        $pocomosSalesStatus = PocomosSalesStatus::whereActive(true)->whereOfficeId($officeId)->get(['id','name']);

        return $this->sendResponse(true, 'Marketing report filters', [
            'branches'      => $branches,
            'sales_status'  => $pocomosSalesStatus,
        ]);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'office_ids' => 'required|array',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $dateType = $request->date_type;

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $officeIdsArr = $request->office_ids;
        $officeIds    = implode(',', $request->office_ids);

        $salesStatuses = $request->sales_statuses ? implode(',', $request->sales_statuses) : null;

        if ($dateType === 'initial') {
            $dateConstraint = 'COALESCE(j.date_completed, j.date_scheduled) BETWEEN "'.$startDate.'"
                                                    AND "'.$endDate.'" ';
        } elseif ($dateType === 'contract') {
            $dateConstraint = 'co.date_start BETWEEN "'.$startDate.'" AND "'.$endDate.'" ';
        } else {
            $dateConstraint = 'csp.date_signed_up BETWEEN "'.$startDate.'" AND "'.$endDate.'" ';
        }

        $customFieldConfigs = PocomosCustomFieldConfiguration::select('pocomos_custom_field_configuration.*')
            ->join('pocomos_pest_office_settings as ppos', 'pocomos_custom_field_configuration.office_configuration_id', 'ppos.id')
            ->whereIn('ppos.office_id', $officeIdsArr)
            ->where('pocomos_custom_field_configuration.show_on_acct_status', true)
            ->where('pocomos_custom_field_configuration.active', true)
            ->get();

        $i = 0;
        $configSqls = [];
        foreach ($customFieldConfigs as $config) {
            // return $config->id;
            $configSqls[] = '(SELECT cf.value FROM pocomos_custom_fields cf 
                                JOIN pocomos_custom_field_configuration cfc 
            ON cf.custom_field_configuration_id = cfc.id AND cfc.id = ' . $config->id . '
             WHERE cf.pest_control_contract_id = pcc.id) AS config' . $i . '_value';

            $configSqls[] = '(SELECT cfc.label FROM pocomos_custom_field_configuration cfc
             WHERE cfc.id = ' . $config->id . ') AS config' . $i . '_label';
            $i++;
        }

        // return $configSqls;

        $sql = 'SELECT
                       pcc.id                                                                     as pcc_id,
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
                       oup.profile_external_id,
                       o.list_name                                                                as branch_name';

        $sql .= !empty($configSqls) ? ',' . PHP_EOL . implode(',' . PHP_EOL, $configSqls) : '';

        $sql .= '   from pocomos_pest_contracts pcc
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
                       AND o.id in ('.$officeIds.') AND c.active = 1';

        if ($salesStatuses) {
            // return $salesStatuses;
            $sql .=  ' AND ss.id IN ('.$salesStatuses.')';
        }

        if ($request->search) {
            $search = '"%'.$request->search.'%"';

            $sql .= " AND (CONCAT(c.first_name,' ',c.last_name) LIKE $search 
                OR CONCAT(ca.street, ' ',  ca.city, ', ',reg.code, ' ', ca.postal_code) LIKE $search 
                OR DATE_FORMAT(co.date_start,'%c/%d/%Y') LIKE $search 
                OR DATE_FORMAT(COALESCE(j.date_completed, j.date_scheduled),'%c/%d/%Y') LIKE $search 
                OR ss.name LIKE $search 
                OR pcc.initial_price LIKE $search 
                OR pcc.recurring_price LIKE $search 
                OR pcc.modifiable_original_value LIKE $search 
                OR csp.autopay LIKE $search 
                OR sp.pay_level LIKE $search 
                )";
        }

        $sql .= ' GROUP BY pcc_id';

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $results = DB::select(DB::raw($sql));

        if ($request->download) {
            return Excel::download(new ExportTotalSold($results), 'ExportTotalSold.csv');
        }

        return $this->sendResponse(true, 'Total Sold Across All Offices', [
            'results' => $results,
            'count' => $count
        ]);
    }
}
