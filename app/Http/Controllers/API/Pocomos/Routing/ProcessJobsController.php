<?php

namespace App\Http\Controllers\API\Pocomos\Routing;

use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosJob;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosRoute;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosRouteSlots;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosOpiniionLog;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosSmsFormLetter;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosTeamRouteAssignment;
use App\Models\Pocomos\PocomosPestpacExportCustomer;

class ProcessJobsController extends Controller
{
    use Functions;

    public function get(Request $request)
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

        $date = $request->date;

        $route = PocomosRoute::select('*', 'pocomos_routes.id')
            ->leftJoin('pocomos_technicians as pt', 'pocomos_routes.technician_id', 'pt.id')
            ->leftJoin('pocomos_company_office_users as pcou', 'pt.user_id', 'pcou.id')
            ->leftJoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->where('pocomos_routes.date_scheduled', $date)
            ->where('pocomos_routes.office_id', $officeId)
            ->get();

        return $this->sendResponse(true, 'Technician and route', $route);
    }


    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'route_id' => 'required',
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $slots = PocomosRouteSlots::has('job_detail')->with('job_detail.invoice_detail')
                ->whereRouteId($request->route_id)->get();
        $slotsArr = $slots->toArray();
        // return $slots[0]['job_detail'];

        $summary['total']['count'] = 0;
        $summary['total']['value'] = 0.00;

        $slotIds = $slots->pluck('id');

        $cspIds = array_filter(array_map(function ($slot) use (&$summary) {
            $summary[str_replace(['-',' '], '_', $slot['job_detail']['type'])]['value'] = 0;
            $summary[str_replace(['-',' '], '_', $slot['job_detail']['type'])]['count'] = 0;

            if (str_replace(['-',' '], '_', $slot['job_detail']['type'])) {
                $summary[str_replace(['-',' '], '_', $slot['job_detail']['type'])]['count']++;
                $summary[str_replace(['-',' '], '_', $slot['job_detail']['type'])]['value'] += $slot['job_detail']['invoice_detail']['amount_due'];
            }
            $summary['total']['count']++;
            $summary['total']['value'] += $slot['job_detail']['invoice_detail']['amount_due'];
        }, $slotsArr));

        $query = PocomosRoute::select(
            '*',
            'pocomos_routes.date_scheduled as route_date_scheduled',
            'pi.balance as invoice_balance',
            'prs.time_begin',
            'pj.id as job_id',
            'pj.type as job_type',
            'pj.status as job_status',
            'ppcst.name as service_type',
            'pi.status as invoice_status',
            'prs.type as slot_type',
            'prs.id as slot_id'
        )
        ->join('pocomos_route_slots as prs', 'pocomos_routes.id', 'prs.route_id')
        ->leftJoin('pocomos_jobs as pj', 'prs.id', 'pj.slot_id')
        ->leftJoin('pocomos_pest_contracts as ppc', 'pj.contract_id', 'ppc.id')
        ->leftJoin('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
        ->leftJoin('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id')

        ->leftJoin('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
        ->leftJoin('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
        ->leftJoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
        ->leftJoin('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
        ->leftJoin('pocomos_invoices as pi', 'pj.invoice_id', 'pi.id')
        ->where('pocomos_routes.id', $request->route_id);

        $slotsQuery = (clone($query))->whereNotNull('pj.slot_id');

        // return $query;

        if ($request->search) {
            $slotsQuery->where(function ($query) use ($request) {
                // dd($request->search);
                $formatDate = date('Y/m/d', strtotime($request->search));
                $date = str_replace("/", "-", $formatDate);
                // dd($search);

                $query->where('pocomos_routes.date_scheduled', 'like', '%'.$date.'%')
                    ->orWhere('pocomos_routes.date_scheduled', 'like', '%' . $request->search . '%')
                    ->orWhere('prs.time_begin', 'like', '%' . $request->search . '%')
                    ->orWhere('pcu.first_name', 'like', '%' . $request->search . '%')
                    ->orWhere('pcu.last_name', 'like', '%' . $request->search . '%')
                    ->orWhere('pcu.email', 'like', '%' . $request->search . '%')
                    ->orWhere('ppn.number', 'like', '%' . $request->search . '%')
                    ->orWhere('pa.street', 'like', '%'.$request->search.'%')
                    ->orWhere('pa.suite', 'like', '%'.$request->search.'%')
                    ->orWhere('pa.city', 'like', '%'.$request->search.'%')
                    ->orWhere('pa.postal_code', 'like', '%'.$request->search.'%')
                    ->orWhere('pj.type', 'like', '%' . $request->search . '%')
                    ->orWhere('pj.status', 'like', '%' . $request->search . '%')
                    ->orWhere('pi.status', 'like', '%' . $request->search . '%');
            });
        }

        // to get all ids if user selects all
        if ($request->all_ids) {
            // $jobs = collect();

            $q = $slotsQuery->whereNotIn('pj.status', ['Complete', 'Cancelled']);
            $jobIds = $q->pluck('job_id');
            $custIds = $q->pluck('customer_id');

            $i=0;
            foreach($jobIds as $jId){
                // foreach($custIds as $cId){
                    $allIds[$i]['jobId'] = $jId;
                    $allIds[$i]['custId'] = $custIds[$i];
                // }
                $i++;
            }
        } else {
            $jobs = $slotsQuery->get()->makeHidden('agreement_body');

            // for blocks, reserved and lunch break
            $blocksQuery = (clone($query))->where(function ($q) {
                $q->where('prs.type', 'Lunch')
                    ->orWhere('prs.type', 'Blocked')
                    ->orWhere('prs.type', 'Reserved');
            });

            if ($request->search) {
                $blocksQuery->where(function ($query) use ($request) {
                    // dd($request->search);
                    $formatDate = date('Y/m/d', strtotime($request->search));
                    $date = str_replace("/", "-", $formatDate);
                    // dd($search);
    
                    $query->where('pocomos_routes.date_scheduled', 'like', '%'.$date.'%')
                        ->orWhere('pocomos_routes.date_scheduled', 'like', '%' . $request->search . '%')
                        ->orWhere('prs.time_begin', 'like', '%' . $request->search . '%')
                        ->orWhere('prs.type', 'like', '%' . $request->search . '%');
                });
            }

            $blocks = $blocksQuery->get()->makeHidden('agreement_body');

            $assignmentsQuery = PocomosTeamRouteAssignment::select('*', 'pocomos_teams_route_assignments.id')
                        ->join('pocomos_routes as pr', 'pocomos_teams_route_assignments.route_id', 'pr.id')
                        ->join('pocomos_teams as pt', 'pocomos_teams_route_assignments.team_id', 'pt.id')
                        ->where('pr.id', $request->route_id)
                        ->where('pr.office_id', $officeId)
                        ->where('pocomos_teams_route_assignments.active', 1);

            if ($request->search) {
                $assignmentsQuery->where(function ($q) use ($request) {
                    // dd($request->search);
                    $formatDate = date('Y/m/d', strtotime($request->search));
                    $date = str_replace("/", "-", $formatDate);
                    // dd($search);
    
                    $q->where('pr.date_scheduled', 'like', '%'.$date.'%')
                        ->orWhere('pr.date_scheduled', 'like', '%' . $request->search . '%')
                        ->orWhere('pocomos_teams_route_assignments.time_begin', 'like', '%' . $request->search . '%')
                        ->orWhere('pt.name', 'like', '%' . $request->search . '%');
                });
            }
            $assignments = $assignmentsQuery->get();

            $merged = $jobs->concat($blocks)->concat($assignments)->sortBy('time_begin');

            $values = $merged->values();

            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $count = $values->count();
            $final = $values->skip($perPage * ($page - 1))->take($perPage)->values();
        }

        // $jobs = $values->get()->makeHidden('agreement_body');

        return $this->sendResponse(true, 'summary and scheduled jobs', [
            'summary' => $summary,
            'jobs' => $final ?? [],
            // 'blocks' => $blocks,
            // 'route_team_assignments' => $assignments,
            'count' => $count ?? null,
            'all_ids' => $allIds ?? [],
        ]);

        /*
        //for service type colom
        //service type, job type, job_status-invoice_status

        //for schedule jobs
        // > date= route date scheduled and type reason
        */
    }

    public function updateCommission(Request $request, $jobId)
    {
        $v = validator($request->all(), [
            'commission_type' => 'required|in:None,Flat,Rate',
            'commission_value' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $jobs =PocomosJob::whereId($jobId)->update([
            'commission_type' => $request->commission_type,
            'commission_value' => $request->commission_value,
            'commission_edited' => 1
        ]);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Commission settings']));
    }

    public function updateTimeSlotLabel(Request $request, $jobId)
    {
        $v = validator($request->all(), [
            'type_reason' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $job =PocomosJob::with('slot')->whereId($jobId)->first();

        if ($job) {
            $slotId =$job->slot_id;
        }

        if (!$job || !$slotId) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate Job']));
        }

        $job->slot->update(['type_reason'=> $request->type_reason]);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Time slot label']));
    }

    public function getTechnicians(Request $request)
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

        $technicians = PocomosTechnician::select('*', 'pocomos_technicians.id as technician_id')
                ->join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
                ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->where('pcou.office_id', $officeId)
                ->where('pocomos_technicians.active', 1)
                ->get();

        return $this->sendResponse(true, 'Technicians', $technicians);
    }

    public function completeJobs(Request $request)
    {
        $v = validator($request->all(), [
            'job_ids' => 'required',
            'date_complete' => 'required',
            'technician' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $opinionLog = PocomosOpiniionLog::query();

        foreach ($request->job_ids as $jobId) {
            $job = PocomosJob::findOrFail($jobId);
            $job->date_completed = $request->date_complete;
            $job->technician_id = $request->technician;
            $job->status = 'Complete';
            $job->save();

            if ($job->invoice_id) {
                PocomosInvoice::whereId($job->invoice_id)->update([
                    'date_due' => date('Y-m-d')
                ]);
            }

            /*
            //get customer by job
            $customer = PocomosJob::select('pcsp.customer_id')
                ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
                ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_company_offices as pco', 'pcsp.office_id', 'pco.id')
                ->where('pocomos_jobs.id', $jobId)
                ->first();

            (clone($opinionLog))->create([

            ]);*/
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Completed jobs']));
    }

    public function exportToPestpac(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'job_ids' => 'required',
        ]);

        $officeId = $request->office_id;
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        //get customers by jobs
        $customerIds = PocomosJob::select('pcsp.customer_id')
                ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
                ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_company_offices as pco', 'pcsp.office_id', 'pco.id')
                ->whereIn('pocomos_jobs.id', $request->job_ids)
                ->get()->pluck('customer_id')->unique();

        // return $customerIds;

        foreach ($customerIds as $custId) {
            //get contacts by customer
            $pestContractIds = PocomosPestContract::select('pocomos_pest_contracts.id')
                ->join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
                ->where('pcu.id', $custId)
                ->get()->pluck('id')->unique();

            // return $pestContractIds;

            foreach ($pestContractIds as $contractId) {
                $ppExportCust = PocomosPestpacExportCustomer::firstOrCreate(
                    [   'pest_contract_id' => $contractId   ],
                    [
                        'customer_id' => $custId,
                        'office_id' =>  $officeId,
                        'status' =>  'Pending'
                    ]
                );
            }
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Customer added to export queue.']));
    }

    public function sendMails(Request $request)
    {
        $v = validator($request->all(), [
            'letter' => 'required|exists:pocomos_form_letters,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'job_ids' => 'nullable',
            'subject' => 'nullable',
            'message' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // PocomosCustomer::findOrFail($request->customer_id);

        $letter = $request->letter;
        $jobIds = $request->job_ids;

        if ($jobIds) {
            // dd(88);
            $result = $this->sendFormLetterFromJobIds($jobIds, $letter, $request->office_id);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Letters sent']));
    }

    public function sendSmss(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            // 'pest_contract' => 'required',
            'job_ids' => 'nullable',
            'invoices' => 'nullable',
            'form_letter' => 'nullable',
            'message' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;
        $letterId = $request->form_letter ?? null;

        $officeConfig = PocomosOfficeSetting::whereOfficeId($officeId)->firstorfail();

        if (!$officeConfig->sender_phone_id) {
            return $this->sendResponse(false, 'Please talk to your account manager to setup SMS.');
        }

        // $customer = PocomosCustomer::with('contact_address','sales_profile')->where('id', $custId)->first();

        // if (!$customer) {
        //     return $this->sendResponse(false, 'Unable to find the Customer.');
        // }

        $jobIds = $request->job_ids;

        if ($letterId) {
            $letter = PocomosSmsFormLetter::whereActive(true)->whereOfficeId($officeId)->findOrFail($letterId);
        } else {
            $message = $request->message;
            $letter = new PocomosSmsFormLetter();
            $letter->office_id = $officeId;
            $letter->category = 0;
            $letter->title = '';
            $letter->message = $message ?? '';
            $letter->description = '';
            $letter->confirm_job = 1;
            $letter->require_job = false;
            $letter->active = true;
            $letter->save();
        }

        if ($jobIds) {
            // dd(11);
            $sentCount = $this->sendSmsFormLetterFromJobIds($jobIds, $letter);
        }
        return $this->sendResponse(true, 'SMS form letter(s) sent');
        /*
        get job id,cust id from routes/jobs key
         */
    }

    public function sendSmsFormLetterFromJobIds($jobIds, $letter)
    {
        // dd($jobIds);
        $jobs = PocomosJob::with('contract', 'route_detail')
            ->select('*', 'pcsp.customer_id', 'pocomos_jobs.contract_id')
            ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_agreements as pa', 'pc.agreement_id', 'pa.id')
            ->where('pcsp.office_id', $letter->office_id)
            ->whereIn('pocomos_jobs.id', $jobIds)
            ->get();

        foreach ($jobs as $job) {
            $customerId = $job->customer_id;
            $contractId = $job->contract_id;
            // $customer = $job->getContract()->getContract()->getProfile()->getCustomer();

            $customer = PocomosCustomer::with('contact_address', 'sales_profile')->findOrFail($customerId);
            $pestContract = PocomosPestContract::with('service_type_details')->find($contractId);

            $sentCount = $this->sendSmsFormLetter($letter, $customer, $pestContract, $job);
            // $result->meta->count += $sentCount;

            if ($job && $letter->confirm_job == 1 && $sentCount > 0) {
                $this->confirmJob($job);
            }
        }

        return $sentCount;
    }
}
