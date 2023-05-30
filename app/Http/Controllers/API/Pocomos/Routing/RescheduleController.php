<?php

namespace App\Http\Controllers\API\Pocomos\Routing;

use DB;
use Excel;
use Illuminate\Http\Request;
use App\Exports\ExportReschedule;
use App\Models\Pocomos\PocomosJob;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosTechnician;

use App\Models\Pocomos\PocomosCreditHoldSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class RescheduleController extends Controller
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

        $technicians = PocomosTechnician::select('*', 'pocomos_technicians.id')
                ->join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
                ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->where('pocomos_technicians.active', 1)
                ->where('pcou.active', 1)
                ->where('ou.active', 1)
                ->where('pcou.office_id', $officeId)
                ->get();

        return $this->sendResponse(true, 'Reschedule filters', [
            'technicians'   => $technicians,
            'service_types'   => $serviceTypes,
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

        // $opinionSetting = PocomosOfficeOpiniionSetting::whereOfficeId($officeId)->whereActive(1)->first();

        // $PocomosDocusendConfiguration = PocomosDocusendConfiguration::whereOfficeId($officeId)->first();

        // $docusendEnabled = $PocomosDocusendConfiguration ? true : false;

        // $letters = PocomosFormLetter::whereOfficeId($officeId)->whereActive(1)->get();

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $query = PocomosJob::select(
                    '*',
                    'pocomos_jobs.id',
                    'pocomos_jobs.date_scheduled as job_date_scheduled',
                    'pocomos_jobs.technician_id',
                    'pocomos_jobs.type as job_type',
                    'pi.status as invoice_status',
                    'pi.balance as invoice_balance',
                    'pt.name as tag_name',
                    'pag.name as agreement_name',
                    'ou.first_name as tech_fname',
                    'ou.last_name as tech_lname',
                    'pcu.first_name',
                    'pcu.last_name',
                    'pcu.email',
                    'ppcst.name as service_type'
                )
        ->join('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
        ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
        ->leftJoin('pocomos_pest_contracts_tags as ppct', 'ppc.id', 'ppct.contract_id')
        ->leftJoin('pocomos_tags as pt', 'ppct.tag_id', 'pt.id')
        ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
        ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
        ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
        ->leftjoin('pocomos_sub_customers as psc', 'pcu.id', 'psc.parent_id')
        ->leftJoin('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
        ->leftJoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
        ->leftJoin('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
        ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
        ->leftJoin('pocomos_route_slots as prs', 'pocomos_jobs.slot_id', 'prs.id')
        ->leftJoin('pocomos_routes as pr', 'prs.route_id', 'pr.id')
        
        // added
        ->leftJoin('pocomos_technicians as ptr', 'pr.technician_id', 'ptr.id')
        ->leftJoin('pocomos_company_office_users as pcou', 'ptr.user_id', 'pcou.id')
        ->leftJoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
        ->leftjoin('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id')

        ->where('pag.office_id', $officeId)
        ->whereBetween('pocomos_jobs.date_scheduled', [$startDate, $endDate])
        ->where('pcu.status', 'Active')
        ->whereIn('pocomos_jobs.status', ['Pending','Re-scheduled'])
        ->orderBy('pocomos_jobs.date_scheduled', 'ASC')
        ->orderBy('pocomos_jobs.time_scheduled', 'ASC')
        ->orderBy('prs.time_begin', 'ASC');

        if ($request->paid !== null) {
            $operator = $request->paid == false ? '!=' : '=';
            $query->where('pi.status', $operator, 'Paid');
        }

        if ($request->service_types) {
            $query->where('ppc.service_type_id', $request->service_types);
        }

        if ($request->service_frequency) {
            $query->where('ppc.service_frequency', $request->service_frequency);
        }

        if ($request->job_type) {
            $query->where('pocomos_jobs.type', $request->job_type);
        }

        if ($request->postal_code) {
            $query->where('pa.postal_code', $request->postal_code);
        }

        if ($request->technician) {
            $query->where('pr.technician_id', $request->technician);
        }

        if ($request->search_terms) {
            $search = '%' . $request->search_terms . '%';

            $query->where(function ($query) use ($search) {
                $query->where(DB::raw("CONCAT(pcu.first_name, ' ', pcu.last_name)"), 'LIKE', $search)
                    ->orWhere('pcu.email', 'like', $search)
                    ->orWhere('pa.street', 'like', $search)
                    ->orWhere('pa.suite', 'like', $search)
                    ->orWhere('pa.city', 'like', $search)
                    ->orWhere('pag.name', 'like', $search);
            });
        }

        $summaryByType = $query->get()->countBy('job_type');

        $summary = [];

        $q=0;
        foreach ($summaryByType as $k=>$v) {
            $summary[$q]['job_type'] = $k;
            $summary[$q]['count'] = $v;
            $q++;
        }

        // to get all ids if user selects all
        if ($request->all_ids) {
            $jobIds = $query->pluck('id');
            $invoiceIds = $query->pluck('invoice_id');
            $custIds = $query->pluck('customer_id');

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
        } else {
            /**For pagination */
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $count = $query->count();
            $query = $query->skip($perPage * ($page - 1))->take($perPage);
            $jobs = $query->get()->makeHidden('agreement_body');
        }


        // $jobsCount = $jobs->count();

        if ($request->download) {
            return Excel::download(new ExportReschedule($jobs), 'ExportReschedule.csv');
        }

        return $this->sendResponse(true, 'Reminder', [
            'summary' => $summary,
            'jobs' => $jobs ?? [],
            'count' => $count ?? null,
            // 'total_jobs_count' => $jobsCount,
            'all_ids' => $allIds ?? [],
        ]);

        //date = job_date_scheduled
        //name, number and email from customers
        //balance = balance_overall
        //name = frst and last name
        //email =email
        //ph. no= number


        //outstanding amount=  total of balance
        //billing note =summary

        //for service type colom
        //service type, job type, technician (tech_fname or with)

        /** for more */
        //customer name and id
        //map code= map_code
        //Job Balance = invoice_balance
        //street, suit, city, postal code
        //contract = agreement name
        //service type = service_type, job type, job status -invoice status
    }

    public function checkCreditStatus(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::whereId($custId)->first();

        $creditStatus = $customer->status == "Active" ? true : false;

        $creditHoldSetting = PocomosCreditHoldSetting::whereOfficeId($request->office_id)->first();

        if ($creditHoldSetting && $creditHoldSetting->on_hold== 1) {
            // return 99;
            $dueDays = $creditHoldSetting->due_days > 0 ? $creditHoldSetting->due_days : 365;

            $customer = PocomosCustomer::with('sales_profile.contract_details')->whereId($custId)->first();

            if ($contracts = $customer->sales_profile->contract_details) {
                foreach ($contracts as $contract) {
                    $sql = "SELECT *, DATEDIFF(CURDATE(), date_due) as diff_days FROM pocomos_invoices 
                        where status IN ('Due','Past due') AND contract_id=".$contract->id." having diff_days > ".$dueDays."";

                    $invoice = DB::select(DB::raw(($sql)));

                    if (empty($invoice)) {
                        $creditStatus = false;
                        break;
                    }
                }
            }
        }

        return $this->sendResponse(true, 'credit status', [
            'credit_status' => $creditStatus,
        ]);
    }
}
