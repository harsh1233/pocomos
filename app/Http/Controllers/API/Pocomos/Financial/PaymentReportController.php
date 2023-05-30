<?php

namespace App\Http\Controllers\API\Pocomos\Financial;

use DB;
use PDF;
use Excel;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosTeam;
use App\Exports\ExportPaymentReport;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Orkestra\OrkestraTransaction;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class PaymentReportController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'branch_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }
        $branchId = $request->branch_id;

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id', 'name']);

        $agreements = PocomosAgreement::whereOfficeId($request->office_id)->whereActive(true)->get(['id', 'name']);

        $sql = 'SELECT DISTINCT u.id, CONCAT(u.first_name, \' \', u.last_name) as name
                                FROM orkestra_users u
                                JOIN pocomos_company_office_users ou ON u.id = ou.user_id AND ou.office_id = ' . $officeId . '
                                JOIN orkestra_user_groups ug ON u.id = ug.user_id
                                LEFT JOIN orkestra_groups g ON g.id = ug.group_id
                                INNER JOIN orkestra_user_preferences up ON u.id = up.user_id
                                WHERE (g.id IS NULL OR g.role <> "ROLE_CUSTOMER") AND ou.deleted = 0';

        $sql .= ' ORDER BY u.first_name, u.last_name';

        $users = DB::select(DB::raw($sql));

        $teams = PocomosTeam::with('member_details')->whereOfficeId($branchId)
            ->where('pocomos_teams.active', 1)
            ->orderBy('name')
            ->get();

        $taxCodes = PocomosTaxCode::whereOfficeId($branchId)->whereActive(1)->get();

        $marketingTypes = PocomosMarketingType::whereOfficeId($officeId)->whereActive(1)->get();

        $serviceTypes = PocomosService::whereOfficeId($officeId)->whereActive(1)->get();

        $tags = PocomosTag::whereOfficeId($officeId)->whereActive(1)->orderBy('name')->get();

        return $this->sendResponse(true, 'Payment report filters', [
            'branches' => $branches,
            'agreements' => $agreements,
            'users' => $users,
            'teams' => $teams,
            'tax_codes' => $taxCodes,
            'marketing_types' => $marketingTypes,
            'service_types'   => $serviceTypes,
            'tags'   => $tags,
        ]);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
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

        $yearStart = date("Y", strtotime($request->start_date));
        $yearEnd = date("Y", strtotime($request->end_date));

        $monthStart = date("n", strtotime($request->start_date));
        $monthEnd = date("n", strtotime($request->end_date));

        // $customerStatus = $request->customer_status ? implode(',',$request->customer_status) : null;

        $filters['marketingType'] = $request->marketing_type_ids ? implode(',', $request->marketing_type_ids) : null;
        $filters['serviceType'] = $request->service_type_ids ? implode(',', $request->service_type_ids) : null;
        $filters['tags'] = $request->tags ? implode(',', $request->tags) : null;
        $filters['serviceFrequency'] = $request->service_frequency ? $request->service_frequency : null;
        $filters['jobType'] = $request->job_type ? $request->job_type : null;
        $filters['taxCode'] = $request->tax_codes ? implode(',', $request->tax_codes) : null;
        $filters['startDate'] = $request->start_date ? date("Y-m-d", strtotime($request->start_date)) : date('Y-m-d');
        $filters['endDate'] = $request->end_date ? date("Y-m-d", strtotime($request->end_date)) : date('Y-m-d');
        $filters['agreement'] = $request->agreement_ids ? implode(',', $request->agreement_ids) : null;
        $filters['payment_type'] = $request->payment_type ? $request->payment_type : null;
        $filters['filterResultBy'] = $request->filter_result_by ? $request->filter_result_by : null;
        $filters['startDateFilter'] = $request->start_date_filter ? date("Y-m-d", strtotime($request->start_date_filter)) : null;
        $filters['endDateFilter'] = $request->end_date_filter ? date("Y-m-d", strtotime($request->end_date_filter)) : null;
        $filters['report_basis'] = $request->report_basis;
        $filters['hasEmailOnFile'] = $request->hasEmailOnFile;
        $filters['autopayOnFile'] = $request->autopayOnFile;
        $filters['includePoints'] = isset($request->includePoints) ? $request->includePoints : null;

        $branchId = $request->branch_id;

        $salespeople = $request->salespeople ? implode(',', $request->salespeople) : [];

        $users = $request->user_ids ? implode(',', $request->user_ids) : null;

        $search = $request->search;
        $page = $request->page;
        $perPage = $request->perPage;

        $results = $this->getTransactionInvoices($branchId, $filters, $salespeople, $users, $search, $page, $perPage);

        $cashTotal =
            $checkTotal =
            $outstandingTotal =
            $cardTotal =
            $achTotal =
            $outsideTotal =
            $pointsTotal = 0.00;

        $cashRefundsTotal =
            $checkRefundsTotal =
            $outstandingRefundsTotal =
            $cardRefundsTotal =
            $achRefundsTotal =
            $outsideRefundsTotal =
            $pointsRefundsTotal = 0.00;

        $cashSummsTotal =
            $checkSummsTotal =
            $outstandingSummsTotal =
            $cardSummsTotal =
            $achSummsTotal =
            $outsideSummsTotal =
            $pointsSummsTotal = 0.00;

        $totalRefundsNumber = 0;
        $totalSaleNumber = 0;

        $includeRevs = 0;

        $counted = array();
        $reports = $results['reports'];
        // $count = $results['count'];

        foreach ($reports as $rep) {
            // $rep = (array)$rep;

            $type = $rep->paymentType;
            $actualPaymentType = $rep->actualPaymentType;
            $amount = (int)$rep->paymentAmount;
            $tid = $rep->tid;

            $childData = OrkestraTransaction::whereParentId($tid)->whereType('Refund')->first();
            $refundedStatus = ($childData != null) ? "yes" : "no";
            $rep->refundedStatus = $refundedStatus;
            $rep->isRefunded = ($rep->actualPaymentType == "Refund") ? "yes" : "no";

            if ($tid == null || !array_key_exists($tid, $counted)) {
                // return $rep->balance;
                $outstandingTotal = $rep->balance;
                $counted[$tid] = 1;
            }

            if ($actualPaymentType == 'Sale') {
                $totalSaleNumber += $amount;
                if ($type == 'Cash') {
                    $cashTotal += $amount;
                } elseif ($type == 'Check') {
                    $checkTotal += $amount;
                } elseif ($type == 'Card') {
                    $cardTotal += $amount;
                } elseif ($type == 'ACH') {
                    $achTotal += $amount;
                } elseif ($type == 'Points') {
                    $pointsTotal += $amount;
                } elseif ($type == 'Processed Outside') {
                    $outsideTotal += $amount;
                }
            } elseif ($actualPaymentType == 'Refund') {
                $totalRefundsNumber += $amount;
                if ($type == 'Cash') {
                    $cashRefundsTotal += $amount;
                } elseif ($type == 'Check') {
                    $checkRefundsTotal += $amount;
                } elseif ($type == 'Card') {
                    $cardRefundsTotal += $amount;
                } elseif ($type == 'ACH') {
                    $achRefundsTotal += $amount;
                } elseif ($type == 'Points') {
                    $pointsRefundsTotal += $amount;
                } elseif ($type == 'Processed Outside') {
                    $outsideRefundsTotal += $amount;
                }
            }
        }

        if ($request->download) {
            // return $results;

            return Excel::download(new ExportPaymentReport($reports), 'ExportPaymentReport.csv');

            //TransactionExportJob
            // ExportPaymentReportJob::dispatch($results);
            // return $this->sendResponse(true, 'Transactions export job has started. You will find the download link on your message board when its complete. This could take a few minutes.');
        }

        return $this->sendResponse(true, 'Payment report', [
            'results' => $results,
            'cashTotal' => $cashTotal,
            'checkTotal' => $checkTotal,
            'cardTotal' => $cardTotal,
            'achTotal' => $achTotal,
            'pointsTotal' => $pointsTotal,
            'outsideTotal' => $outsideTotal,
            'outstandingTotal' => $outstandingTotal,

            'totalSaleNumber' => $totalSaleNumber,
            'totalRefundsNumber' => $totalRefundsNumber,
            'totalNumber' => $totalSaleNumber - $totalRefundsNumber,

            'cashRefundsTotal' => $cashRefundsTotal,
            'checkRefundsTotal' => $checkRefundsTotal,
            'outstandingRefundsTotal' => $outstandingRefundsTotal,
            'cardRefundsTotal' => $cardRefundsTotal,
            'achRefundsTotal' => $achRefundsTotal,
            'outsideRefundsTotal' => $outsideRefundsTotal,
            'pointsRefundsTotal' => $pointsRefundsTotal,

            'cashSummsTotal' => $cashSummsTotal,
            'checkSummsTotal' => $checkSummsTotal,
            'outstandingSummsTotal' => $outstandingSummsTotal,
            'cardSummsTotal' => $cardSummsTotal,
            'achSummsTotal' => $achSummsTotal,
            'outsideSummsTotal' => $outsideSummsTotal,
            'pointsSummsTotal' => $pointsSummsTotal,

            'includeRefs' => $includeRevs
        ]);
    }


    public function getTransactionInvoices($branchId, $filters, $salespeople, $users, $search, $page, $perPage)
    {
        // dd($search);

        if ($filters['report_basis'] == 'accrual') {
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
                           CONCAT(u.first_name, ' ', u.last_name) as sales_person,
                           CONCAT(ca.street, ' ', ca.city, ', ', reg.name, ' ', ca.postal_code) as address,
                           DATE_FORMAT(i.date_due,'%c/%d/%Y') as dateDue,
                           DATE_FORMAT(t.date_created,'%c/%d/%Y') as paymentDate,
                           DATE_FORMAT(prcs.initial_service_date,'%c/%d/%Y') as initialServiceDate,
                           DATE_FORMAT(co.date_created,'%c/%d/%Y') as contractCreationDate,
                           t.network as paymentType,
                           t.type as actualPaymentType,
                           FORMAT(COALESCE(IF(t.network <> 'Points',t.amount,t.amount/100),0),2) as paymentAmount,
                           FORMAT(COALESCE(IF(t.network <> 'Points',t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2) as preTaxAmount,
                           i.sales_tax as taxRate,
                           i.balance,
                           r.external_id AS refNumber,
                           COALESCE(t.status, 'Unpaid') AS paymentStatus,
                           st.name AS service_type_name,
                           pcc.first_year_contract_value as first_year_contract_value,
                           csp.autopay as autopay,
                           j.type as jobtype,
                           co.date_start as initialdate,
                           pa.name as agreement_name,
                           co.found_by_type_id,
                           o.list_name as branch_name

                           FROM pocomos_contracts co
                           JOIN pocomos_invoices i ON i.contract_id = co.id
                           LEFT JOIN pocomos_invoice_transactions it ON i.id = it.invoice_id
                           JOIN orkestra_transactions t on (it.transaction_id = t.id and t.type IN ('Sale','Refund') and t.status = 'Approved')
                           LEFT JOIN pocomos_user_transactions AS ut ON t.id = ut.transaction_id
                           LEFT JOIN pocomos_salespeople AS psp ON co.salesperson_id = psp.id
                           LEFT JOIN orkestra_users AS u ON ut.user_id = u.id
                           LEFT JOIN pocomos_salespeople AS psp2  ON psp2.user_id = u.id
                           LEFT JOIN pocomos_jobs j on j.invoice_id = i.id
                           JOIN pocomos_pest_contracts pcc on co.id = pcc.contract_id
                           LEFT JOIN pocomos_pest_contract_service_types st ON pcc.service_type_id = st.id
                           LEFT JOIN pocomos_pest_contracts_tags pt on pcc.id = pt.contract_id
                           JOIN pocomos_customer_sales_profiles csp on co.profile_id = csp.id
                           JOIN pocomos_customers cu on csp.customer_id = cu.id
                           JOIN pocomos_addresses ca on cu.contact_address_id = ca.id
                           LEFT JOIN orkestra_countries_regions reg on ca.region_id = reg.id
                           LEFT JOIN orkestra_results r ON r.transaction_id = t.id
                           LEFT JOIN pocomos_agreements pa on co.agreement_id = pa.id
                           LEFT JOIN pocomos_reports_contract_states prcs on co.id = prcs.contract_id
                           JOIN pocomos_company_offices o on o.id = csp.office_id
                           WHERE csp.office_id = " . $branchId . "
                           AND (j.status = 'complete' OR j.status is NULL)
                           ";



            if (isset($filters['startDate'], $filters['endDate'])) {
                $sql .= ' AND ((j.id is NULL && i.date_due BETWEEN "' . $filters['startDate'] . '" AND "' . $filters['endDate'] . '")
                            OR (j.date_completed BETWEEN "' . $filters['startDate'] . '" AND "' . $filters['endDate'] . '"))';
            }

            if ($filters['filterResultBy'] == "initialService") {
                $sql .= " AND ((prcs.initial_service_date BETWEEN '" . $filters['startDateFilter'] . "' AND '" . $filters['endDateFilter'] . "'))";
            } elseif ($filters['filterResultBy'] == "signupDate") {
                $sql .= " AND ((co.date_created BETWEEN '" . $filters['startDateFilter'] . "' AND '" . $filters['endDateFilter'] . "'))";
            }
        } else {
            //cash show all revenue for payment (points, receive money) dates within the given range
            //as well as any misc invoice made in the date range (total value)
            //Cash = SHow all Transactions during period

            // return 81;

            $sql = "SELECT
                           DISTINCT t.id as tid,
                           cu.id as custId,
                           cu.external_account_id as custAcctId,
                           CONCAT(cu.first_name, ' ', cu.last_name) as name,
                           CONCAT(u.first_name, ' ', u.last_name) as sales_person,
                           CONCAT(ca.street, ' ', ca.city, ', ', reg.name, ' ', ca.postal_code) as address,
                           GROUP_CONCAT(DISTINCT i.id SEPARATOR ', ') as id,
                           DATE_FORMAT(i.date_due,'%c/%d/%Y') as dateDue,
                           DATE_FORMAT(prcs.initial_service_date,'%c/%d/%Y') as initialServiceDate,
                           DATE_FORMAT(co.date_created,'%c/%d/%Y') as contractCreationDate,
                           DATE_FORMAT(t.date_created,'%c/%d/%Y') as paymentDate,
                           t.network as paymentType,
                           t.type as actualPaymentType,
                           FORMAT(IF(t.network <> 'Points',t.amount,t.amount/100),2) as paymentAmount,
                           FORMAT(COALESCE(IF(t.network <> 'Points',t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2) as preTaxAmount,
                           i.balance,
                           i.sales_tax as taxRate,
                           r.external_id AS refNumber,
                           t.status AS paymentStatus,
                           st.name AS service_type_name,
                           pcc.first_year_contract_value as first_year_contract_value,
                           csp.autopay as autopay,
                           j.type as jobtype,
                           co.date_start as initialdate,
                           pa.name as agreement_name,
                           o.list_name as branch_name

                        FROM orkestra_transactions t
                        LEFT JOIN pocomos_user_transactions AS ut ON t.id = ut.transaction_id
                        LEFT JOIN orkestra_users AS u ON ut.user_id = u.id
                        LEFT JOIN pocomos_salespeople AS psp2  ON psp2.user_id = u.id
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
                        LEFT JOIN pocomos_agreements pa on co.agreement_id = pa.id
                        LEFT JOIN pocomos_reports_contract_states prcs on co.id = prcs.contract_id
                        JOIN pocomos_company_offices o on o.id = csp.office_id
                        WHERE csp.office_id = " . $branchId . "
                         AND t.type IN ('Sale','Refund')
                         AND t.status = 'Approved' ";

            if (isset($filters['startDate'], $filters['endDate'])) {
                $sql .= ' AND t.date_created BETWEEN "' . $filters['startDate'] . '" AND "' . $filters['endDate'] . '"';
            }

            if ($filters['filterResultBy'] == "initialService") {
                $sql .= " AND ((prcs.initial_service_date BETWEEN '" . $filters['startDateFilter'] . "' AND '" . $filters['endDateFilter'] . "'))";
            } elseif ($filters['filterResultBy'] == "signupDate") {
                $sql .= " AND ((co.date_created BETWEEN '" . $filters['startDateFilter'] . "' AND '" . $filters['endDateFilter'] . "'))";
            }
        }

        if ($salespeople) {
            $sql .=  'AND co.salesperson_id IN (' . $salespeople . ')';
        }

        if ($users) {
            $sql .=  'AND u.id IN (' . $users . ') ';
        }

        if (isset($filters['marketingType'])) {
            $sql .=  'AND co.found_by_type_id IN (' . $filters['marketingType'] . ')';
        }

        if (isset($filters['serviceType'])) {
            $sql .=  'AND pcc.service_type_id IN (' . $filters['serviceType'] . ')';
        }

        if (isset($filters['agreement'])) {
            // dd($filters['agreement']);
            $sql .= 'AND pa.id IN (' . $filters['agreement'] . ')';
        }

        if (isset($filters['tags'])) {
            $sql .= 'AND pt.tag_id IN (' . $filters['tags'] . ')';
        }


        if (isset($filters['taxCode'])) {
            $sql .= 'AND i.tax_code_id IN (' . $filters['taxCode'] . ')';
        }

        if (isset($filters['hasEmailOnFile'])) {
            if ($filters['hasEmailOnFile'] === 1) {
                $sql .=  "AND (cu.email != '' AND cu.email IS NOT null)";
            } else {
                $sql .=  "AND (cu.email = '' OR cu.email IS null)";
            }
        }

        if (isset($filters['autopayOnFile']) && $filters['autopayOnFile'] === true) {
            $sql .=  'AND csp.autopay = 1';
        }

        if (isset($filters['serviceFrequency'])) {
            $sql .=  'AND pcc.service_frequency = "' . $filters['serviceFrequency'] . '"';
        }

        if (isset($filters['jobType'])) {
            $sql .=  'AND j.type = "' . $filters['jobType'] . '"';
        }

        // payment type
        if (isset($filters['payment_type'])) {
            // dd(11);

            // dd($filters['payment_type']);

            switch ($filters['payment_type']) {
                case 'ACH' :
                    $transNetwork = 'ACH';
                    break;
                case 'Card' :
                    $transNetwork = 'Card';
                    break;
                case 'Cash' :
                    $transNetwork = 'Cash';
                    break;
                case 'Check' :
                    $transNetwork = 'Check';
                    break;
                case 'Points' :                     //token
                    $transNetwork = 'Points';
                    break;
                default:
                    $transNetwork = 'Card';
                    break;
            }
            $transNetworks[] = $transNetwork;
        // return $transNetworks;
        } else {
            $transNetworks = [
                'ACH',
                'Card',
                'Cash',
                'Check',
                'Points',
                'Processed Outside'
            ];
        }

        if (isset($filters['includePoints'])) {
            // dd(22);
            array_push($transNetworks, 'Points');
            // return $transNetworks;
        }

        // $transNetworks = implode('","', $transNetworks);
        $transNetworks = $this->convertArrayInStrings($transNetworks);

        // return $transNetworks;

        $sql .=  'AND t.network IN ('.$transNetworks.') ';

        if ($search) {
            $search = '"%' . $search . '%"';
            // dd($search);

            $formatDate = date('Y/m/d', strtotime($search));
            $date = '"%' . str_replace("/", "-", $formatDate) . '%"';

            $sql .= ' AND (
                            cu.external_account_id LIKE ' . $search . ' or
                            CONCAT(cu.first_name," ",cu.last_name) LIKE ' . $search . ' or
                            CONCAT(ca.street, " ", ca.city, ", ", reg.name, " ", ca.postal_code) LIKE ' . $search . ' or
                            i.id LIKE ' . $search . ' or
                            DATE_FORMAT(co.date_created,"%c/%d/%Y") LIKE ' . $search . ' or
                            DATE_FORMAT(prcs.initial_service_date,"%c/%d/%Y") LIKE ' . $search . ' or
                            DATE_FORMAT(t.date_created,"%c/%d/%Y") LIKE ' . $search . ' or
                            t.network LIKE ' . $search . ' or
                            FORMAT(COALESCE(IF(t.network <> "Points",t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2) LIKE ' . $search . ' or

                            (FORMAT(COALESCE(IF(t.network <> "Points",t.amount,t.amount/100),0),2)-
                                FORMAT(COALESCE(IF(t.network <> "Points",t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2))
                                LIKE ' . $search . ' or
                            FORMAT(IF(t.network <> "Points",t.amount,t.amount/100),2) LIKE ' . $search . ' or
                            COALESCE(t.status, "Unpaid") LIKE ' . $search . ' or
                            r.external_id LIKE ' . $search . ' or
                            st.name LIKE ' . $search . ' or
                            CONCAT(u.first_name, " ", u.last_name) LIKE ' . $search . '
                            )';
        }

        $sql .=  ' GROUP BY t.id, IF(t.id IS NULL, i.id, 0)';



        if ($filters['filterResultBy'] == "initialService") {
            $sql .=  "ORDER BY prcs.initial_service_date";
        } elseif ($filters['filterResultBy'] == "signupDate") {
            $sql .=  "ORDER BY co.date_created";
        } else {
            $sql .=  "ORDER BY name";
        }

        // return DB::select(DB::raw($sql));


        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));
        /**If result data are from DB::row query then `true` else `false` normal laravel get listing */
        
        if($page && $perPage){
            $paginateDetails = $this->getPaginationDetails($page, $perPage, true);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $sql .= " LIMIT $perPage offset $page";
        }

        $reports = DB::select(DB::raw($sql));

        return ['reports' => $reports, 'count' => $count];

        /*
        id = custAcctId
        invoice # = id
        taxAmount = paymentAmount - preTaxAmount
        sales_person
        */
    }
}
