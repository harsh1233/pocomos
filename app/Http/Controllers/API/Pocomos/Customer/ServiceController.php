<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use DB;
use PDF;
use Excel;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosJob;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosRoute;
use App\Jobs\ServiceHistorySummaryJob;
use App\Models\Pocomos\PocomosInvoice;
use App\Jobs\PaidServiceHistorySummary;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosRouteSlots;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosCustomerState;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosPestInvoiceSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosPestContractsInvoice;
use App\Models\Pocomos\PocomosOfficeOpiniionSetting;

class ServiceController extends Controller
{
    use Functions;

    public function showScheduledServicesAction(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'contract_id' => 'required|exists:pocomos_pest_contracts,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // return date('Y-m-d');

        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        $customer = $this->findOneByIdAndOffice_customerRepo($custId, $officeId);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find Customer entity.']));
        }

        $contractId = $request->contract_id;

        $pestContract = PocomosPestContract::findOrFail($contractId);

        if ($contractId !== null) {
            $pestContract = PocomosPestContract::select('*', 'pocomos_pest_contracts.id')
                ->join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->where('pcsp.customer_id', $custId)
                ->where('pocomos_pest_contracts.id', $contractId)
                ->first();
        } else {
            // $pestContract = $this->getCurrentContract($customer);
        }

        if ($pestContract) {
            // return 11;
            $jobs = $this->findScheduledServicesForContract($pestContract->id);

            if ($request->today_only) {
                $jobs->where('pocomos_jobs.date_scheduled', date('Y-m-d'));
            }
        } else {
            // return 11;
            $jobs = collect();
        }

