<?php

namespace App\Http\Controllers\API\Pocomos\Financial;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTeam;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosTag;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosInvoicePayment;
use App\Models\Pocomos\PocomosOfficeOpiniionSetting;
use App\Models\Pocomos\PocomosCounty;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Jobs\ExportRevenueReportJob;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosInvoiceTransaction;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosInvoiceInvoicePayment;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Orkestra\OrkestraTransaction;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use App\Exports\ExportFinancialTransaction;
use Excel;
use PDF;
use Illuminate\Support\Facades\Storage;
use App\Exports\ExportAccountsWithCredit;

class RevenueReportController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $branchId = $request->branch_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }
        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id','name']);

        $agreements = PocomosAgreement::with('office_details')->whereOfficeId($request->office_id)->whereActive(true)->get(['id','name']);

        $counties = PocomosCounty::whereOfficeId($officeId)->whereActive(true)->get();

        $sql = 'SELECT DISTINCT u.id, CONCAT(u.first_name, \' \', u.last_name) as name
                                FROM orkestra_users u
                                JOIN pocomos_company_office_users ou ON u.id = ou.user_id AND ou.office_id = ' . $officeId . '
                                JOIN orkestra_user_groups ug ON u.id = ug.user_id
                                LEFT JOIN orkestra_groups g ON g.id = ug.group_id
                             -- INNER JOIN orkestra_user_preferences up ON u.id = up.user_id 
                                WHERE (g.id IS NULL OR g.role <> "ROLE_CUSTOMER") AND ou.deleted = 0';

        $sql .= ' ORDER BY u.first_name, u.last_name';

        $employees = DB::select(DB::raw($sql));

        $marketingTypes = PocomosMarketingType::whereOfficeId($branchId)->whereActive(1)->get();

        $salespeopleByTeams = PocomosCompanyOffice::with('teams.member_details.ork_user_details')->whereId($branchId)->get(['id','name']);

        $serviceTypes = PocomosService::whereOfficeId($branchId)->whereActive(1)->get();

        $tags = PocomosTag::whereOfficeId($branchId)->whereActive(1)->orderBy('name')->get();

        $ids = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereActive(true)->pluck('id');
        $technicians = PocomosTechnician::with('user_detail.user_details:id,first_name,last_name')->whereIn('user_id', $ids)->whereActive(true)->get();

        return $this->sendResponse(true, 'Revenue report filters', [
            'branches'        => $branches,
            'agreements'        => $agreements,
            'counties'        => $counties,
            'employees'        => $employees,
            'marketing_types' => $marketingTypes,
            'salespeople_by_team'        => $salespeopleByTeams,
            'service_types'   => $serviceTypes,
            'tags'   => $tags,
            'technicians'   => $technicians,
        ]);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'revenue_by' => 'required',
            'branch_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $branchId = $request->branch_id;

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $startDate = date("Y-m-d", strtotime($request->start_date));
        $endYear = date("Y", strtotime($request->start_date));
        $endDate = $endYear.'-12-31';

        $revenueBy = $request->revenue_by;

        $filters = [
            'report_basis' => $request->report_basis,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        switch ($revenueBy) {
            // case 'CUSTOMER':
            //     $results = $this->get('pocomos.pest.helper.report')->getRevenueReportByCustomers($data['branchSelect'], $data);
            //     $custIds = array();
            //     foreach ($results as &$result) {
            //         $custIds[] = $result['custId'];
            //     }
            //     $custIdsArray = array_unique($custIds);
            //     $allArrayPeriodic = array();
            //     foreach ($custIdsArray as $value) {
            //         $allArrayPeriodic[] = $this->arrayFindElement($results, 'custId', $value);
            //     }
            //     break;
            case 'service type':
                //$results = $this->get('pocomos.pest.helper.report')->getRevenueReportBy($data['branchSelect'], $data);
                break;
            case 'marketing type':
                //$results = $this->get('pocomos.pest.helper.report')->getRevenueReportBy($data['branchSelect'], $data);
                break;
            case 'city':
                //$results = $this->get('pocomos.pest.helper.report')->getRevenueReportBy($data['branchSelect'], $data);
                break;
            case 'county':
                $results = $this->getRevenueReportByCounty($branchId, $filters);
                $custIds = array();
                foreach ($results as &$result) {
                    $custIds[] = $result->county_id;
                }
                $custIdsArray = array_unique($custIds);
                $allArrayPeriodic = array();
                foreach ($custIdsArray as $value) {
                    $allArrayPeriodic[] = $this->arrayFindElement($results, 'county_id', $value);
                }
                break;
            case 'employee':
                //$results = $this->get('pocomos.pest.helper.report')->getRevenueReportBy($data['branchSelect'], $data);
                break;
            case 'tag':
                //$results = $this->get('pocomos.pest.helper.report')->getRevenueReportBy($data['branchSelect'], $data);
                break;
            case 'salesperson':
                $results = $this->getRevenueReportBySalesperson($branchId, $filters);
                $custIds = array();
                foreach ($results as &$result) {
                    $custIds[] = $result['salesperson_id'];
                }
                $custIdsArray = array_unique($custIds);
                $allArrayPeriodic = array();
                foreach ($custIdsArray as $value) {
                    $allArrayPeriodic[] = $this->arrayFindElement($results, 'salesperson_id', $value);
                }
                break;

            default:
                $results = $this->getRevenueReportByCustomers($branchId, $filters);
                break;
        }


        if ($request->export) {
            ExportRevenueReportJob::dispatch($allArrayPeriodic);
            return $this->sendResponse(true, 'Transactions export job has started. You will find the download link on your message board when its complete. This could take a few minutes.');
        }

        $allArrayPeriodic = isset($allArrayPeriodic) ? $allArrayPeriodic : null;

        return $this->sendResponse(true, 'all periodic', $allArrayPeriodic);
    }


    public function getRevenueReportByCounty($branchId, $filters)
    {
        if ($filters['report_basis'] === 'accrual') {
            //accrual should show all jobs completed within the date range and the total value
            //basically invoice based!
            //Accrual = Transactions for completed services + disjointed billing by due date (edited)
            //Add tax amount + Tax Name + Tax %
            //Check if Accrual is correct. It shouldn't match up
            $sql = "SELECT 
                           DISTINCT t.id as tid,
                           j.id as jid,
                           GROUP_CONCAT(DISTINCT i.id SEPARATOR ', ') as id,
                           cu.id as custId,
                           pcc.county_id as county_id,
                           cu.external_account_id as custAcctId,
                           pcou.name,
                           DATE_FORMAT(i.date_due,'%c/%d/%Y') as dateDue,
                           DATE_FORMAT(t.date_created,'%c/%d/%Y') as paymentDate,
                           t.network as paymentType,
                           t.type as actualPaymentType,
                           t.amount as paymentAmount,
                           FORMAT(COALESCE(IF(t.network <> 'Points',t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2) as preTaxAmount,
                           i.sales_tax as taxRate,
                           i.balance,
                           r.external_id AS refNumber,
                           COALESCE(t.status, 'Unpaid') AS paymentStatus,
                           st.name AS service_type_name,
                           co.agreement_id AS agreement_id
                           FROM pocomos_contracts co
                           JOIN pocomos_invoices i ON i.contract_id = co.id
                           LEFT JOIN pocomos_invoice_transactions it ON i.id = it.invoice_id
                           JOIN orkestra_transactions t on (it.transaction_id = t.id and t.type IN ('Sale') and t.status = 'Approved')
                           LEFT JOIN pocomos_user_transactions AS ut ON t.id = ut.transaction_id
                           LEFT JOIN orkestra_users AS u ON ut.user_id = u.id
                           LEFT JOIN pocomos_jobs j on j.invoice_id = i.id
                           JOIN pocomos_pest_contracts pcc on co.id = pcc.contract_id
                           JOIN pocomos_counties pcou on pcc.county_id = pcou.id
                           LEFT JOIN pocomos_pest_contract_service_types st ON pcc.service_type_id = st.id
                           LEFT JOIN pocomos_pest_contracts_tags pt on pcc.id = pt.contract_id
                           JOIN pocomos_customer_sales_profiles csp on co.profile_id = csp.id
                           JOIN pocomos_customers cu on csp.customer_id = cu.id
                           JOIN pocomos_addresses ca on cu.contact_address_id = ca.id
                           LEFT JOIN orkestra_countries_regions reg on ca.region_id = reg.id
                           LEFT JOIN orkestra_results r ON r.transaction_id = t.id
                           LEFT JOIN pocomos_agreements pa ON co.agreement_id = pa.id                           
                           WHERE csp.office_id = ".$branchId."
                            AND pa.id = 1
                            AND (j.status = 'complete' OR j.status is NULL)
                           ";

        // if (isset($filters['startDate'], $filters['endDate'])) {
            //     $sql .= ' AND ((j.id is NULL && i.date_due BETWEEN '.$filters['startDate'].' AND '.$filters['endDate'].') OR (j.date_completed BETWEEN '.$filters['startDate'].' AND '.$filters['endDate'].'))';
        // }
        } else {
            $sql = "SELECT
                           DISTINCT t.id as tid,
                           cu.id as custId,
                           cu.external_account_id as custAcctId,
                           
                           CONCAT(cu.first_name, ' ', cu.last_name) as name,
                           CONCAT(ca.street, ' ', ca.city, ', ', reg.name, ' ', ca.postal_code) as address,
                           GROUP_CONCAT(DISTINCT i.id SEPARATOR ', ') as id,
                           DATE_FORMAT(i.date_due,'%c/%d/%Y') as dateDue,
                           DATE_FORMAT(t.date_created,'%c/%d/%Y') as paymentDate,
                           t.network as paymentType,
                           t.type as actualPaymentType,
                           t.amount as paymentAmount,
                           FORMAT(COALESCE(IF(t.network <> 'Points',t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2) as preTaxAmount,
                           i.balance,
                           i.sales_tax as taxRate,
                           r.external_id AS refNumber,
                           t.status AS paymentStatus,
                           st.name AS service_type_name,
                           co.agreement_id AS agreement_id
                        FROM orkestra_transactions t
                        LEFT JOIN pocomos_user_transactions AS ut ON t.id = ut.transaction_id
                        LEFT JOIN orkestra_users AS u ON ut.user_id = u.id
                        JOIN orkestra_accounts a on t.account_id = a.id
                        JOIN pocomos_customers_accounts acct on a.id = acct.account_id
                        JOIN pocomos_customer_sales_profiles csp on acct.profile_id = csp.id
                        JOIN pocomos_customers cu on csp.customer_id = cu.id
                        JOIN pocomos_addresses ca on cu.contact_address_id = ca.id
                        LEFT JOIN orkestra_countries_regions reg on ca.region_id = reg.id
                        LEFT JOIN pocomos_invoice_transactions it on it.transaction_id = t.id
                        JOIN orkestra_results r ON r.transaction_id = t.id
                        LEFT JOIN pocomos_invoices i on it.invoice_id = i.id
                        LEFT JOIN pocomos_jobs j on j.invoice_id = i.id
                        LEFT JOIN pocomos_contracts co on i.contract_id = co.id
                        LEFT JOIN pocomos_pest_contracts pcc on co.id = pcc.contract_id
                        LEFT JOIN pocomos_pest_contract_service_types st ON pcc.service_type_id = st.id
                        LEFT JOIN pocomos_pest_contracts_tags pt on pcc.id = pt.contract_id
                        LEFT JOIN pocomos_agreements pa ON co.agreement_id = pa.id
                        WHERE csp.office_id = ".$branchId."
                        AND t.type IN ('Sale')
                        AND pa.id = 1
                        AND t.status = 'Approved' ";

            // if (isset($filters['startDate'], $filters['endDate'])) {
            //     $sql .= ' AND t.date_created BETWEEN '.$filters['startDate'].' AND '.$filters['endDate'].'';
            // }
        }

        // $sql .=  'GROUP BY t.id, IF(t.id IS NULL, i.id, 0)';
        // $sql .=  "ORDER BY name;";

        $result = DB::select(DB::raw($sql));

        return $result;
    }

    public function getRevenueReportBySalesperson($branchId, $filters)
    {
        if ($filters['report_basis'] === 'accrual') {
            //accrual should show all jobs completed within the date range and the total value
            //basically invoice based!
            //Accrual = Transactions for completed services + disjointed billing by due date (edited)
            //Add tax amount + Tax Name + Tax %
            //Check if Accrual is correct. It shouldn't match up
            $sql = "SELECT
            DISTINCT ou.profile_id as id, CONCAT(u.first_name, ' ', u.last_name) as name,
            cu.id as custId,
            cu.external_account_id as custAcctId,
            DATE_FORMAT(i.date_due,'%c/%d/%Y') as dateDue,
            DATE_FORMAT(t.date_created,'%c/%d/%Y') as paymentDate,
            t.network as paymentType,
            t.type as actualPaymentType,
            t.amount as paymentAmount,
            FORMAT(COALESCE(IF(t.network <> 'Points',t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2) as preTaxAmount,
            i.sales_tax as taxRate,
            i.balance as balance,
            co.agreement_id AS agreement_id,
            csp.id as salesperson_id
            FROM pocomos_salespeople s 
            JOIN pocomos_company_office_users ou ON s.user_id = ou.id 
            JOIN orkestra_users u ON ou.user_id = u.id
            JOIN pocomos_customer_sales_profiles csp on ou.profile_id = csp.id
            JOIN pocomos_customers cu on csp.customer_id = cu.id
            JOIN pocomos_invoices i ON i.contract_id = cu.id
            LEFT JOIN pocomos_contracts co on i.contract_id = cu.id
            LEFT JOIN pocomos_invoice_transactions it ON i.id = it.invoice_id
            LEFT JOIN pocomos_jobs j on j.invoice_id = i.id
            LEFT JOIN pocomos_agreements pa ON co.agreement_id = pa.id
            JOIN orkestra_transactions t on (it.transaction_id = t.id and t.type IN ('Sale') and t.status = 'Approved')
            WHERE csp.office_id = ".$branchId."
            AND pa.id = 1
            AND (j.status = 'complete' OR j.status is NULL)";

        // if (isset($filters['startDate'], $filters['endDate'])) {
            //     $sql .= ' AND ((j.id is NULL && i.date_due BETWEEN '.$filters['startDate'].' AND '.$filters['endDate'].' ) OR (j.date_completed BETWEEN '.$filters['startDate'].' AND '.$filters['endDate'].' ))';
        // }
        } else {
            $sql = "SELECT
            DISTINCT ou.profile_id as id, CONCAT(u.first_name, ' ', u.last_name) as name,
            cu.id as custId,
            cu.external_account_id as custAcctId,
            DATE_FORMAT(i.date_due,'%c/%d/%Y') as dateDue,
            DATE_FORMAT(t.date_created,'%c/%d/%Y') as paymentDate,
            t.network as paymentType,
            t.type as actualPaymentType,
            t.amount as paymentAmount,
            FORMAT(COALESCE(IF(t.network <> 'Points',t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2) as preTaxAmount,
            i.sales_tax as taxRate,
            i.balance as balance,
            co.agreement_id AS agreement_id
            FROM pocomos_salespeople s 
            JOIN pocomos_company_office_users ou ON s.user_id = ou.id 
            JOIN orkestra_users u ON ou.user_id = u.id
            JOIN pocomos_customer_sales_profiles csp on ou.profile_id = csp.id
            JOIN pocomos_customers cu on csp.customer_id = cu.id
            JOIN pocomos_invoices i ON i.contract_id = cu.id
            LEFT JOIN pocomos_contracts co on i.contract_id = cu.id
            LEFT JOIN pocomos_invoice_transactions it ON i.id = it.invoice_id
            LEFT JOIN pocomos_jobs j on j.invoice_id = i.id
            LEFT JOIN pocomos_agreements pa ON co.agreement_id = pa.id
            JOIN orkestra_transactions t on (it.transaction_id = t.id and t.type IN ('Sale') and t.status = 'Approved')
            WHERE t.type IN ('Sale')
            AND csp.office_id = ".$branchId."
            AND pa.id = 1
            AND t.status = 'Approved' ";

            if (isset($filters['startDate'], $filters['endDate'])) {
                $sql .= ' AND t.date_created BETWEEN '.$filters['startDate'].' AND '.$filters['endDate'].' ';
            }
        }

        $sql .=  'GROUP BY t.id, IF(t.id IS NULL, i.id, 0)';
        $sql .=  "ORDER BY name;";

        $result = DB::select(DB::raw($sql));

        return $result;
    }


    public function getRevenueReportByCustomers($branchId, $filters)
    {
        if ($filters['report_basis'] === 'accrual') {
            //accrual should show all jobs completed within the date range and the total value
            //basically invoice based!
            //Accrual = Transactions for completed services + disjointed billing by due date (edited)
            //Add tax amount + Tax Name + Tax %
            //Check if Accrual is correct. It shouldn't match up
            $sql = "SELECT
                           DISTINCT t.id as tid,
                           j.id as jid,
                           GROUP_CONCAT(DISTINCT i.id SEPARATOR ', ') as id,
                           cu.id as custId,
                           cu.external_account_id as custAcctId,
                           CONCAT(cu.first_name, ' ', cu.last_name) as name,
                           CONCAT(ca.street, ' ', ca.city, ', ', reg.name, ' ', ca.postal_code) as address,
                           DATE_FORMAT(i.date_due,'%c/%d/%Y') as dateDue,
                           DATE_FORMAT(t.date_created,'%c/%d/%Y') as paymentDate,
                           t.network as paymentType,
                           t.type as actualPaymentType,
                           t.amount as paymentAmount,
                           FORMAT(COALESCE(IF(t.network <> 'Points',t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2) as preTaxAmount,
                           i.sales_tax as taxRate,
                           i.balance,
                           r.external_id AS refNumber,
                           COALESCE(t.status, 'Unpaid') AS paymentStatus,
                           st.name AS service_type_name,
                           co.agreement_id AS agreement_id,
                           DATE_FORMAT(prcs.initial_service_date,'%c/%d/%Y') as initialdate,
                           pa.name AS agreement_name
                           FROM pocomos_contracts co
                           JOIN pocomos_invoices i ON i.contract_id = co.id
                           LEFT JOIN pocomos_invoice_transactions it ON i.id = it.invoice_id
                           JOIN orkestra_transactions t on (it.transaction_id = t.id and t.type IN ('Sale') and t.status = 'Approved')
                           LEFT JOIN pocomos_user_transactions AS ut ON t.id = ut.transaction_id
                           LEFT JOIN orkestra_users AS u ON ut.user_id = u.id
                           LEFT JOIN pocomos_jobs j on j.invoice_id = i.id
                           JOIN pocomos_pest_contracts pcc on co.id = pcc.contract_id
                           LEFT JOIN pocomos_pest_contract_service_types st ON pcc.service_type_id = st.id
                           LEFT JOIN pocomos_pest_contracts_tags pt on pcc.id = pt.contract_id
                           JOIN pocomos_customer_sales_profiles csp on co.profile_id = csp.id
                           JOIN pocomos_customers cu on csp.customer_id = cu.id
                           JOIN pocomos_addresses ca on cu.contact_address_id = ca.id
                           LEFT JOIN orkestra_countries_regions reg on ca.region_id = reg.id
                           LEFT JOIN orkestra_results r ON r.transaction_id = t.id
                           LEFT JOIN pocomos_agreements pa ON co.agreement_id = pa.id
                           LEFT JOIN pocomos_reports_contract_states prcs on co.id = prcs.contract_id
                           WHERE csp.office_id = ".$branchId."
                           AND pa.id IN (1)
                           AND (j.status = 'complete' OR j.status is NULL)";

        // if (isset($filters['startDate'], $filters['endDate'])) {
            //     $sql .= ' AND ((j.id is NULL && i.date_due BETWEEN '.$filters['startDate'].' AND '.$filters['endDate'].' ) OR (j.date_completed BETWEEN '.$filters['startDate'].' AND '.$filters['endDate'].' ))';
        // }
        } else {
            $sql = "SELECT
                           DISTINCT t.id as tid,
                           cu.id as custId,
                           cu.external_account_id as custAcctId,
                           
                           CONCAT(cu.first_name, ' ', cu.last_name) as name,
                           CONCAT(ca.street, ' ', ca.city, ', ', reg.name, ' ', ca.postal_code) as address,
                           GROUP_CONCAT(DISTINCT i.id SEPARATOR ', ') as id,
                           DATE_FORMAT(i.date_due,'%c/%d/%Y') as dateDue,
                           DATE_FORMAT(t.date_created,'%c/%d/%Y') as paymentDate,
                           t.network as paymentType,
                           t.type as actualPaymentType,
                           t.amount as paymentAmount,
                           FORMAT(COALESCE(IF(t.network <> 'Points',t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2) as preTaxAmount,
                           i.balance,
                           i.sales_tax as taxRate,
                           r.external_id AS refNumber,
                           t.status AS paymentStatus,
                           st.name AS service_type_name,
                           co.agreement_id AS agreement_id,
                           DATE_FORMAT(prcs.initial_service_date,'%c/%d/%Y') as initialdate,
                           pa.name AS agreement_name
                        FROM orkestra_transactions t
                        LEFT JOIN pocomos_user_transactions AS ut ON t.id = ut.transaction_id
                        LEFT JOIN orkestra_users AS u ON ut.user_id = u.id
                        JOIN orkestra_accounts a on t.account_id = a.id
                        JOIN pocomos_customers_accounts acct on a.id = acct.account_id
                        JOIN pocomos_customer_sales_profiles csp on acct.profile_id = csp.id
                        JOIN pocomos_customers cu on csp.customer_id = cu.id
                        JOIN pocomos_addresses ca on cu.contact_address_id = ca.id
                        LEFT JOIN orkestra_countries_regions reg on ca.region_id = reg.id
                        LEFT JOIN pocomos_invoice_transactions it on it.transaction_id = t.id
                        JOIN orkestra_results r ON r.transaction_id = t.id
                        LEFT JOIN pocomos_invoices i on it.invoice_id = i.id
                        LEFT JOIN pocomos_jobs j on j.invoice_id = i.id
                        LEFT JOIN pocomos_contracts co on i.contract_id = co.id
                        LEFT JOIN pocomos_pest_contracts pcc on co.id = pcc.contract_id
                        LEFT JOIN pocomos_pest_contract_service_types st ON pcc.service_type_id = st.id
                        LEFT JOIN pocomos_pest_contracts_tags pt on pcc.id = pt.contract_id
                        LEFT JOIN pocomos_agreements pa ON co.agreement_id = pa.id
                        LEFT JOIN pocomos_reports_contract_states prcs on co.id = prcs.contract_id
                        WHERE csp.office_id = ".$branchId."
                        AND t.type IN ('Sale')
                        AND pa.id IN (1)
                        AND t.status = 'Approved' ";

            if (isset($filters['startDate'], $filters['endDate'])) {
                $sql .= ' AND t.date_created BETWEEN '.$filters['startDate'].' AND '.$filters['endDate'].' ';
            }
        }

        $sql .=  'GROUP BY t.id, IF(t.id IS NULL, i.id, 0)';
        $sql .=  "ORDER BY name;";
        $result = DB::select(DB::raw($sql));

        return $result;
    }

    public function arrayFindElement($array, $key, $value)
    {
        $results = array();
        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }
            foreach ($array as $subarray) {
                $results = array_merge($results, $this->arrayFindElement($subarray, $key, $value));
            }
        }
        return $results;
    }
}
