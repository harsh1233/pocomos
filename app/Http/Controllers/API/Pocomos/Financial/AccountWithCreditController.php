<?php

namespace App\Http\Controllers\API\Pocomos\Financial;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosTag;
use App\Models\Pocomos\PocomosJob;
use App\Jobs\BulkApplyAccountCreditJob;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosInvoiceInvoicePayment;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use Excel;
use App\Exports\ExportAccountsWithCredit;

class AccountWithCreditController extends Controller
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

        $serviceTypes = PocomosService::whereOfficeId($officeId)->whereActive(1)->get();

        $marketingTypes = PocomosMarketingType::whereOfficeId($officeId)->whereActive(1)->get();

        $sql = 'SELECT s.id, CONCAT(u.first_name, \' \', u.last_name) as name
            FROM pocomos_salespeople s
            JOIN pocomos_company_office_users ou ON s.user_id = ou.id AND ou.office_id = ' . $request->office_id . '
            JOIN orkestra_users u ON ou.user_id = u.id ORDER BY u.first_name, u.last_name';

        $salesPeople = DB::select(DB::raw($sql));

        $tags = PocomosTag::whereOfficeId($officeId)->whereActive(1)->orderBy('name')->get();

        return $this->sendResponse(true, 'Payment report filters', [
            'service_types'   => $serviceTypes,
            'marketing_types' => $marketingTypes,
            'salespeople' => $salesPeople,
            'tags'   => $tags,
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

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $now = date('Y-m-d');

        $dateSpans = [];

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

        // findCreditedCSPWithSearchTerms
        $query = PocomosCustomerSalesProfile::select(
            '*',
            'pc.*',
            'pcu.*',
            'oa.*',
            'pcu.id as cust_id',
            'ot.description',
            'pcs.balance_credit',
            'ot.date_created as date_added'
        )
            ->join('pocomos_customers as pcu', 'pocomos_customer_sales_profiles.customer_id', 'pcu.id')
            ->join('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
            ->join('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->join('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
            ->join('orkestra_accounts as oa', 'pocomos_customer_sales_profiles.points_account_id', 'oa.id')
            ->join('orkestra_transactions as ot', 'oa.id', 'ot.account_id')
            ->join('pocomos_company_offices as pco', 'pocomos_customer_sales_profiles.office_id', 'pco.id')
            ->join('pocomos_contracts as pc', 'pocomos_customer_sales_profiles.id', 'pc.profile_id')
            ->join('pocomos_pest_contracts as ppc', 'pc.id', 'ppc.contract_id')
            ->join('pocomos_jobs as pj', 'ppc.id', 'pj.contract_id')
            ->join('pocomos_route_slots as prs', 'pj.slot_id', 'prs.id')
            ->join('pocomos_invoices as pi', 'pc.id', 'pi.contract_id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
            ->where('oa.balance', '>', 0)
            ->whereBetween('pi.date_created', [$startDate, $endDate])
            ->whereBetween('pi.date_due', [$startDate, $endDate]);

        if ($dateSpans) {
            foreach ($dateSpans as $span) {
                $start = $span[0];
                $end = $span[1];

                if ($start === null) {
                    $query->where('pi.date_created', '>', $end)
                        ->where('pi.date_due', '>', $end);
                } elseif (is_null($end)) {
                    $query->where('pi.date_created', '<', $start)
                        ->where('pi.date_due', '<', $start);
                } else {
                    $query->whereBetween('pi.date_created', [$start, $end])
                        ->whereBetween('pi.date_due', [$start, $end]);
                }
            }
        }

        $query->where('pi.status', '!=', 'Cancelled');

        if ($request->confirmed == 1) {
            $query->where('prs.type', 'Confirmed');
        } elseif ($request->confirmed == 0) {
            $query->where('prs.type', '!=', 'Confirmed');
        }

        if ($request->invoice_status == 'Unpaid' || $request->invoice_status == 'Paid') {
            $operator = $request->invoice_status === 'Unpaid' ? '!=' : '=';
            $query->where('pi.status', $operator, 'Paid');
        }

        if ($request->service_type) {
            $query->where('ppc.service_type_id', $request->service_type);
        }

        if ($request->service_frequency) {
            $query->where('ppc.service_frequency', $request->service_frequency);
        }

        if ($request->marketing_type != null) {
            $query->where('pc.found_by_type_id', $request->marketing_type);
        }

        if ($request->salesperson != null) {
            $query->where('pc.salesperson_id', $request->salesperson);
        }

        if ($request->acct_on_file == 1) {
            $query->join('orkestra_accounts as oa', 'pocomos_customer_sales_profiles.autopay_account_id', 'oa.id')
                ->whereIn('oa.type', ['BankAccount', 'CardAccount']);
        }

        if ($request->autopay_on_file == 1) {
            $query->where('pocomos_customer_sales_profiles.autopay', true);
        }

        if ($request->search_terms) {
            $search = '%' . $request->search_terms . '%';

            $query->where(function ($query) use ($search) {
                $query->where(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'LIKE', $search)
                    ->orWhere('number', 'like', $search)
                    ->orWhere('pcu.email', 'like', $search)
                    ->orWhere('pa.street', 'like', $search)
                    ->orWhere('pa.suite', 'like', $search)
                    ->orWhere('pa.city', 'like', $search)
                    ->orWhere('pag.name', 'like', $search);
            });
        }

        if ($request->search) {
            $search = '%' . $request->search . '%';

            $formatDate = date('Y/m/d', strtotime($request->search));
            $date = '%' . str_replace("/", "-", $formatDate) . '%';

            $query->where(function ($query) use ($search, $date) {
                $query->where(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'LIKE', $search)
                    ->orWhere('number', 'like', $search)
                    ->orWhere('pcu.email', 'like', $search)
                    ->orWhere('pcs.balance_credit', 'like', $search)
                    ->orWhere('ot.description', 'like', $search)
                    ->orWhere('ot.date_created', 'like', $date);
            });
        }

        if(!$request->all_ids){
            /**For pagination */
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $count = $query->count();
            $query->skip($perPage * ($page - 1))->take($perPage);
        }

        $results = $query->get()->makeHidden('agreement_body')->toArray();
        // return $results;

        if ($request->tags || $request->not_tags) {
            // return 99;
            foreach ($results as $q) {
                $tagIds = PocomosPestContract::join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
                    ->join('pocomos_pest_contracts_tags as ppct', 'pocomos_pest_contracts.id', 'ppct.contract_id')
                    ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                    ->where('pc.profile_id', $q['profile_id'])
                    ->pluck('ppct.tag_id');
                $cspTags[$q['profile_id']] = $tagIds;
            }
        }

        // return $cspTags;

        if (isset($cspTags)) {
            if ($request->tags) {
                $filterTags = $request->tags;

                // return $results;

                $results = array_filter($results, function ($csp) use ($filterTags, $cspTags) {
                    $tags = $cspTags[$csp['profile_id']];
                    foreach ($tags as $tag) {
                        if (in_array($tag, $filterTags)) {
                            return true;
                        }
                    }
                    return false;
                });
            }

            // return $results;

            if ($request->not_tags) {
                $notTags = $request->not_tags;

                $results = array_filter($results, function ($csp) use ($notTags, $cspTags) {
                    $tags[] = $cspTags[$csp['profile_id']];
                    // dd($tags);
                    $intersection = array_intersect($tags, $notTags);

                    return empty($intersection);
                });
            }
        }



        foreach ($results as $result) {
            // return $result;

            // for credit
            $result['unpaid_invoice_count'] = 1;
        }

        if($request->all_ids){
            $allIds = collect($results)->pluck('profile_id');
            $results = [];
        }

        if ($request->download) {
            return Excel::download(new ExportAccountsWithCredit($results), 'ExportAccountsWithCredit.csv');
        }

        return $this->sendResponse(true, 'Accounts list', [
            'results' => $results,
            'count' => $count ?? null,
            'all_ids' => $allIds ?? [],
        ]);
    }

    // public function getInvoices(PestControlContract $contract, $onlyCompletedJobs = false)
    // {
    //     $jobs = $contract->getJobs()->toArray();
    //     if ($onlyCompletedJobs) {
    //         $jobs = array_filter($jobs, function (Job $job) {
    //             return $job->isComplete();
    //         });
    //     }

    //     return array_merge(array_map(function (Job $job) {
    //         return $job->getInvoice();
    //     }, $jobs), $contract->getMiscInvoices()->toArray());
    // }


    public function receiveMoneyForm(Request $request, $customerId)
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

        $profileId = PocomosCustomerSalesProfile::where('customer_id', $customerId)->firstOrFail()->id;
        //  $contracts = PocomosContract::where('profile_id', $profileId)->pluck('id')->toArray();
        //    $invoices = PocomosInvoice::whereIn('contract_id', $contracts)->get();

        $jobs = PocomosJob::select('pi.*')
            ->join('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
            ->join('pocomos_contracts as pc', 'pi.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->where('pc.profile_id', $profileId)
            ->where('pi.status', '!=', 'Paid')
            ->where('pocomos_jobs.status', '!=', 'Cancelled')
            ->get();

        $invoices = PocomosInvoice::leftJoin('pocomos_jobs as pj', 'pocomos_invoices.id', 'pj.invoice_id')
            ->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->where('pc.profile_id', $profileId)
            ->whereNotIn('pocomos_invoices.status', ['Paid', 'Cancelled'])
            ->where('pj.status', '!=', 'Cancelled')
            ->orderBy('pocomos_invoices.date_due');

        $payments = PocomosInvoiceInvoicePayment::select('pi.*')
            ->join('pocomos_invoices as pi', 'pocomos_invoices_invoice_payments.invoice_id', 'pi.id')
            ->join('pocomos_invoice_payments as pip', 'pocomos_invoices_invoice_payments.payment_id', 'pip.id')
            ->join('pocomos_contracts as pc', 'pi.contract_id', 'pc.id')
            ->where('pc.profile_id', $profileId)
            ->where('pip.status', 'Unpaid')
            ->get();

        //cash or check =simple ac
        //ac cred = PointsAccount
        //ext ac = simpl ac

        $accounts = PocomosCustomersAccount::with('account_detail')
            //    ->whereHas('account_detail', function($query) {
            //         $query->where('type','BankAccount');
            //     })
            ->whereProfileId($profileId)->get();

        //profile id= customer id
        //id = #11111
        //balance=
        //due date
        //status

        return $this->sendResponse(true, 'Receive money form', [
            'jobs' => $jobs,
            'invoices' => $invoices,
            'payments' => $payments,
            'accounts' => $accounts,
        ]);
    }

    public function bulkApplyAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'profile_ids' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // return auth()->user()->pocomos_company_office_user->id;

        $args = [
            'office_id' => $request->office_id,
            'profile_ids' => $request->profile_ids,
            'office_user_id' => auth()->user()->pocomos_company_office_user->id,
        ];

        
        // return PocomosCustomerSalesProfile::get()->take(5);

        BulkApplyAccountCreditJob::dispatch($args);

        return $this->sendResponse(true, 'The server is processing these transactions, you will be notified when it completes.');
    }
}