        $technicians = $this->findAllActiveByOffice_techRepo($officeId);
        if (!$technicians) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the technicians.']));
        }

        // $returnTech = [];
        // foreach ($technicians as $technician) {
        //     $returnTech[$technician->getId()] = $technician->getUser()->getUser()->__toString();
        // }

        if ($request->search) {
            $search = '%' . $request->search . '%';

            $formatDate = date('Y/m/d', strtotime($request->search));
            $date = "%" . str_replace('/', '-',  $formatDate) . "%";

            $jobs->where(function ($jobs) use ($search, $date) {
                $jobs->where('pocomos_jobs.date_scheduled', 'like', $date)
                    ->orWhere('pocomos_jobs.type', 'like', $search)
                    ->orWhere('pocomos_jobs.status', 'like', $search)
                    ->orWhere('pi.amount_due', 'like', $search)
                    ->orWhere(DB::raw("CONCAT(ou.first_name, ' ', ou.last_name)"), 'LIKE', $search);
            });
        }

        if ($request->all_ids) {
            $allIds = $jobs->pluck('job_id');
        } else {
            /**For pagination */
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $count = $jobs->count();
            $jobs->skip($perPage * ($page - 1))->take($perPage);

            if ($count > 0) {
                $jobs = $jobs->get()->each->append('total_amount_due');
            } else {
                $jobs = collect();
            }
        }

        // return $jobs;

        return $this->sendResponse(true, 'Schdule Service List', [
            'services' => $jobs ?? [],
            'count' => $count ?? null,
            'all_ids' => $allIds ?? [],
        ]);
    }

    /* API for list of Service */
    public function showServiceHistoryAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            // 'status' => 'required|array|in:Pending,Complete,Cancelled,Re-scheduled',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $opinionsetting = PocomosOfficeOpiniionSetting::where('active', 1)->where('office_id', $request->office_id)->first();

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $contract = PocomosContract::where('profile_id', $profile->id)->first();

        $pest_contract = PocomosPestContract::where('contract_id', $contract->id)->first();

        if ($pest_contract) {
            // $jobs = PocomosJob::with('invoice', 'technician_detail.user_detail.user_details')->where('contract_id', $pest_contract->id)->whereIn('status', ['Complete', 'Cancelled']);

            $jobs = $this->findCompletedServicesForContract($pest_contract->id);

            if ($request->status) {
                // return 11;
                $jobs->whereIn('pocomos_jobs.status', $request->status);
            }

            if ($request->search) {

                $search = '%'.$request->search.'%';

                $formatDate = date('Y/m/d', strtotime($request->search));
                $date = '%'.str_replace("/","-",$formatDate).'%';

                $jobs->where(function ($query) use ($search, $date) {
                    $query->where('invoice_id', 'like',  $search)
                        ->orWhere('pocomos_jobs.date_completed', 'like',  $date)
                        ->orWhere('pocomos_jobs.date_cancelled', 'like',  $date)
                        ->orWhere('pocomos_jobs.status', 'like',  $search)
                        ->orWhere('pocomos_jobs.type', 'like',  $search)
                        ->orWhere('amount_due', 'like',  $search)
                        ->orWhere('pi.balance', 'like',  $search)
                        ->orWhere(DB::raw("CONCAT(ou.first_name, ' ', ou.last_name)"), 'LIKE', $search)
                        ;
                });
            }

            /**For pagination */
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $jobs_count = $jobs->count();
            $jobs->skip($perPage * ($page - 1))->take($perPage);

            $jobs = $jobs->get();

            $data = [
                "jobs_data" => $jobs,
                "count" => $jobs_count
            ];
        } else {
            $jobs = [];
        }

        // misc invoices
        $pest_contract_invoice = PocomosPestContractsInvoice::where('pest_contract_id', $pest_contract->id)->pluck('invoice_id')->toArray();

        $invoices = PocomosInvoice::whereIn('id', $pest_contract_invoice);

        if ($request->search_misc) {
            $search = $request->search_misc;
            $invoices->where(function ($query) use ($search) {
                $query->where('id', 'like', '%' . $search . '%')
                    ->orWhere('date_due', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('amount_due', 'like', '%' . $search . '%')
                    ->orWhere('balance', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $invoices_count = $invoices->count();
        $invoices->skip($perPage * ($page - 1))->take($perPage);

        $invoices = $invoices->get();

        $invoices_data = [
            "invoices_data" => $invoices,
            "count" => $invoices_count
        ];

        $balance = PocomosCustomerState::where('customer_id', $request->customer_id)->select('balance_overall', 'balance_outstanding', 'balance_credit')->first();

        $lifetimeRevenue = $this->getLifetimeRevenue($pest_contract);

        $data = [
            'balance' => $balance,
            // 'contract' => $contract,
            //'entity' => $customer,
            'entities' => $data,
            // 'invoices' => $invoices_data,
            'lifetimeRevenue' => $lifetimeRevenue,
            // 'opinionsetting' => $opinionsetting,
        ];

        return $this->sendResponse(true, 'List of services', $data);
    }

    public function getCustomerServiceHistorySummaryAction()
    {
        $PocomosCustomer = PocomosCustomer::find($_GET['customer_id']);

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $_GET['customer_id'])->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to locate Customer Sales Profile.');
        }

        $pestContract = PocomosPestContract::find($_GET['contract_id']);

        if (!$pestContract) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Contract']));
        }

        $contract = PocomosContract::find($pestContract->contract_id);

        $serviceContract = $billingContract = $contract->pest_contract_details;
        $office = $profile->office_details;
        $invoiceConfig = PocomosPestInvoiceSetting::where('office_id', $office->id)->firstOrFail();

        if ($invoiceConfig->use_legacy_layout) {
            // Use the old generator
            $params = array(
                'office' => $office,
                'contract' => $contract->pest_contract_details,
                'paid' => $_GET['type'],
            );

            // return $this->legacyServiceHistorySummaryGenerator($params);
        }

        if ($_GET['type'] == 'paid') {
            $contract_id = $_GET['contract_id'];
            $invoices = DB::select(DB::raw("SELECT pc.id, pc.date_due, pc.amount_due, pc.status, pc.balance,cspa.name,cso.date_completed
        FROM pocomos_invoices AS pc
        JOIN pocomos_jobs AS cso ON cso.invoice_id = pc.id
        JOIN pocomos_contracts AS csp ON csp.id = pc.contract_id
        JOIN pocomos_agreements AS cspa ON cspa.id = csp.agreement_id
        JOIN pocomos_customer_sales_profiles AS cspp ON cspp.id = csp.profile_id
        WHERE cspp.id  = '$profile->id' AND pc.status  IN ('Paid','Paid')  AND pc.contract_id  = '$pestContract->contract_id'  AND cso.contract_id   = '$contract_id'  ORDER BY pc.date_due DESC"));
        } elseif ($_GET['type'] == 'unpaid') {
            $contract_id = $_GET['contract_id'];
            $invoices = DB::select(DB::raw("SELECT pc.id, pc.date_due, pc.amount_due, pc.status, pc.balance,cspa.name,cso.date_completed
        FROM pocomos_invoices AS pc
        JOIN pocomos_jobs AS cso ON cso.invoice_id = pc.id
        JOIN pocomos_contracts AS csp ON csp.id = pc.contract_id
        JOIN pocomos_agreements AS cspa ON cspa.id = csp.agreement_id
        JOIN pocomos_customer_sales_profiles AS cspp ON cspp.id = csp.profile_id
        WHERE cspp.id  = '$profile->id' AND pc.status NOT IN ('Paid','Cancelled','Not sent')  AND pc.contract_id  = '$pestContract->contract_id'  AND cso.contract_id   = '$contract_id'  ORDER BY pc.date_due DESC"));
        }

        $serviceCustomer = $billingCustomer = $profile->customer_details;
        $lifetimeRevenue = $this->getLifetimeRevenue($pestContract);
        $customerState = PocomosCustomerState::where('customer_id', $_GET['customer_id'])->firstOrFail();
        $outstandingBalance = $customerState->balance_outstanding ?? 00;

        $agreement = $contract->agreement_details;

        $publicPaymentLink = '';

        $invoiceIntro = $this->renderDynamicTemplate($agreement->invoice_intro, null, $serviceCustomer, $serviceContract, null, true);

        $parameters = array(
            'serviceCustomer' => $serviceCustomer,
            'billingCustomer' => $billingCustomer,
            'invoices' => $invoices,
            'lifetime_revenue' => $lifetimeRevenue,
            'office' => $office,
            'invoiceConfig' => $invoiceConfig,
            'paid_type' =>  $_GET['type'],
            'outstanding' => $outstandingBalance,
            'contract' => $contract,
            'invoiceIntro' => $invoiceIntro,
            'portalLink' => $publicPaymentLink
        );

        $pdf = PDF::loadView('pdf.BillingSummary.index', compact('parameters'));

        return $pdf->download('paid_service_history_summaries' . $_GET['customer_id'] . '.pdf');
    }

    public function getCustomerServiceHistorySummaryActionImproved()
    {
        $customer = $this->findOneByIdAndOffice_customerRepo($_GET['customer_id'], $_GET['office_id']);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find Customer.']));
        }

        $pestContract = $this->findOneByIdAndCustomerPCCRepository($_GET['contract_id'], $_GET['customer_id']);

        if (!$pestContract) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Contract.']));
        }

        $hash = md5(date('Y-m-d H:i:s P'));

        $args = array(
            'pcc_ids' => $pestContract->id,
            'paid' => $_GET['type'],
            // 'returnUrl' => $returnUrl,
            'hash' => $hash,
            'alertReceivingUsers' => auth()->user()->pocomos_company_office_user->id,
        );

        ServiceHistorySummaryJob::dispatch($args);

        return $this->sendResponse(true, 'The server is processing your request. You will receive an alert when it is completed.');

        // return $pdf->download('paid_service_history_summaries' . $_GET['customer_id'] . '.pdf');
    }

    /**
     * API for paid Service Hisroy Summary
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paidServiceHistorySummaryStart(Request $request)
    {
        $exported_columns = $request->exported_columns ?? array();

        PaidServiceHistorySummary::dispatch($exported_columns);

        return $this->sendResponse(true, 'The server is processing your request. You will receive an alert when it is completed.');
    }


    /* Hard-schedules a job * customer_service_remove_hard_schedule */

    public function removeHardScheduleAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'job_id' => 'required|exists:pocomos_jobs,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $job = PocomosJob::where('id', $request->job_id)->first();

        if (!$job) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Job']));
        }

        $slot = PocomosRouteSlots::where('id', $job->slot_id)->first();

        if (!$slot) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Slot']));
        }

        $status = $slot->schedule_type;

        if ($status == "Confirmed" || $status == "Hard-scheduled") {
            $slot->schedule_type = config('constants.CONFIRMED');
        } else {
            $slot->schedule_type =  config('constants.DYNAMIC');
        }

        $slot->save();

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Removed']), $slot);
    }

    /* Hard-schedules a job * customer_service_update_hard_schedule */

    public function updateHardScheduleAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'job_id' => 'required|exists:pocomos_jobs,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $job = PocomosJob::where('id', $request->job_id)->first();

        if (!$job) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Job']));
        }

        $routeQb =  PocomosRoute::query();

        $routeQb = $routeQb->where('date_scheduled', $job->date_scheduled)->where('office_id', $request->office_id)->where('active', true);

        $slot = PocomosRouteSlots::where('id', $job->slot_id)->first();

        if (!$slot) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Slot']));
        }

        $route = PocomosRoute::where('id', $slot->route_id)->first();

        $preferredTech = PocomosPestContract::where('id', $job->contract_id)->select('technician_id')->first();

        $routes = array();

        if ($preferredTech['technician_id'] !== null && (!$route || ($route->technician_id) === null)) {
            $route = null;
            $routeQb = $routeQb->where('technician_id', $preferredTech['technician_id']);
            $routeQb_count = $routeQb->count();
            $routes = $routeQb->get();
        } elseif (!$preferredTech['technician_id'] && !$route) {
            $routeQb_count = $routeQb->count();
            $routes = $routeQb->get();
        }

        if (count($routes) > 0) {
            foreach ($routes as $possibleRoute) {
                try {
                    $result = $this->rescheduleJobReschedulingHelper($job, $job->date_scheduled, $slot ? $slot->time_begin : null, $possibleRoute);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        if (!$route) {
            $route = $this->createRoute_routeFactory($request->office_id, $job->date_scheduled, $preferredTech['technician_id'], 'No idea');
        }

        try {
            $result = $this->rescheduleJobReschedulingHelper($job, $job->date_scheduled, $slot ? $slot->time_begin : null, $route);
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Hard Schedule Job']), $slot);
    }

    /**
     * Displays a form to edit a Job entity.
     * @param Request $request
     */
    public function findRoutesByDay(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'date' => 'required|date'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $date = $request->date;

        $office = PocomosCompanyOffice::find($officeId);
        
        $schedule = $this->getEffectiveSchedule($office, $date);
        $repRoutes = $this->findByDateScheduled($date, $office);

        $config = PocomosPestOfficeSetting::whereOfficeId($officeId)->first();

        $slots = array(
            '' => $this->getStandardTimeSlots($officeId, $date),
        );
        $routes = array();

        $i = 0;
        foreach ($repRoutes as $route) {
            $route = PocomosRoute::find($route->id);
            $slots[$route->id] = $this->getAvailableTimeSlots($route, $config->regular_duration);
            $name = ($route->technician_detail ? ($route->technician_detail->user_detail ? $route->technician_detail->user_detail->user_details_name->first_name : '') : '');
            $routes[$route->id] = $route->name .' '. $name;
            $i = $i + 1;
        }

        return $this->sendResponse(true, __('strings.details', ['name' => 'Routes by day']), array(
            'routes' => $routes,
            'slots' => $slots,
            'open' => $schedule->open,
        ));
    }
}
