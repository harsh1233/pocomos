<?php

namespace App\Http\Controllers\API\Pocomos\Routing;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosTag;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosOfficeOpiniionSetting;
use App\Models\Pocomos\PocomosDocusendConfiguration;
use App\Models\Pocomos\PocomosFormLetter;
use App\Models\Pocomos\PocomosRoute;
use App\Jobs\BulkCardChargeJob;
use App\Models\Pocomos\PocomosCustomerNote;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosRouteSlots;
use App\Models\Orkestra\OrkestraUser;
use DB;
use App\Exports\ExportReminderReport;
use Excel;

class ReminderController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $serviceTypes = PocomosService::whereOfficeId($officeId)->whereActive(1)->get();

        $technicians = PocomosTechnician::select('*', 'pocomos_technicians.id')
                ->join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
                ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->where('pcou.office_id', $officeId)
                ->where('pocomos_technicians.active', 1)
                ->get();

        $tags = PocomosTag::whereOfficeId($officeId)->whereActive(1)->orderBy('name')->get();

        return $this->sendResponse(true, 'Reminder filters', [
            'technicians'   => $technicians,
            'tags'   => $tags,
            'service_types'   => $serviceTypes,
        ]);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $opinionSetting = PocomosOfficeOpiniionSetting::whereOfficeId($officeId)->whereActive(1)->first();

        $PocomosDocusendConfiguration = PocomosDocusendConfiguration::whereOfficeId($officeId)->first();

        $docusendEnabled = $PocomosDocusendConfiguration ? true : false;

        $letters = PocomosFormLetter::whereOfficeId($officeId)->whereActive(1)->get();

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $jobs = PocomosJob::select(
            '*',
            'pocomos_jobs.id',
            'pocomos_jobs.slot_id',
            'pocomos_jobs.date_scheduled as job_date_scheduled',
            // 'pocomos_jobs.technician_id',
            'pocomos_jobs.type as job_type',
            'pi.status as invoice_status',
            'pi.balance as invoice_balance',
            'pt.name as tag_name',
            'pag.name as agreement_name',
            'ppcst.name as service_type',
            'prs.schedule_type',
            'pcsp.autopay',
            'pcu.first_name',
            'pcu.last_name',
            'ou.first_name as tech_fname',
            'ou.last_name as tech_lname',
            'pr.technician_id',
            'prs.time_begin',
            'prs.id as qqq',
        )
        ->join('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
        ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
        ->leftJoin('pocomos_pest_contracts_tags as ppct', 'ppc.id', 'ppct.contract_id')
        ->leftJoin('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id')
        ->leftJoin('pocomos_tags as pt', 'ppct.tag_id', 'pt.id')
        ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
        ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
        ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
        ->leftJoin('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
        ->leftJoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
        ->leftJoin('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
        ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
        ->leftJoin('pocomos_route_slots as prs', 'pocomos_jobs.slot_id', 'prs.id')
        ->leftJoin('pocomos_routes as pr', 'prs.route_id', 'pr.id')

        //added
        ->leftJoin('pocomos_technicians as ptr', 'pr.technician_id', 'ptr.id')
        ->leftJoin('pocomos_company_office_users as pcou', 'ptr.user_id', 'pcou.id')
        ->leftJoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')

        ->where('pag.office_id', $officeId)
        ->where('pocomos_jobs.date_completed', null)
        ->whereBetween('pocomos_jobs.date_scheduled', [$startDate, $endDate])
        ->where('pcu.status', 'active')
        ->whereIn('pocomos_jobs.status', ['pending','Re-scheduled'])
        ->orderBy('pocomos_jobs.date_scheduled', 'ASC')
        ->orderBy('pocomos_jobs.time_scheduled', 'ASC')
        ->orderBy('prs.time_begin', 'ASC');

        // ->get();

        if ($request->confirmed === 1) {
            $jobs->whereIn('prs.schedule_type', ['Confirmed','Hard-scheduled, Confirmed', 'Hard-scheduled']);
        } elseif ($request->confirmed === 0) {
            $jobs->whereNotIn('prs.schedule_type', ['Confirmed','Hard-scheduled, Confirmed', 'Hard-scheduled']);
        }

        if ($request->service_types) {
            $jobs->where('ppc.service_type_id', $request->service_types);
        }

        if ($request->service_frequency) {
            $jobs->where('ppc.service_frequency', $request->service_frequency);
        }

        if ($request->job_type) {
            $jobs->where('pocomos_jobs.type', $request->job_type);
        }

        if ($request->postal_code) {
            $jobs->where('pa.postal_code', $request->postal_code);
        }

        if ($request->technician) {
            // return 11;
            $jobs->where('pr.technician_id', $request->technician);
        }

        if ($request->email_filter == 'verified') {
            $jobs->where('pcu.email_verified', 1);
        }

        if ($request->search_terms) {
            $searchTerms = $request->search_terms;

            $jobs->where(function ($query) use ($searchTerms) {
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
            
            $jobs->where(function ($j) use ($request) {

                $search = '%'.$request->search.'%';
                // dd($request->search);
                $formatDate = date('Y/m/d', strtotime($search));
                $date = str_replace("/", "-", $formatDate);
                // dd($search);

                /* $uids = OrkestraUser::where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'LIKE', $search)
                        ->pluck('id');

                $ouIds = PocomosCompanyOfficeUser::whereIn('user_id', $uids)->pluck('id');

                $techIds = PocomosTechnician::whereIn('user_id', $ouIds)->pluck('id'); */

                $j->where('pocomos_jobs.date_scheduled', 'like', '%'.$date.'%')
                    ->orWhere('pocomos_jobs.date_scheduled', 'like', $search)
                    // ->orWhere('pcu.first_name', 'like', $search)
                    // ->orWhere('pcu.last_name', 'like', $search)
                    ->orWhere(DB::raw("CONCAT(pcu.first_name, ' ', pcu.last_name)"), 'LIKE', $search)
                    ->orWhere(DB::raw("CONCAT(ou.first_name, ' ', ou.last_name)"), 'LIKE', $search)
                    ->orWhere('ppn.number', 'like', $search)
                    ->orWhere('ppcst.name', 'like', $search)
                    ->orWhere('pocomos_jobs.type', 'like', $search);

                if (stripos('unassigned', $request->search)  !== false) {
                    $j->orwhere('pr.technician_id', null);
                }

            });
        }

        // to get all ids if user selects all
        if ($request->all_ids) {
            $jobIds = $jobs->pluck('id');
            $invoiceIds = $jobs->pluck('invoice_id');
            $custIds = $jobs->pluck('customer_id');

            $i=0;
            foreach($jobIds as $jId){
                foreach($invoiceIds as $invId){
                    foreach($custIds as $cId){
                    $allIds[$i]['jobId'] = $jId;
                    $allIds[$i]['invoiceId'] = $invId;
                    $allIds[$i]['custId'] = $cId;
                    }
                }
                $i++;
            }
            $jobs = [];

            // return $allIds;


        } else {
            /**For pagination */
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $count = $jobs->count();
            $jobs->skip($perPage * ($page - 1))->take($perPage);

            $jobs = $jobs->get()->makeHidden('agreement_body')->toArray();
        }

        if ($request->tags) {
            $filterTags = $request->tags;

            // return $results;

            $jobs = array_filter($jobs, function ($job) use ($filterTags) {
                $tags = PocomosJob::with('contract.contract_tags')->whereId($job['id'])->get()
                            ->pluck('contract.contract_tags.*.tag_id');

                foreach ($tags[0] as $tag) {
                    if (in_array($tag, $filterTags)) {
                        return true;
                    }
                }
                return false;
            });

            $jobs = array_values($jobs);
        }

        if ($request->not_tags) {
            $notTags = $request->not_tags;

            $jobs = array_filter($jobs, function ($job) use ($notTags) {
                $tags = PocomosJob::with('contract.contract_tags')->whereId($job['id'])->get()
                            ->pluck('contract.contract_tags.*.tag_id');

                $intersection = array_intersect($tags[0], $notTags);

                return empty($intersection);
            });

            $jobs = array_values($jobs);
        }

        if ($request->download) {
            return Excel::download(new ExportReminderReport($jobs), 'ExportReminderReport.csv');
        }

        $jobsBalance = collect($jobs)->sum('invoice_balance');

        return $this->sendResponse(true, 'List of reminders', [
            'jobs' => $jobs ?? [],
            'expected_revenue' => $jobsBalance,
            'count' => $count ?? null,
            'all_ids' => $allIds ?? [],
            // 'all_invoice_ids' => $invoiceIds ?? [],
            // 'all_customer_ids' => $custIds ?? [],
        ]);

        //date = job_date_scheduled
        //name, number and email from customers
        //account balance = balance_overall
        //name = frst and last name
        //email =email
        //ph. no= number


        //outstanding amount=  total of balance
        //billing note =summary

        //for service type colom
        //service type, job type, technician

        /* for more 
        customer name and id
        map code= map_code
        Job Balance = invoice_balance
        street, suit, city, postal code
        contract = agreement name
        service type = service_type, job type, invoice status
        */
        //confirmed button = schedule_type
    }

    public function availableRoutes(Request $request)
    {
        $v = validator($request->all(), [
            'date_scheduled' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $routes = PocomosRoute::has('office_detail')
                    ->with('technician_detail.user_detail.user_details')
                    ->whereDateScheduled($request->date_scheduled)
                    ->whereOfficeId($request->office_id)
                    ->whereActive(1)
                    ->get();

        return $this->sendResponse(true, 'Routes', $routes);
    }

    public function availableTimeSlotsAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'date' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if ($request->route_id) {
            $route = PocomosRoute::has('office_detail')
                        ->with(['technician_detail.user_detail.user_details','office_detail'])
                        ->whereId($request->route_id)
                        ->whereOfficeId($request->office_id)
                        ->whereActive(1)
                        ->first();

            $availableTimeSlots = $this->getAvailableTimeSlots($route);
        } else {
            $availableTimeSlots = $this->getStandardTimeSlots($request->office_id, $request->date);
        }

        return $this->sendResponse(true, 'Time Slots', $availableTimeSlots ?? []);
    }


    //rescheduleJobReschedulingHelper
    public function rescheduleJob(Request $request, $jobId)
    {
        // return serialize('j');
        $v = validator($request->all(), [
            // 'job_id' => 'required',
            'date_scheduled' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $slot = PocomosRouteSlots::query();
        $job =PocomosJob::with('invoice_detail')->whereId($jobId)->firstOrFail();
        $route =PocomosRoute::find($request->assign_to_route);

        $options['date_scheduled'] = $request->date_scheduled;
        $options['offset_future_jobs'] = $request->offset_future_jobs;
        $options['reschedule_future_jobs'] = $request->reschedule_future_jobs;
        $options['route'] = $route;
        $options['anytime'] = $request->anytime;

        if ($request->slot) {
            // $this->rescheduleJobWithBestFitOptions($options, $slot);
        } else {
            // return 11;

            // if specific time selected
            if ($request->time_scheduled) {
                $options['time_scheduled'] = $request->time_scheduled;
            } else {
                $options['time_scheduled'] = $job->time_scheduled;
            }
            $this->rescheduleJobWithOptionsImproved($options, $job);
        }
        // $this->rescheduleJobReschedulingHelper($job, $request->date_scheduled);

        /*
        $job->date_scheduled = $request->date_scheduled;
        $job->original_date_scheduled = $request->date_scheduled;
        $job->time_scheduled = null;
        $job->status = 'Re-Scheduled';

        // (clone($slot))->whereId($job->slot_id)->delete();

        $getslot = (clone($slot))->whereId($job->slot_id)->first();
// return $getslot;
        if($getslot){
            $getslot->time_begin = $request->anytime == 1 ? '00:00:00' : $getslot->time_begin;
            $getslot->route_id = $request->assign_to_route;
            $getslot->save();
        }

        // (clone($slot))->whereId($job->slot_id)->update([
        //     'time_begin' => $request->anytime == 1 ? '00:00:00' : $getslot->time_begin,
        //     'route_id' => $request->assign_to_route
        // ]);

        if($job->invoice_detail){
            $job->invoice_detail->update([
                'date_due' => $request->date_scheduled
            ]);
        }

        $job->save();

        $this->sendRescheduleJobEmail($job);
        */


        /* // for activity

        $job = PocomosJob::with([
            'contract.contract_details.profile_details.customer',
            'invoice_detail'])->whereId($jobId)->firstOrFail();

        // dd($job->contract->contract_details->profile_id);

        $profileId = 'null';
        if (isset($job->contract->contract_details->profile_id)) {
            $profileId = $job->contract->contract_details->profile_id;
        }

        if(isset($job->contract->contract_details->profile_details->customer)){
            $customer = $job->contract->contract_details->profile_details->customer;
        }

        if(isset($job->invoice_detail)){
            $invoice = $job->invoice_detail;
        }

        $fromTime = $job->date_scheduled;

        if($job->time_scheduled != null)
        {
            $fromTime = $fromTime.' at '. $job->time_scheduled;
            $toTime = $job->date_scheduled;
            $toTime = $toTime.' at '. $job->time_scheduled;
        }
        else{
            $fromTime = $fromTime.' at anytime';
            $toTime = $fromTime.' at anytime';
        }

        $desc = '';
        if(auth()->user()){
            $desc .= "<a href='/pocomos-admin/app/employees/users/".auth()->user()->id."/show'>" . auth()->user()->full_name . "</a> ";
        } else {
            $desc .= 'The system ';
        }

        $desc .= 'rescheduled a job for';

        if(isset($customer)) {
            $desc .= " customer <a href='/pocomos-admin/app/Customers/".$customer->id."/service-information'>" . $customer->first_name ." ". $customer->last_name . "</a> ";
        }
        if (isset($fromTime) && !empty($fromTime)) {
            $desc .= ' to '.$fromTime;
        }
        if (isset($invoice) && !empty($invoice)) {
            $desc .= " with invoice <a href='/pocomos-admin/app/Customers/".$customer->id."/invoice/".$invoice->id."/show'> ".$invoice->id." </a> ";
        }
        $desc .= '.';

        $sql = 'INSERT INTO pocomos_activity_logs
                    (type, office_user_id, customer_sales_profile_id, description, context, date_created)
                    VALUES("Job Rescheduled", '.auth()->user()->pocomos_company_office_user->id.',
                        '.$profileId.', "", "", "'.date('Y-m-d H:i:s').'")';

        DB::select(DB::raw($sql)); */

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Jobs rescheduled']));
    }

    public function jobNote(Request $request, $jobId)
    {
        $job = PocomosJob::with('slot')->whereId($jobId)->firstOrFail();

        return $this->sendResponse(true, __('strings.details', ['name' => 'Note']), $job);
    }

    public function updateNote(Request $request, $jobId)
    {
        $v = validator($request->all(), [
            // 'service_id' => 'required',
            'note' => 'required'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $job = PocomosJob::with('slot')->whereId($jobId)->firstOrFail();
        $job->note = $request->note;
        $job->save();

        if ($request->duplicate_note) {
            // $note_detail['user_id'] = $or_user->id ?? null;
            $note['summary'] = $request->note;
            $note['interaction_type'] = 'Other';
            $note['active'] = true;
            $note['body'] = '';
            $noteData = PocomosNote::create($note);

            // return $noteData;

            // PocomosCustomerNote::create([
            //     'customer_id' => $request->customer_id,
            //     'note_id' => $noteData->id,
            // ]);
        }

        if ($request->always_display_note) {
            // $workOrderNote['customer_id'] = $request->customer_id;
            // $workOrderNote['note_id'] = $noteData->id;
            // $find_old_work_order = PocomosCustomersWorkorderNote::where('customer_id', $request->customer_id)->where('note_id', $noteData->id)->first();
            // if (!$find_old_work_order) {
            //     $add_work_order = PocomosCustomersWorkorderNote::create($workOrderNote);
            // }
        }

        // return $job;

        // $request->hard_schedule =1,0
        if (isset($request->hard_schedule) && $job->slot) {
            // return 88;
            // $hardSchedule = $form->get('hardSchedule')->getData();
            // $helper = $this->get('pocomos.pest.helper.job_rescheduling');

            $route = PocomosRoute::find($job->slot->route_id);

            if ($request->hard_schedule == 1) {
                // dd(11);
                $result = $this->rescheduleJobReschedulingHelper($job, $job->date_scheduled, $job->slot->time_begin, $route);
            } else {
                // return 8;
                $slot = $this->removeHardScheduled($job->slot);
            }
        }

        return $this->sendResponse(true, 'Note edited successfully');
    }


    public function bulkCharge(Request $request)
    {
        $v = validator($request->all(), [
            'job_ids' => 'nullable',
            'invoice_ids' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $invoiceIds = $request->invoice_ids ?: [];

        if ($request->job_ids) {
            $jobs = PocomosJob::join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
                    ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
                    ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                    ->join('pocomos_company_offices as pco', 'pcsp.office_id', 'pco.id')
                    ->whereIn('pocomos_jobs.id', $request->job_ids)
                    ->get();

            foreach ($jobs as $job) {
                $invoiceIds[] =  $job->invoice_id;
            }
        }


        // return $invoiceIds;

        BulkCardChargeJob::dispatch($invoiceIds);

        return $this->sendResponse(true, 'The server is processing these transactions, you will be notified when it completes.');
    }


    public function confirmJobs(Request $request)
    {
        $v = validator($request->all(), [
            'job_ids' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $jobs =PocomosJob::with('slot')->whereIn('id', $request->job_ids)->get();

        foreach ($jobs as $job) {
            $this->confirmJob($job);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Jobs confirmed']));
    }


    //rescheduleJobReschedulingHelper
    public function rescheduleJobs(Request $request)
    {
        $v = validator($request->all(), [
            'job_ids' => 'required',
            'date_scheduled' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $jobs =PocomosJob::with('invoice_detail')->whereIn('id', $request->job_ids)
                                ->whereNotIn('status', ['Complete','Cancelled'])->get();

        $slot = PocomosRouteSlots::query();

        foreach ($jobs as $job) {
            /* $job->date_scheduled = $request->date_scheduled;
            $job->original_date_scheduled = $request->date_scheduled;
            $job->time_scheduled = null;
            $job->status = 'Rescheduled';

            (clone($slot))->whereId($job->slot_id)->delete();

            $job->invoice_detail->update([
                'date_due' => $request->date_scheduled
            ]);

            $job->save(); */

            $this->rescheduleJobReschedulingHelper($job, $request->date_scheduled);
        }

        /* $profileId = 'null';
        if(isset($customer->sales_profile)){
            $profileId = $customer->sales_profile->id;
        }

        $sql = 'INSERT INTO pocomos_activity_logs
                    (type, office_user_id, customer_sales_profile_id, description, context, date_created)
                    VALUES("Job Rescheduled", '.auth()->user()->pocomos_company_office_user->id.',
                        '.$profileId.', "", "", "'.date('Y-m-d H:i:s').'")';

        DB::select(DB::raw($sql)); */

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Jobs rescheduled']));
    }

    public function cancelJobs(Request $request)
    {
        $v = validator($request->all(), [
            'job_ids' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $jobs = PocomosJob::with(['invoice','contract.contract_details.profile_details.customer'])
                    ->whereIn('id', $request->job_ids)->get();

        foreach ($jobs as $job) {
            $invoice = $job->invoice;
            $jobs = $this->cancelJob($job, $invoice);

            $profileId = 'null';
            if (isset($job->contract->contract_details->profile_id)) {
                $profileId = $job->contract->contract_details->profile_id;
            }

            if (isset($job->contract->contract_details->profile_details->customer)) {
                $customer = $job->contract->contract_details->profile_details->customer;
            }

            if (isset($job->invoice_detail)) {
                $invoice = $job->invoice_detail;
            }

            $desc = '';
            if (auth()->user()) {
                $desc .= "<a href='/pocomos-admin/app/employees/users/".auth()->user()->id."/show'>" . auth()->user()->full_name . "</a> ";
            } else {
                $desc .= 'The system ';
            }

            $desc .= 'cancelled a job for';

            if (isset($customer)) {
                $desc .= " customer <a href='/pocomos-admin/app/Customers/".$customer->id."/service-information'>" .$customer->first_name ." ". $customer->last_name."</a>.";
            }

            if (isset($invoice) && !empty($invoice)) {
                $desc .= " with invoice <a href='/pocomos-admin/app/Customers/".$customer->id."/invoice/".$invoice->id."/show'> ".$invoice->id." </a> ";
            }

            $desc .= '.';

            $sql = 'INSERT INTO pocomos_activity_logs
                        (type, office_user_id, customer_sales_profile_id, description, context, date_created)
                        VALUES("Job Cancelled", ' . auth()->user()->pocomos_company_office_user->id . ',
                            ' . $profileId . ', "", "", "' . date('Y-m-d H:i:s') . '")';

            DB::select(DB::raw($sql));
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Jobs cancelled']));
    }


    public function addNoteForCustomers(Request $request)
    {
        $v = validator($request->all(), [
            'job_ids' => 'required',
            'note' => 'required'
        ]);

        $officeId = $request->office_id;
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customers = PocomosCustomer::select('pocomos_customers.id as id')->join('pocomos_customer_sales_profiles as pcsp', 'pocomos_customers.id', 'pcsp.customer_id')
            ->join('pocomos_company_offices as pco', 'pcsp.office_id', 'pco.id')
            ->join('pocomos_contracts as pc', 'pcsp.id', 'pc.profile_id')
            ->join('pocomos_pest_contracts as ppc', 'pc.id', 'ppc.contract_id')
            ->join('pocomos_jobs as pj', 'ppc.id', 'pj.contract_id')
            ->where('pco.id', $officeId)
            ->whereIn('pj.id', $request->job_ids)
            ->get();

        // return $customers;

        $note = PocomosNote::query();
        $customerNote = PocomosCustomerNote::query();

        foreach ($customers as $customer) {
            $createdNote = (clone($note))->create([
                'summary' => $request->note,
                'interaction_type' => 'Other',
                'body' => '',
                'active' => 1,
            ]);

            // return $customer->id;

            (clone($customerNote))->create([
                'customer_id' => $customer->id,
                'note_id' => $createdNote->id,
            ]);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Added note']));
    }
}
