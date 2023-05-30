<?php

namespace App\Http\Controllers\API\Pocomos\Financial;

use DB;
use PDF;
use Excel;
use Illuminate\Http\Request;
use App\Jobs\BillingSummaryJob;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Jobs\UnpaidInvoicesExportJob;
use App\Jobs\ServiceHistorySummaryJob;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosFormLetter;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosSmsFormLetter;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosInvoiceTransaction;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosDocusendConfiguration;
use App\Models\Pocomos\PocomosOfficeOpiniionSetting;
use App\Models\Pocomos\PocomosPestContractServiceType;

class UnpaidInvoiceController extends Controller
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
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $marketingTypes = PocomosMarketingType::whereOfficeId($officeId)->whereActive(1)->get();

        $serviceTypes = PocomosPestContractServiceType::whereOfficeId($officeId)->whereActive(1)->get();

        $sql = 'SELECT s.id, CONCAT(u.first_name, \' \', u.last_name) as name
                                FROM pocomos_salespeople s
                                JOIN pocomos_company_office_users ou ON s.user_id = ou.id AND ou.office_id = ' . $request->office_id . '
                                JOIN orkestra_users u ON ou.user_id = u.id WHERE 1 = 1 ';

        if ($request->search_term) {
            $searchTerm = "'" . $request->search_term . "'";
            $sql .= ' AND (u.first_name LIKE ' . $searchTerm . ' OR u.last_name LIKE ' . $searchTerm . ' OR u.username LIKE ' . $searchTerm . ' OR CONCAT(u.first_name, \' \', u.last_name) LIKE ' . $searchTerm . ')';
        }

        $sql .= ' ORDER BY u.first_name, u.last_name';

        $salesPeople = DB::select(DB::raw($sql));

        $tags = PocomosTag::whereOfficeId($officeId)->whereActive(1)->orderBy('name')->get();

        $salesStatus = PocomosSalesStatus::whereActive(true)->whereOfficeId($officeId)->get(['id', 'name']);

        $opinionSetting = PocomosOfficeOpiniionSetting::whereOfficeId($officeId)->whereActive(1)->first();

        $salesConfig = PocomosOfficeSetting::with('card_cred_details')->whereOfficeId($officeId)->firstorfail();

        return $this->sendResponse(true, 'Unpaid Invoices filters', [
            'marketing_types' => $marketingTypes,
            'service_types'   => $serviceTypes,
            'salespeople'   => $salesPeople,
            'tags'   => $tags,
            'sales_status'   => $salesStatus,
            'user'   => auth()->user(),
            'opinion_setting' => $opinionSetting,
            'office_settings' => $salesConfig,
        ]);
    }


    public function search(Request $request)
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

        $PocomosDocusendConfiguration = PocomosDocusendConfiguration::whereOfficeId($officeId)->first();

        $docusendEnabled = $PocomosDocusendConfiguration ? true : false;

        $letters = PocomosFormLetter::whereOfficeId($officeId)->whereActive(1)->get();

        $invoiceStatus = $request->invoice_status == 'paid' || $request->invoice_status == 'unpaid' ? 'paid' : $request->invoice_status;

        $now = date('Y-m-d');

        $dateSpans = [];

        $jobsBalance= 0;

        $jobsCount= 0;

        $page = $request->page;
        $perPage = $request->perPage;

        if ($request->lessThan30 == 1) {
            // $thirty = clone $now;
            // $thirty->modify('-30 days');
            $thirty = date('Y-m-d', strtotime('-30 days'));

            $dateSpans[] = array(null, $thirty);
        }
        if ($request->thirtyTo60 == 1) {
            $thirtyOne = date('Y-m-d', strtotime('-31 days'));
            $sixty = date('Y-m-d', strtotime('-60 days'));
            ;

            $dateSpans[] = array($sixty, $thirtyOne);
        }
        if ($request->sixtyTo90 == 1) {
            $ninety = date('Y-m-d', strtotime('-90 days'));
            $sixtyOne = date('Y-m-d', strtotime('-61 days'));
            ;

            $dateSpans[] = array($ninety, $sixtyOne);
        }
        if ($request->moreThan90 == 1) {
            $ninety = date('Y-m-d', strtotime('-90 days'));
            $dateSpans[] = array($ninety, null);
        }

        // return $dateSpans;

        $jobsQuery = PocomosJob::select(
            '*',
            'pocomos_jobs.date_scheduled as job_date_scheduled',
            'pocomos_jobs.date_completed as qqq',
            'pocomos_jobs.type as job_type',
            'pocomos_jobs.id as job_id',
            'pocomos_jobs.invoice_id',
            'pi.status as invoice_status',
            'pi.balance as invoice_balance',
            'pt.name as tag_name',
            'pag.name as agreement_name',
            'ppcst.name as service_type',
            'oa.active as card_active',
            'pcu.external_account_id',
            'oap.balance as points_ac_balance'
        )
            ->join('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
            ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
            ->leftjoin('pocomos_pest_contracts_tags as ppct', 'ppc.id', 'ppct.contract_id')
            ->leftjoin('pocomos_tags as pt', 'ppct.tag_id', 'pt.id')
            ->join('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            // ->join('pocomos_customers_notes as pcn', 'pcsp.customer_id', 'pcn.customer_id')
            // ->join('pocomos_notes as pn', 'pcn.note_id', 'pn.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->leftJoin('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
            ->leftJoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->leftJoin('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
            ->leftJoin('pocomos_route_slots as prs', 'pocomos_jobs.slot_id', 'prs.id')
            ->leftJoin('pocomos_routes as pr', 'prs.route_id', 'pr.id')

            ->join('pocomos_customers_accounts as pca', 'pcsp.id', 'pca.profile_id')
            ->join('orkestra_accounts as oa', 'pca.account_id', 'oa.id')
            ->leftjoin('pocomos_sub_customers as psc', 'pcu.id', 'psc.parent_id')
            ->leftjoin('orkestra_accounts as oap', 'pcsp.points_account_id', 'oap.id')
            ->leftjoin('pocomos_notes as pn', 'pcu.unpaid_note', 'pn.id')
            ->whereBetween('pocomos_jobs.date_completed', [$request->start_date, $request->end_date])
            ->groupBy('pocomos_jobs.invoice_id')
            ;

        if ($dateSpans) {
            foreach ($dateSpans as $span) {
                $start = $span[0];
                $end = $span[1];

                if ($start === null) {
                    $jobsQuery->where('pocomos_jobs.date_completed', '>', $end);
                } elseif (is_null($end)) {
                    $jobsQuery->where('pocomos_jobs.date_completed', '<', $start);
                } else {
                    $jobsQuery->whereBetween('pocomos_jobs.date_completed', [$start, $end]);
                }
            }
        }

        $jobsQuery->where('pag.office_id', $officeId)
            ->where('pocomos_jobs.status', 'complete')
            ->where('pi.status', '!=', 'cancelled')
            ->orderBy('pocomos_jobs.date_scheduled', 'ASC')
            ->orderBy('pocomos_jobs.time_scheduled', 'ASC')
            ->orderBy('pocomos_jobs.time_begin', 'ASC');

        if ($request->invoice_status) {
            if ($request->invoice_status === 'Unpaid' || $request->invoice_status === 'Paid') {
                $operator = $request->invoice_status === 'Unpaid' ? '!=' : '=';
                $jobsQuery->where('pi.status', $operator, 'Paid');
            } else {
                $jobsQuery->where('pi.status', $request->invoice_status);
            }
        }

        if ($request->service_type) {
            $jobsQuery->where('ppc.service_type_id', $request->service_type);
        }

        if ($request->service_frequency) {
            $jobsQuery->where('ppc.service_frequency', $request->service_frequency);
        }

        if ($request->job_type) {
            $jobsQuery->where('pocomos_jobs.type', $request->job_type);
        }

        if ($request->marketing_type != null) {
            $jobsQuery->where('pc.found_by_type_id', $request->marketing_type);
        }

        if ($request->salesperson != null) {
            $jobsQuery->where('pc.salesperson_id', $request->salesperson);
        }

        if ($request->acct_on_file == 1) {
            $jobsQuery->join('orkestra_accounts as oa', 'pcsp.autopay_account_id', 'oa.id')
                ->whereIn('oa.type', ['BankAccount', 'CardAccount']);
        }

        if ($request->autopay_on_file == 1) {
            $jobsQuery->where('pcsp.autopay', true);
        }

        if (isset($request->has_email_on_file)) {
            if ($request->has_email_on_file == true) {
                $jobsQuery->where(function ($jobsQuery) {
                    $jobsQuery->where("pcu.email", '!=', '')
                        ->where("pcu.email", '!=', null);
                });
            } else {
                $jobsQuery->where(function ($jobsQuery) {
                    $jobsQuery->where("pcu.email", '')
                        ->orwhere("pcu.email", null);
                });
            }
        }

        // return $jobsQuery->take(100)->get();

        // in filter
        if ($request->search_terms) {
            $searchTerms = $request->search_terms;

            $jobsQuery->where(function ($jq) use ($searchTerms) {
                $jq->where('pcu.first_name', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pcu.last_name', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pcu.email', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pa.street', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pa.suite', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pa.city', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pag.name', 'like', '%' . $searchTerms . '%');
            });
        }

        if ($request->tags) {
            $jobsQuery->whereIn('ppct.tag_id', $request->tags);
        }

        if ($request->not_tags) {
            $jobsQuery->whereNotIn('ppct.tag_id', $request->not_tags);
        }

        if ($request->search) {
            $search = '%' . $request->search . '%';

            $formatDate = date('Y/m/d', strtotime($request->search));
            $date = str_replace("/", "-", $formatDate);

            $jobsQuery->where(function ($jq) use ($search, $date) {
                $jq->where('pcu.external_account_id', 'like', $search)
                    ->orWhere('pi.id', 'like', $search)
                    // ->orWhere('pocomos_jobs.id', 'like', $search)
                    ->orWhere('pocomos_jobs.date_scheduled', 'like', '%' . $date . '%')
                    ->orWhere('pocomos_jobs.date_completed', 'like', '%' . $date . '%')
                    ->orWhere(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'LIKE', $search)
                    ->orWhere('ppn.number', 'like', $search)
                    ->orWhere('pcu.email', 'like', $search)
                    ->orWhere('ppcst.name', 'like', $search)
                    ->orWhere('pi.status', 'like', $search)
                    ->orWhere('pi.balance', 'like', $search);
            });
        }

        if($request->all_ids) {
            $invoiceIds = $jobsQuery->pluck('invoice_id');
            // $custIds = $jobsQuery->pluck('customer_id');

            $i=0;
            foreach($invoiceIds as $invId){
                $allIds[$i]['invoiceId'] = $invId;
                $i++;
            }

            // return $i;
            // $allIds[228]['contractId'] = 88888;
            // return $allIds;

        } else {
            // $allJobs = (clone($jobsQuery));

            /**For pagination */
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $jobsCount = (clone($jobsQuery))->get()->count();

            $jobsBalance = $jobsQuery->sum('pi.balance');
            
            $jobsQuery->skip($perPage * ($page - 1))->take($perPage);

            
            // return $jobs;
            // return count($jobs);
            
            $jobs = $jobsQuery->get()->makeHidden('agreement_body');
        }

        /* Misc Invoices*/

        // $invIds = PocomosInvoice::join('pocomos_jobs as pj', 'pocomos_invoices.id', 'pj.invoice_id')
        //                     ->pluck('invoice_id')->toArray();

        $query = PocomosInvoice::select(
            '*',
            'pocomos_invoices.id as invoice_id',
            'pocomos_invoices.status as invoice_status',
            'pocomos_invoices.balance as invoice_balance',
            'pcu.email',
            'pc.id as contract_id',
            'oap.balance as points_ac_balance'
        )
            ->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')

            ->join('pocomos_pest_contracts as ppc', 'pc.id', 'ppc.contract_id')
            ->join('pocomos_pest_contracts_tags as ppct', 'ppc.id', 'ppct.contract_id')
            ->join('pocomos_tags as pt', 'ppct.tag_id', 'pt.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->join('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->join('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')

            ->leftjoin('pocomos_sub_customers as psc', 'pcu.id', 'psc.parent_id')
            ->leftjoin('orkestra_accounts as oap', 'pcsp.points_account_id', 'oap.id')
            ->leftjoin('pocomos_notes as pn', 'pcu.unpaid_note', 'pn.id')

            // ->whereNotIn('pocomos_invoices.id', $invIds)
            
            ->whereBetween('pocomos_invoices.date_due', [$request->start_date, $request->end_date])
            ->groupBy('pocomos_invoices.id')
            ;

            // return 11;
        //   return   $miscInvoicesCount = $query->count();


        if ($dateSpans) {
            foreach ($dateSpans as $span) {
                $start = $span[0];
                $end = $span[1];

                if ($start === null) {
                    $query->where('pocomos_invoices.date_due', '>', $end);
                } elseif (is_null($end)) {
                    $query->where('pocomos_invoices.date_due', '<', $start);
                } else {
                    $query->whereBetween('pocomos_invoices.date_due', [$start, $end]);
                }
            }
        }

        $query->where('pag.office_id', $officeId)
            ->whereNotIn('pocomos_invoices.status', ['Cancelled'])
            ->orderBy('pocomos_invoices.date_created', 'ASC')
            ->orderBy('pocomos_invoices.date_due', 'ASC');


        if ($request->invoice_status) {
            if ($request->invoice_status === 'Unpaid') {
                $unpaidStatusArray = ['Past due', 'Due', 'In collections', 'Collections'];
                $query->whereIn('pocomos_invoices.status', $unpaidStatusArray);
            } elseif ($request->invoice_status === 'Paid') {
                $query->whereIn('pocomos_invoices.status', ['Paid']);
            } else {
                $query->whereIn('pocomos_invoices.status', [$request->invoice_status]);
            }
        }

        if ($request->paid !== null) {
            $operator = $request->paid === false ? '!=' : '=';
            $query->where('pocomos_invoices.status', $operator, 'Paid');
        }

        if ($request->marketing_type != null) {
            $query->where('pc.found_by_type_id', $request->marketing_type);
        }

        if ($request->salesperson != null) {
            $query->where('pc.salesperson_id', $request->salesperson);
        }

        if ($request->acct_on_file == 1) {
            $query->join('orkestra_accounts as oa', 'pcsp.autopay_account_id', 'oa.id')
                ->whereIn('oa.type', ['BankAccount', 'CardAccount']);
        }

        if ($request->autopay_on_file == 1) {
            $query->where('pcsp.autopay', true);
        }

        // values = null,1,0
        if (isset($request->has_email_on_file)) {
            if ($request->has_email_on_file == true) {
                // return 77;
                $query->where(function ($q) {
                    $q->where("pcu.email", '!=', '')
                        ->where("pcu.email", '!=', null);
                });
            } else {
                $query->where(function ($q) {
                    $q->where("pcu.email", '')
                        ->orwhere("pcu.email", null);
                });
            }
        }

        if ($request->email != null) {
            $query->where('pcu.email', 'like', '%' . $request->email . '@%');

            if ($request->email == 'verifiedEmail') {
                $query->where('pcu.email_verified', true);
            }
        }

        // in filter
        if ($request->search_terms) {
            $searchTerms = $request->search_terms;

            $query->where(function ($query) use ($searchTerms) {
                $query->where('pcu.first_name', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pcu.last_name', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pcu.email', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pa.street', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pa.suite', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pa.city', 'like', '%' . $searchTerms . '%')
                    ->orWhere('pag.name', 'like', '%' . $searchTerms . '%');
            });
        }

        if ($request->tags) {
            $query->whereIn('ppct.tag_id', $request->tags);
        }

        if ($request->not_tags) {
            $query->whereNotIn('ppct.tag_id', $request->not_tags);
        }

        // search functionality
        if ($request->search) {
            $search = '%' . $request->search . '%';

            $formatDate = date('Y/m/d', strtotime($request->search));
            $date = str_replace("/", "-", $formatDate);

            $query->where(function ($query) use ($search, $date) {
                $query->where('pcu.external_account_id', 'like', $search)
                    ->orWhere('pocomos_invoices.id', 'like', $search)
                    ->orWhere('pocomos_invoices.date_due', 'like', '%' . $date . '%')
                    ->orWhere('pocomos_invoices.date_created', 'like', '%' . $date . '%')
                    ->orWhere(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'LIKE', $search)
                    ->orWhere('ppn.number', 'like', $search)
                    ->orWhere('pcu.email', 'like', $search)
                    ->orWhere('pocomos_invoices.balance', 'like', $search);
            });
        }

        // $allMiscInv = (clone($query));
        
        $miscInvoiceBal = 0;
        if($request->include_misc_invoice){
            $miscInvoiceBal = $query->sum('pocomos_invoices.balance');
        }
        $TotalOutstandingAmt = $jobsBalance + $miscInvoiceBal;

        // return $jobs->toArray();
        // $TotalOutstandingAmt = $this->calculateUnpaidOutstandingBalanceImproved($allJobs->get()->toArray(), $query->get()->toArray());

        $miscInvoicesCount = 0;

        if($request->all_ids){
            $invoiceIds = $query->pluck('invoice_id');

            // $i=$i;
            // return $i;

            foreach($invoiceIds as $invId){
                $allIds[$i]['invoiceId'] = $invId;
                $i++;
            }

                // foreach($invoiceIds as $invId){
                //     $allIds[$i]['invoiceId'] = $invId;
                //     $i++;
                // }

            // return $allIds;
        }else{
            $miscInvoicesCount = 0;
            if($request->include_misc_invoice){
                $miscInvoicesCount = (clone($query))->get()->count();
            }

            if (($page * $perPage) - $jobsCount <= 0) {
                // return $jobsCount;
                $miscInvoices = collect();
                // $miscInvoicesCount = $miscInvoices->count();
            } else {
                // return 99;
                $take = $page * $perPage - $jobsCount;
                // return $take;

                if ($take <= $perPage) {
                    // return 11;
                    $miscInvoices = $request->include_misc_invoice == 1 ? $query->take($take)
                                        ->get()->makeHidden('agreement_body') : collect();
                } else {
                    $miscInvoices = $request->include_misc_invoice == 1 ? $query->skip($take - $perPage)
                        ->take($perPage)->get()->makeHidden('agreement_body') : collect();
                }
                // $miscInvoicesCount = $miscInvoices->count();
            }
        }

        // return $miscInvoicesCount;


        $totalInvoicesCount = $jobsCount + $miscInvoicesCount;

        if ($request->export) {
            $officeUser = auth()->user()->pocomos_company_office_user;

            $terms = $this->createDefaultUnpaidSearchTerms();
            $hash = md5('unpaidExport' . $officeId . $officeUser->id . date('m-d-Y H:i:s'));

            $args = array(
                'officeId' => $officeId,
                'alertReceivingUsers' => array($officeUser->id),
                'terms' => serialize($terms),
                'hash' => $hash,
            );
            $job = UnpaidInvoicesExportJob::dispatch($args);


            // $exported_columns = $request->exported_columns ?? array();
            // ExportUnpaidInvoicesJob::dispatch($exported_columns);
            return $this->sendResponse(true, __('strings.sucess', ['name' => "Unpaid export job has started. You will find the download link on your message board when it's complete. This could take a few minutes."]));
        }

        if ($request->individual_invoices) {
            $jobs = $jobs;

            $jobIds = array();
            foreach ($jobs as $job) {
                $jobIds[] = $job->job_id;
            }

            // return $jobIds;

            if (count($jobIds)) {
                $segment = 250;
                $current = 0;
                while ($current < count($jobIds)) {
                    $ids = array_slice($jobIds, $current, $segment);
                    $current += $segment;

                    $args = array(
                        'job_ids' => $ids,
                        'misc_invoice_ids' => [],
                        'alertReceivingUsers' => auth()->user()->pocomos_company_office_user->id,
                        // 'returnUrl' => $return_url,
                    );

                    // return $args;
                    BillingSummaryJob::dispatch($args);
                }
            }

            // return 11;

            $miscInvoiceIds = array();
            foreach ($miscInvoices as $miscInvoice) {
                $miscInvoiceIds[] = $miscInvoice->invoice_id;
            }

            if (count($miscInvoiceIds)) {
                $segment = 250;
                $current = 0;

                while ($current < count($miscInvoiceIds)) {
                    $ids = array_slice($miscInvoiceIds, $current, $segment);
                    $current += $segment;

                    $args = array(
                        'job_ids' => [],
                        'misc_invoice_ids' => $ids,
                        'alertReceivingUsers' => auth()->user()->pocomos_company_office_user->id,
                        // 'returnUrl' => $return_url,
                    );

                    BillingSummaryJob::dispatch($args);
                }
            }

            if (count($jobIds) || count($miscInvoiceIds)) {
                return $this->sendResponse(true, 'The server has started working on your request. You will receive an alert when it is completed.');
            }
            return $this->sendResponse(true, 'There were no invoices found with the specified search terms');
        }

        if ($request->unpaid_summary) {
            // dd(11);

            // return PocomosContract::with('invoices')->find(6);
            // return 11;

            // if ($request->job_type === null && $request->include_misc_invoice == true) {
                // dd(11);
                $miscInvoices = $miscInvoices;
            // } else {
            //     $miscInvoices = array();
            // }

            // return $miscInvoices;
            $scIds = [];
            foreach ($miscInvoices as $invoice) {
                $scIds[] = $invoice->contract_id;
            }

            $pccIds = PocomosPestContract::join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
                ->whereIn('pc.id', $scIds)
                ->pluck('pocomos_pest_contracts.id');

            $hash = md5(date('Y-m-d H:i:s P'));

            $args = array(
                'pcc_ids' => $pccIds,
                'paid' => 'Unpaid',
                // 'returnUrl' => $returnUrl,
                'hash' => $hash,
                'alertReceivingUsers' => auth()->user()->pocomos_company_office_user->id,
            );

            ServiceHistorySummaryJob::dispatch($args);

            return $this->sendResponse(true, 'The server is processing your request. You will receive an alert when it is completed.');
        }

        if ($request->paid_summary) {
            // PaidInvoiceSummary::dispatch($miscInvoices);
            // return $this->sendResponse(true, __('strings.sucess', ['name' => 'The server has started working on your request']));

            // if ($request->job_type === null && $request->include_misc_invoice == true) {
                // dd(11);
                $miscInvoices = $miscInvoices;
            // } else {
            //     $miscInvoices = array();
            // }

            // return $miscInvoices;
            $scIds = [];
            foreach ($miscInvoices as $invoice) {
                $scIds[] = $invoice->contract_id;
            }

            $pccIds = PocomosPestContract::join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
                ->whereIn('pc.id', $scIds)
                ->pluck('pocomos_pest_contracts.id');

            $hash = md5(date('Y-m-d H:i:s P'));

            $args = array(
                'pcc_ids' => $pccIds,
                'paid' => 'Paid',
                // 'returnUrl' => $returnUrl,
                'hash' => $hash,
                'alertReceivingUsers' => auth()->user()->pocomos_company_office_user->id,
            );

            ServiceHistorySummaryJob::dispatch($args);

            return $this->sendResponse(true, 'The server is processing your request. You will receive an alert when it is completed.');
        }


        return $this->sendResponse(true, 'Unpaid invoices', [
            'jobs' => $jobs ?? [],
            'misc_invoices' => $miscInvoices ?? [],
            'total_invoices_count' => $totalInvoicesCount,
            'outstanding_amt' => $TotalOutstandingAmt,
            'all_ids' => $allIds ?? [],
            // 'letters' => $letters,
            // 'docusend_enabled' => $docusendEnabled,
        ]);

        /*
        id = external ac id from pocomos_customers
        # = invoice_id (for jobs list job_id)
        scheduled date = job_date_scheduled
        completed date = date_completed
        due Date= date_due
        created Date= date_created
        name, number and email from customers
        balance = invoice_balance
        outstanding amount=  total of balance
        billing note =summary

        for more:
        customer name and id
        street, suit, city, postal code
        contract = agreement name

        service type = service type, job_type, status(job status)-invoice status
        */
    }

    public function moreInfo(Request $request, $jobId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            // 'office_ids' => 'required|array'
        ]);

        $officeId = $request->office_id;
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $job = PocomosJob::select('pa.name as contracts', 'pt.name as tags')
            ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
            ->leftJoin('pocomos_pest_contracts_tags as ppct', 'ppc.id', 'ppct.contract_id')
            ->leftJoin('pocomos_tags as pt', 'ppct.tag_id', 'pt.id')
            ->leftJoin('pocomos_pest_contracts_pests as ppcp', 'ppc.id', 'ppcp.contract_id')
            ->leftJoin('pocomos_pests as pp', 'ppcp.pest_id', 'pp.id')
            ->leftJoin('pocomos_pest_contracts_specialty_pests as ppcsp', 'ppc.id', 'ppcsp.contract_id')
            ->leftJoin('pocomos_pests as psp', 'ppcsp.pest_id', 'psp.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_agreements as pa', 'pc.agreement_id', 'pa.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->where('pocomos_jobs.id', $jobId)
            ->where('pcsp.office_id', $officeId)
            ->first();

        if(!$job) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate Job']));
        }

        if ($request->customer_id) {
            $customer = $this->findOneByIdAndOffice_customerRepo($request->customer_id, $officeId);

            if(!$customer) {
                throw new \Exception(__('strings.message', ['message' => 'Unable to locate Customer']));
            }
        } else {
            // $customer = $job->pest_contract->contract->pest_contract->customer;
        }

        return $this->sendResponse(true, __('strings.details', ['name' => 'More']), [ $job ]);
    }


    public function getAccounts($profileId)
    {
        //card = card accounts
        //ach = bank account
        //cash or check, token (id will be same) =simple ac
        //ac cred = PointsAccount
        //ext ac = simpl ac

        $accounts = PocomosCustomersAccount::with('account_detail')
            //    ->whereHas('account_detail', function($query) {
            //         $query->where('type','BankAccount');
            //     })
            ->whereProfileId($profileId)->get();

        return $this->sendResponse(true, 'Accounts', $accounts);
    }

    public function createChargeAction(request $request, $invoiceId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'account_id' => 'required',
            'method' => 'required|in:card,ach,cash,check,points,processed outside',
            'amount' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $invoice = $this->getInvoiceById($invoiceId);
        if (!$invoice) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate Invoice']));
        }

        $invoice = PocomosInvoice::find($invoiceId);

        $officeId = $invoice->contract->profile_details->office_details->id;
        $custId = $invoice->contract->profile_details->customer->id;

        $pointsAccount = $invoice->contract->profile_details->points_account;
        $pointsByDollar = $pointsAccount->balance / 100;

        $generalValues['office_id'] = $officeId;
        $generalValues['customer_id'] = $custId ?? null;

        $payment['amount'] = $pointsByDollar;
        $payment['account_id'] = $pointsAccount->id;
        $payment['method'] = $request->method;
        $payment['description'] = '';

        $userId = auth()->user()->id;

        $this->processPayment($invoiceId, $generalValues, $payment, $userId);

        /* $this->updateBillingInformation($invoiceId, $request->account_id);

        $PocomosOfficeSetting = PocomosOfficeSetting::whereOfficeId($request->office_id)->first();

        $method = $request->method;

        if ($method == 'Card') {
            $credId = $PocomosOfficeSetting->card_credentials_id;
        } elseif ($method == 'ACH') {
            $credId = $PocomosOfficeSetting->ach_credentials_id;
        } elseif ($method == 'Cash') {
            $credId = $PocomosOfficeSetting->cash_credentials_id;
        } elseif ($method == 'Check') {
            $credId = $PocomosOfficeSetting->check_credentials_id;
        } elseif ($method == 'Points') {
            $credId = $PocomosOfficeSetting->points_credentials_id;
        } else {
            $credId = $PocomosOfficeSetting->external_credentials_id;
        }

        $transaction_data['account_id'] = $request->account_id;
        $transaction_data['credentials_id'] = $credId;
        $transaction_data['amount'] = $request->amount;
        $transaction_data['type'] = 'Sale';
        $transaction_data['network'] = $request->method;
        $transaction_data['status'] = 'Approved';
        $transaction_data['active'] = '1';
        $transaction_data['description'] = $request->description;
        $transaction_create = OrkestraTransaction::create($transaction_data);

        $user_transaction['invoice_id'] = $invoiceId;
        $user_transaction['transaction_id'] = $transaction_create->id;
        $user_transaction['active'] = true;
        $user_transaction['memo'] = '';
        $user_transaction['type'] = $request->description ?: 'Invoice';
        //    $user_transaction['user_id'] = $request->user;
        $userTransaction = PocomosUserTransaction::create($user_transaction);

        $invoice_transaction['invoice_id'] = $invoiceId;
        $invoice_transaction['transaction_id'] = $transaction_create->id;
        $invoicetransaction = PocomosInvoiceTransaction::create($invoice_transaction);

        $invoice = PocomosInvoice::whereId($invoiceId)->first();
        $finalBalance = $invoice->balance - $request->amount;

        $invoice->balance = $finalBalance;
        $invoice->save(); */

        return $this->sendResponse(true, 'Payment has been processed successfully.');
    }

    public function updateBillingInformation($invoiceId, $accountId)
    {
        $billingAddress = PocomosInvoice::with('contract.profile_details.customer_details.billing_address.region.country_detail')->whereId($invoiceId)->firstOrFail()->contract->profile_details->customer_details->billing_address;

        OrkestraAccount::whereId($accountId)->update([
            'address' => $billingAddress->street . ' ' . $billingAddress->suite,
            'city' => $billingAddress->city,
            'region' => $billingAddress->region->code,
            'country' => $billingAddress->region->country_detail->code,
            'postal_code' => $billingAddress->postal_code,
        ]);
    }

    public function downloadInvoice(Request $request)
    {
        // $v = validator($request->all(), [
        //     'customer_id' => 'required|integer|min:1',
        //     'invoice_id' => 'required|integer|min:1',
        //     'office_id' => 'required|integer|min:1',
        // ]);

        // if ($v->fails()) {
        //     return $this->sendResponse(false, $v->errors()->first());
        // }

        $customer = PocomosCustomer::where('id', $_GET['customer_id'])->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $_GET['customer_id'])->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $invoice_id = PocomosInvoice::where('id', $_GET['invoice_id'])->first();

        $item = PocomosInvoiceItems::with('tax_code:id,tax_rate')->where('invoice_id', $_GET['invoice_id'])->get();

        $chargesRefunds = PocomosInvoiceTransaction::with('transactions')->whereInvoiceId($_GET['invoice_id'])->get();

        $amount = 0;
        foreach ($chargesRefunds as $w) {
            $amount += $w->transactions->amount;
        }

        $totalDue = $item[0]->price - $amount;

        $data = [
            'invoice_data' => $invoice_id,
            'invoice_item_data' => $item,
            'total_due' => $totalDue,
        ];

        // $variables = ['{{Items}}', '{{Description}}', '{{Cost}}', '{{Qty}}', '{{Tax}}', '{{Total}}'];

        // $values = [$items, $Description, $Cost, $Qty, $Tax, $Total];
        $values = $data;

        // $estimateReport = str_replace($variables, $values, implode(',',$values));
        $unpaidInvoice = implode(',', $values);

        $pdf = $this->getInvoiceBasePdf($invoice_id);

        // $url =  "unpaid_invoice/" . 'invoice_'.$invoice_id->id . '.pdf';
        $url = "unpaid_invoice/" . 'invoice_'.$invoice_id->id . '_'.strtotime('now') . '.pdf';

        Storage::disk('s3')->put($url, $pdf->output(), 'public');

        $path = Storage::disk('s3')->url($url);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Unpaid invoice pdf']), $path);
    }

    public function addDiscount(Request $request, $id)
    {
        $v = validator($request->all(), [
            'description' => 'required',
            'value_type' => 'required|in:dollar,percent',
            'amount' => 'required|numeric',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }


        $invoice = PocomosInvoice::whereId($id)->first();

        if ($request->value_type == 'dollar') {
            $discount = $request->amount;
        } else {
            $discount = $request->amount / 100 * $invoice->balance;
        }

        // return $discount;

        $balance = $invoice->balance - $discount;

        $invoice->balance = $balance;
        $invoice->save();

        /*
        $price = PocomosInvoiceItems::whereType('Discount')->firstOrFail()->price;
        $invoice->amount_due;
        if($request->value_type === 'percent'){
           return $price = round($invoice->amount_due,2)*round($price/100,2);
        }
        */

        return $this->sendResponse(true, 'The discount has been applied successfully.');
    }


    public function sendToCollections(Request $request, $id)
    {
        $invoice = PocomosInvoice::whereId($id)->firstOrFail();
        $invoice->status = 'Collections';
        $invoice->save();

        return $this->sendResponse(true, 'Added to collections.');
    }

    public function cancelInvoices(Request $request)
    {
        $v = validator($request->all(), [
            'invoice_ids' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $invoices = PocomosInvoice::with('job')->whereIn('id', $request->invoice_ids)->get();

        foreach ($invoices as $invoice) {
            $job = $invoice->job;
            $this->cancelJob($job, $invoice);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Invoices cancelled']));
    }

    public function inCollectionsActionInvoiceController(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required',
            'invoice_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $custId = $request->customer_id;

        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        $customer = $this->findOneByIdAndOffice_customerRepo($custId, $officeId);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find Customer entity.']));
        }

        $invoice = $this->getInvoice($custId, $request->invoice_id);

        if (!$invoice) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate Invoice entity']));
        }

        // receivedByCollections
        $invoice->status = 'In collections';

        $invoice->save();

        return $this->sendResponse(true, __('strings.update', ['name' => 'Invoice status']));

    }

    public function receivedByCollectionsBulkByInvoiceAction(Request $request)
    {
        $v = validator($request->all(), [
            'invoice_ids' => 'required|array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        $invoices = $this->findByIdsAndOffice_invoiceRepo($request->invoice_ids,$officeId);
        
        // receivedByCollections
        foreach($invoices as $inv){
            $inv->update(['status' => 'In collections']);
        }

        return $this->sendResponse(true, __('strings.update', ['name' => 'Invoice status']));
    }

    private function getInvoice($customerId, $invoiceId)
    {
        $invoice = PocomosInvoice::join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->where('pcsp.customer_id', $customerId)
            ->where('pocomos_invoices.id', $invoiceId)
            ->first();

        return $invoice;
    }

    private function getInvoiceById($invoiceId)
    {
        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        $invoice = PocomosInvoice::join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
            ->where('pag.office_id', $officeId)
            ->where('pocomos_invoices.id', $invoiceId)
            ->first();

        return $invoice;
    }

    public function salesByInvoicesAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'invoice_ids'    => 'required',
            'sales_status_id' => 'required|exists:pocomos_sales_status,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        //getContractsByInvoiceIdsAndOffices
        $contractIds = PocomosContract::select('pocomos_contracts.id')->join('pocomos_customer_sales_profiles as pcsp', 'pocomos_contracts.profile_id', 'pcsp.id')
            ->join('pocomos_invoices as pi', 'pocomos_contracts.id', 'pi.contract_id')
            ->join('pocomos_company_offices as pco', 'pcsp.office_id', 'pco.id')
            ->where('pcsp.office_id', $officeId)
            ->whereIn('pi.id', $request->invoice_ids)
            ->pluck('id')->toArray();

        $pocomosSalesStatus = PocomosSalesStatus::whereId($request->sales_status_id)->whereOfficeId($officeId)->first();

        if (!$pocomosSalesStatus) {
            return $this->sendResponse(false, 'Invalid sales status');
        }

        $contractIds = implode(',', $contractIds);

        $currentDate = now()->format('Y-m-d H:i:s');

        $sql = 'UPDATE pocomos_contracts c
                LEFt JOIN pocomos_sales_status ss ON c.sales_status_id = ss.id
                SET c.sales_status_id = ' . $request->sales_status_id . ',
                c.sales_status_modified = (CASE ((!ss.serviced OR ss.serviced is NULL) && (SELECT serviced from pocomos_sales_status where id = ' . $request->sales_status_id . ')) WHEN TRUE THEN "'.$currentDate.'" ELSE c.sales_status_modified END)
                WHERE c.id IN (' . $contractIds . ')';

        DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'Status updated successfully');
    }

    public function sendActionFormLetterController(Request $request)
    {
        $v = validator($request->all(), [
            'form_letter' => 'required|exists:pocomos_form_letters,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'job_ids' => 'nullable',
            // 'subject' => 'nullable',
            // 'message' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $formLetter = PocomosFormLetter::where('office_id', $request->office_id)->where('id', $request->form_letter)->where('active', 1)->first();

        if (!$formLetter) {
            return $this->sendResponse(false, 'Unable to find the Form Letter.');
        }

        $formLetterId = $request->form_letter;
        $jobIds = $request->job_ids;
        $invoiceIds = $request->invoice_ids;

        if ($jobIds) {
            // dd(88);
            $result = $this->sendFormLetterFromJobIds($jobIds, $formLetterId, $request->office_id);
        }

        if($invoiceIds){
            $result = $this->sendFormLetterFromInvoiceIds($invoiceIds, $formLetterId);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Sent messages']));
    }


    public function sendAction_smsFormLetterController(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            // 'customers' => 'required|array',
            'form_letter' => 'nullable',
            'message' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        // $office = $this->getCurrentOffice();
        // $jobIds = $request->get('jobs',[]);
        // $invoiceIds = $request->get('invoices',[]);
        // $letterId = $request->get('form-letter');

        $smsFormLetterId = $request->form_letter;
        $jobIds = $request->job_ids;
        $invoiceIds = $request->invoice_ids;

        if ($smsFormLetterId) {
            $letter = PocomosSmsFormLetter::whereActive(true)->whereOfficeId($officeId)->findOrFail($smsFormLetterId);
        } else {
            $message = $request->message;

            $letter = new PocomosSmsFormLetter();
            $letter->office_id = $officeId;
            $letter->category = 0;
            $letter->title = '';
            $letter->message = $message;
            $letter->description = '';
            $letter->confirm_job = 1;
            $letter->require_job = false;
            $letter->active = true;
            $letter->save();
        }

        if ($jobIds) {
            // dd(88);
            $sentCount = $this->sendSmsFormLetterFromJobIds($jobIds, $letter);
        }

        if($invoiceIds){
            $result = $this->sendSmsFormLetterFromInvoiceIds($invoiceIds, $letter);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Sent messages']));
    }

    /**
    * Function basic overview
    * @param  type $input_param_name
    * @return void
    * sendAction_UnpaidInvoiceController
    */
    public function mailInvoice(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'type' => 'required',
            'summary' => 'nullable',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'contract_id' => 'nullable|exists:pocomos_contracts,id',
            'invoices' => 'array',
            'invoices.*' => 'exists:pocomos_invoices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::find($custId);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find Customer Entity']));
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $custId)->first();

        if (!$profile) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find profile Entity']));
        }

        $officeUserId = auth()->user()->pocomos_company_office_user->id;

        $officeUser = PocomosCompanyOfficeUser::whereId($officeUserId)->firstOrFail();

        $formData = $request->all();

        $this->resendEmails($profile, $officeUser, $formData);

        return $this->sendResponse(true, __('strings.message', ['message' => 'The message will be sent shortly. You will be notified when the email is sent.']));
    }


}
