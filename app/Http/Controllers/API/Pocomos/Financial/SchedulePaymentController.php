<?php

namespace App\Http\Controllers\API\Pocomos\Financial;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosPestContractServiceType;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\PocomosTag;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosInvoiceInvoicePayment;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use Excel;
use App\Exports\ExportSchedulePayment;
use App\Jobs\BulkCardChargeJob;

class SchedulePaymentController extends Controller
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

        $marketingTypes = PocomosMarketingType::whereOfficeId($branchId)->whereActive(1)->get();

        $serviceTypes = PocomosPestContractServiceType::whereOfficeId($branchId)->whereActive(1)->get();

        $tags = PocomosTag::whereOfficeId($branchId)->whereActive(1)->orderBy('name')->get();

        $salesPeople = PocomosSalesPeople::leftJoin('pocomos_company_office_users as pcou', 'pocomos_salespeople.user_id', 'pcou.id')
                ->leftJoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->leftJoin('orkestra_user_preferences as oup', 'ou.id', 'oup.user_id')
                ->where('pocomos_salespeople.active', 1)
                ->where('pcou.office_id', $officeId)
                ->orderBy('ou.first_name')
                ->orderBy('ou.last_name')
                ->get();

        return $this->sendResponse(true, 'Schedule payment filters', [
            'service_types'   => $serviceTypes,
            'marketing_types' => $marketingTypes,
            'tags'   => $tags,
            'salespeople'   => $salesPeople,
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

        $query = PocomosInvoiceInvoicePayment::select(
            '*',
            'pocomos_invoices_invoice_payments.invoice_id',
            'pip.id',
            'pip.date_scheduled',
            'pj.id as job_id',
            'pj.type as job_type',
            'pi.status as invoice_status',
            'pip.status as invoice_payment_status',
            'pip.amount_in_cents',
            'pip.id as qqq'
        )
                ->join('pocomos_invoices as pi', 'pocomos_invoices_invoice_payments.invoice_id', 'pi.id')
                ->join('pocomos_invoice_payments as pip', 'pocomos_invoices_invoice_payments.payment_id', 'pip.id')
                ->join('pocomos_contracts as pc', 'pi.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
                ->join('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
                ->join('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
                ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id');

        if ($request->include_misc_invoice == 1) {
            $query->leftJoin('pocomos_jobs as pj', 'pi.id', 'pj.invoice_id')
                  ->leftJoin('pocomos_route_slots as prs', 'pj.slot_id', 'prs.id')
                  ->leftJoin('pocomos_pest_contracts as ppc', 'pj.contract_id', 'ppc.id');
        } else {
            $query->join('pocomos_jobs as pj', 'pi.id', 'pj.invoice_id')
                  ->leftJoin('pocomos_route_slots as prs', 'pj.slot_id', 'prs.id')
                  ->join('pocomos_pest_contracts as ppc', 'pj.contract_id', 'ppc.id');
        }

        $query->join('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id');

        $query->whereBetween('pip.date_scheduled', [$request->start_date, $request->end_date])
                ->where('pcu.status', 'Active');

        if ($request->paid !== null) {
            $operator = $request->paid === false ? '!=' : '=';
            $query->where('pi.status', $operator, 'Paid');
        }

        if ($request->service_type !== null) {
            $query->where('ppc.service_type_id', $request->service_type);
        }

        if ($request->service_frequency) {
            $query->where('ppc.service_frequency', $request->service_frequency);
        }

        if ($request->job_type !== null) {
            $query->where('pj.type', $request->job_type);
        }

        if ($request->marketing_type != null) {
            $query->where('pc.found_by_type_id', $request->marketing_type);
        }

        if ($request->salesperson != null) {
            $query->where('pc.salesperson_id', $request->salesperson);
        }

        if ($request->acct_on_file == 1) {
            $query->join('orkestra_accounts as oa', 'pcsp.autopay_account_id', 'oa.id')
                ->whereIn('oa.type', ['BankAccount','CardAccount']);
        }

        if ($request->autopay_on_file == 1) {
            $query->where('pcsp.autopay', true);
        }

        if ($request->search_terms) {
            $searchTerms = $request->search_terms;

            $query->where(function ($query) use ($searchTerms) {
                $query->where('pcu.first_name', 'like', '%'.$searchTerms.'%')
                ->orWhere('pcu.last_name', 'like', '%'.$searchTerms.'%')
                ->orWhere('pcu.email', 'like', '%'.$searchTerms.'%')
                ->orWhere('pa.street', 'like', '%'.$searchTerms.'%')
                ->orWhere('pa.suite', 'like', '%'.$searchTerms.'%')
                ->orWhere('pa.city', 'like', '%'.$searchTerms.'%')
                ->orWhere('pag.name', 'like', '%'.$searchTerms.'%');
            });
        }

        if ($request->search) {
            $search = '%'.$request->search.'%';

            $formatDate = date('Y/m/d', strtotime($request->search));
            $date = '%'.str_replace("/", "-", $formatDate).'%';

            $query->where(function ($query) use ($search, $date) {
                $query->where('pip.id', 'like', $search)
                    ->orWhere('pip.date_scheduled', 'like', $date)
                    ->orWhere(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'LIKE', $search)
                    ->orWhere('ppn.number', 'like', $search)
                    ->orWhere('pcu.email', 'like', $search)
                    ->orWhere(DB::raw('amount_in_cents/100'), 'like', $search)
                    ->orWhere('pocomos_invoices_invoice_payments.invoice_id', 'like', $search)
                ;
            });
        }

        if ($request->all_ids) {

            $paymentIds = $query->pluck('payment_id');
            $invoiceIds = $query->pluck('invoice_id');
            $custIds = $query->pluck('customer_id');

            $i=0;
            foreach($paymentIds as $pId){
                $allIds[$i]['paymentId'] = $pId;
                $allIds[$i]['invoiceId'] = $invoiceIds[$i];
                $allIds[$i]['custId'] = $custIds[$i];
                $i++;
            }
            // return $allIds;

        } else {

            /**For pagination */
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $count = $query->count();
            $totalOutstandingAmt = $query->sum('amount_in_cents')/100;
            $query->skip($perPage * ($page - 1))->take($perPage);

            $payments = $query->get()->makeHidden('agreement_body');

            $temp = '';
            $invoices = [];

            foreach ($payments as $payment) {
                if ($payment->id == $temp) {
                    $q[] = $payment->invoice_id;
                    $invoices[$payment->id] = $q;
                } else {
                    $q =[];
                    $q[] = $payment->invoice_id;
                    $invoices[$payment->id] = $q;
                }
                $temp = $payment->id;
            }
        }


        if ($request->download) {
            return Excel::download(new ExportSchedulePayment($payments), 'ExportSchedulePayment.csv');
        }

        /*
        outstnd amt = sum of due

        # = id (pip id)
        date schdld = date_scheduled
        due = amount_in_cents/100
        invoices = invoice_id of same id
        charge/paid = invoice_payment_status

        for more:
            job balance = amount_in_cents/100
            service type = name(service type), job_type, status(job status)-invoice status

        */

        return $this->sendResponse(true, 'Schedule payment list', [
            'payments'   => $payments ?? [],
            'invoices'   => $invoices ?? [],
            'total_outstanding_amt'   => $totalOutstandingAmt ?? null,
            'count'   => $count ?? null,
            'all_ids' => $allIds ?? [],
        ]);
    }


    // indexAction-BulkActionController
    public function bulkChargePayments(Request $request)
    {
        $v = validator($request->all(), [
            'payment_ids' => 'nullable',
            // 'invoice_ids' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $invoiceIds = PocomosInvoiceInvoicePayment::select(
            '*',
            'pocomos_invoices_invoice_payments.invoice_id',
            'pip.id',
            'pip.date_scheduled',
            'pi.status as invoice_status'
        )
        ->leftJoin('pocomos_invoices as pi', 'pocomos_invoices_invoice_payments.invoice_id', 'pi.id')
        ->leftJoin('pocomos_invoice_payments as pip', 'pocomos_invoices_invoice_payments.payment_id', 'pip.id')
        ->leftJoin('pocomos_contracts as pc', 'pi.contract_id', 'pc.id')
        ->leftJoin('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
        ->join('pocomos_company_offices as pco', 'pcsp.office_id', 'pco.id')
        ->whereIn('pip.id', $request->payment_ids)
        ->get()->pluck('invoice_id');

        BulkCardChargeJob::dispatch($invoiceIds);

        return $this->sendResponse(true, 'The server is processing these transactions, you will be notified when it finishes.');
    }


    public function bulkCancelPayments(Request $request)
    {
        $v = validator($request->all(), [
            'payment_ids' => 'nullable',
            // 'invoice_ids' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $invoiceIds = PocomosInvoiceInvoicePayment::select(
            '*',
            'pocomos_invoices_invoice_payments.invoice_id',
            'pip.id',
            'pip.date_scheduled',
            'pi.status as invoice_status'
        )
                ->leftJoin('pocomos_invoices as pi', 'pocomos_invoices_invoice_payments.invoice_id', 'pi.id')
                ->leftJoin('pocomos_invoice_payments as pip', 'pocomos_invoices_invoice_payments.payment_id', 'pip.id')
                ->leftJoin('pocomos_contracts as pc', 'pi.contract_id', 'pc.id')
                ->leftJoin('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_company_offices as pco', 'pcsp.office_id', 'pco.id')
                ->whereIn('pip.id', $request->payment_ids)
                ->get()->pluck('invoice_id');

        foreach ($invoiceIds as $invoiceId) {
            $request['customer_id'] = 21;
            $request['invoice_id'] = $invoiceId;

            app('App\Http\Controllers\API\Pocomos\Customer\InvoiceController')->cancelAction($request);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Cancelled payments']));
    }
}
