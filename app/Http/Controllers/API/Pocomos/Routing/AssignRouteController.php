<?php

namespace App\Http\Controllers\API\Pocomos\Routing;

use DB;
use PDF;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosJob;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosTeam;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosRoute;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Support\Facades\Storage;
use App\Models\Pocomos\PocomosJobService;
use App\Models\Pocomos\PocomosRouteSlots;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosSmsFormLetter;
use App\Models\Pocomos\PocomosInvoicePayment;
use App\Models\Pocomos\PocomosCreditHoldSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosTeamRouteAssignment;
use App\Models\Pocomos\PocomosInvoiceInvoicePayment;
use App\Models\Pocomos\PocomosPestContractServiceType;

class AssignRouteController extends Controller
{
    use Functions;

    public function getAssignRouteFilters(Request $request)
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

        $technicians = PocomosTechnician::select('*', 'pocomos_technicians.id')
                ->join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
                ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->where('pcou.office_id', $officeId)
                ->where('pocomos_technicians.active', 1)
                ->get();

        $teams = PocomosTeam::whereOfficeId($officeId)->whereActive(true)->get();

        return $this->sendResponse(true, 'Assign route filters', [
            'technicians'   => $technicians,
            'teams'   => $teams,
        ]);
    }

    // for calender slots
    public function calendarSlotsQuery($officeId){
        return  PocomosRoute::select(
            '*',
            'pocomos_routes.id',
            'pocomos_routes.name',
            'pocomos_routes.locked',
            'pocomos_routes.technician_id',
            'pocomos_routes.date_scheduled',
            'pi.balance as invoice_balance',
            'prs.time_begin',
            'pj.slot_id as job_slot_id',
            'prs.id as slot_id',
            'prs.type as slot_type',
            'pj.status as job_status',
            'pj.id as job_id',
            'pj.color',
            'prs.color as slot_color',
            'ppc.technician_id as pref_tech_id',
            'ppc_pt_ou.first_name as tech_first_name',
            'ppc_pt_ou.last_name as tech_last_name',
            'pj.commission_type',
            'pj.commission_value',
            'pcu.first_name',
            'pcu.last_name',
            'slot_ou.first_name as slot_ou_fname',
            'slot_ou.last_name as slot_ou_lname',
            'ppcst.name as service_type',
            'ppcst.color as service_type_color',
            'ocr.name as region_name',
            'pj.time_begin as job_time_begin',
            'pj.time_end as job_time_end',
        )
            ->leftJoin('pocomos_route_slots as prs', 'pocomos_routes.id', 'prs.route_id')
            ->leftJoin('pocomos_jobs as pj', 'prs.id', 'pj.slot_id')
            ->leftJoin('pocomos_technicians as pt', 'pocomos_routes.technician_id', 'pt.id')
            ->leftJoin('pocomos_company_office_users as pcou', 'pt.user_id', 'pcou.id')
            ->leftJoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->leftJoin('pocomos_pest_contracts as ppc', 'pj.contract_id', 'ppc.id')
            ->leftJoin('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->leftJoin('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->leftJoin('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->leftJoin('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
            ->leftJoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->leftJoin('orkestra_countries_regions as ocr', 'pa.region_id', 'ocr.id')
            ->leftJoin('pocomos_invoices as pi', 'pj.invoice_id', 'pi.id')
            ->leftJoin('pocomos_invoice_items as pii', 'pi.id', 'pii.invoice_id')
            ->join('pocomos_company_offices as pco', 'pocomos_routes.office_id', 'pco.id')
            ->where('pco.id' , $officeId)

            //added
            ->leftJoin('pocomos_company_office_users as slot_pcou', 'prs.office_user_id', 'slot_pcou.id')
            ->leftJoin('orkestra_users as slot_ou', 'pcou.user_id', 'slot_ou.id')
            ->leftJoin('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id')
            ->leftJoin('pocomos_technicians as ppc_pt', 'ppc.technician_id', 'ppc_pt.id')
            ->leftjoin('pocomos_company_office_users as ppc_pt_pcou', 'ppc_pt.user_id', 'ppc_pt_pcou.id')
            ->leftjoin('orkestra_users as ppc_pt_ou', 'ppc_pt_pcou.user_id', 'ppc_pt_ou.id');
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

        $startDatePool = $request->start_date_pool;
        $endDatePool = $request->end_date_pool;

        $routesQuery = PocomosRoute::whereOfficeId($officeId)
                    ->whereBetween('date_scheduled', [$startDate, $endDate]);

        // all techs = null, prefrd tech = [], no tech = "no technician"
        if (is_array($request->technicians)) {
            $routesQuery->whereIn('technician_id', $request->technicians);
        } elseif ($request->technicians == 'no technician') {
            // return 88;
            $routesQuery->where('technician_id', null);
        }

        $routes = $routesQuery->get()->makeHidden('agreement_body');

        // for calendar slots
        $slotsQuery = $this->calendarSlotsQuery($officeId)
                    ->whereBetween('pocomos_routes.date_scheduled', [$startDate, $endDate]);

        // all techs = null, prefrd tech = [], no tech = "no technician"
        if (is_array($request->technicians)) {
            $slotsQuery->whereIn('pocomos_routes.technician_id', $request->technicians);
        } elseif ($request->technicians == 'no technician') {
            $slotsQuery->where('pocomos_routes.technician_id', null);
        }

        $slots = (clone($slotsQuery))->whereNotNull('pj.slot_id')->groupBy('pj.slot_id')
                        ->get()->makeHidden('agreement_body');

        // $routesQuery->groupBy('pj.slot_id');

        // for blocks, reserved and lunch break
        $blocks = (clone($slotsQuery))->where(function ($q) {
            $q->where('prs.type', 'Lunch')
                ->orWhere('prs.type', 'Blocked')
                ->orWhere('prs.type', 'Reserved');
        })->get()->makeHidden('agreement_body');

        
        // for pool jobs
        $poolJobsQuery = PocomosJob::select(
            '*',
            'pocomos_jobs.id',
            'pi.status as invoice_status',
            'pi.balance as invoice_balance',
            'pcu.first_name',
            'pcu.last_name',
            'ou.first_name as tech_first_name',
            'ou.last_name as tech_last_name',
            'ppcst.name as service_type',
            'ppcst.color as service_type_color',
            'ppc.service_frequency',
            'ous.first_name as salesp_first_name',
            'ous.last_name as salesp_last_name',
            'ppn.number',
            'ppan.number as alt_number',
            'pcu.email',
            'pocomos_jobs.color',
            'pocomos_jobs.status',
            'ppc.technician_id as pref_tech_id',
            'pocomos_jobs.commission_type',
            'pocomos_jobs.commission_value',
            'pocomos_jobs.date_scheduled',
            'pa.id as qqq'
        )
                ->join('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
                ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
                ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
                ->join('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
                ->leftjoin('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
                ->leftjoin('pocomos_phone_numbers as ppan', 'pa.alt_phone_id', 'ppan.id')
                ->leftJoin('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
                ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
                ->leftJoin('pocomos_route_slots as prs', 'pocomos_jobs.slot_id', 'prs.id')
                
                ->leftJoin('pocomos_technicians as pt', 'ppc.technician_id', 'pt.id')
                ->leftjoin('pocomos_company_office_users as pcou', 'pt.user_id', 'pcou.id')
                ->leftjoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->leftJoin('pocomos_salespeople as ps', 'pc.salesperson_id', 'ps.id')
                ->leftjoin('pocomos_company_office_users as pcous', 'ps.user_id', 'pcous.id')
                ->leftjoin('orkestra_users as ous', 'pcous.user_id', 'ous.id')
                ->leftJoin('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id')

                ->where('pag.office_id', $officeId)
                ->where('prs.id', null)
                ->whereBetween('pocomos_jobs.date_scheduled', [$startDatePool, $endDatePool])
                ->where('pcu.status', 'active')
                ->whereIn('pocomos_jobs.status', ['Pending','Re-scheduled'])
        ;

        if (is_array($request->technicians)) {
            $poolJobsQuery->whereIn('ppc.technician_id', $request->technicians);

            $poolJobsQuery->where(function ($q) use ($request) {
                $q->whereIn('ppc.technician_id', $request->technicians)
                    ->orWhereIn('pocomos_jobs.technician_id', $request->technicians);
            });
        } elseif ($request->technicians == 'no technician') {
            // $poolJobsQuery->where('ppc.technician_id', null);
        }

        if ($request->sort_pool_jobs == 'Name') {
            $poolJobsQuery->orderBy('pcu.first_name')->orderBy('pcu.last_name');
        } elseif ($request->sort_pool_jobs == 'Date Scheduled') {
            $poolJobsQuery->orderBy('pocomos_jobs.date_scheduled');
        } elseif ($request->sort_pool_jobs == 'Zip Code') {
            $poolJobsQuery->orderBy('pa.postal_code');
        }

        $jobs = $poolJobsQuery->get()->makeHidden('agreement_body');

        $assignments = PocomosTeamRouteAssignment::select('*', 'pocomos_teams_route_assignments.id')
                    ->join('pocomos_routes as pr', 'pocomos_teams_route_assignments.route_id', 'pr.id')
                    ->join('pocomos_teams as pt', 'pocomos_teams_route_assignments.team_id', 'pt.id')
                    ->whereBetween('pr.date_scheduled', [$startDate, $endDate])
                    ->where('pr.office_id', $officeId)
                    ->where('pocomos_teams_route_assignments.active', 1)
                    ->get();

        return $this->sendResponse(true, __('strings.details', ['name' => 'Calender routes and pool jobs']), [
            'jobs' => $jobs,
            'routes' => $routes,
            'slots' => $slots,
            'blocks' => $blocks,
            'route_team_assignments' => $assignments,
        ]);
        /*
        for pool jobs:
        job color for background line
        if balance_outstanding >0 then show red line/font
        job price = invoice balance/invoice total amount due
        balance = customer balance overall

        for routes:
        Estimated Revenue = total of all job price

        for slots:

        for slots, check job_slot_id in routes key, If job_slot_id exist then it's a slot

        for reserved:
        reserved by = slot_ou_fname

        job price = invoice balance
        Comppleted = job status:complete
        Last serviced = by = "technician" and date = job_detail > date_completed
        for blocked slots check if slot_type=blocked
        get assigned sales team by comparing route id, time begin, duration of slots and team_assignments

        */
    }


    public function routeList(Request $request)
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

        $startDate = $request->calender_start_date;
        $endDate = $request->calender_end_date;

        $routes = PocomosRoute::whereBetween('date_scheduled', [$startDate, $endDate])->with('technician_detail.user_detail.user_details')
                ->whereOfficeId($officeId)->get();
        $routeIds = $routes->pluck('id');
        $routeSlots = PocomosRouteSlots::whereIn('route_id', $routeIds)->get();

        // show date_scheduled for header

        return $this->sendResponse(true, __('strings.list', ['name' => 'Route slots']), [
            'routes' => $routes,
            'route_slots' => $routeSlots,
        ]);
    }

    /*

    */

    /*

    public function unassignRouteSlot(Request $request, $id){
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if(!$pocomosCompanyOffice){
            return $this->sendResponse(false, 'Company Office not found.');
        }

        PocomosRouteSlots::whereId($id)->delete();

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Deleted route slot']));
    }

    */

    public function editRouteSlot(Request $request, $id)
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

        $routeSlot = PocomosRouteSlots::findOrFail($id);
        if ($request->type_reason || $request->type_reason=='') {
            $routeSlot->type_reason = $request->type_reason ?: 'BLOCKED';
        } else {
            $routeSlot->color = $request->color;
        }
        $routeSlot->save();

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Edited route slot']));
    }


    public function searchCustomer(Request $request)
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

        $sql = 'SELECT pc.id, pc.first_name, pc.last_name, ppn.number AS phone, pc.email, pca.postal_code, pc.status, pcs.last_service_date, pcs.next_service_date, pc.company_name

            FROM `pocomos_customer_sales_profiles` pcsp
            JOIN pocomos_customers pc ON pcsp.customer_id = pc.id
            JOIN pocomos_addresses pca ON pc.contact_address_id = pca.id
            JOIN pocomos_addresses pba ON pc.billing_address_id = pba.id
            LEFT JOIN pocomos_customer_state pcs ON pc.id = pcs.customer_id
            LEFT JOIN pocomos_phone_numbers ppn ON pca.phone_id = ppn.id
            LEFT JOIN pocomos_phone_numbers ppan ON pca.phone_id = ppan.id
            where pcsp.office_id = '.$officeId.'
            ';

        // $searchTerm = '%'.$request->search_term.'%';
        $searchTerm = "'%".$request->search_term."%'";
        $equalsSearchTerm = "'".$request->search_term."'";


        $sql .= ' AND (pc.first_name LIKE '.$searchTerm.'
            OR pc.company_name LIKE '.$searchTerm.'
            OR pc.last_name LIKE '.$searchTerm.'
            OR pc.email LIKE '.$searchTerm.'
            OR CONCAT(pca.street,\' \',pca.suite, \' \',pca.city,\' \',pca.postal_code) LIKE '.$searchTerm.'
            OR CONCAT(pba.street,\' \',pba.suite, \' \',pba.city,\' \',pba.postal_code) LIKE '.$searchTerm.'
            OR CONCAT(pc.first_name, \' \', pc.last_name) LIKE '.$searchTerm.'
            OR pc.external_account_id = '.$equalsSearchTerm.' )';

        /*
        $explodedSearchTerm = explode(' ', $searchTerm);
        foreach ($explodedSearchTerm as $term) {
            $phoneNumber = preg_replace('/[^0-9]/', '', $term);
            if (strlen($phoneNumber) > 5) {
                $phoneNumber = '%'.$phoneNumber.'%';
                $dql .= ' OR pnp.number LIKE '.$phoneNumber.'
                OR ppan.number LIKE '.$phoneNumber.' ';
                break;
            }
        }*/

        $sql .= ' ORDER BY pc.date_created DESC';

        $entities = DB::select(DB::raw($sql));


        foreach ($entities as $entity) {
            $entity = (array)$entity;

            $data[$entity['id']] = $entity['first_name'] . ' ' . $entity['last_name'];

            if (!empty($entity['company_name'])) {
                $data[$entity['id']] .=  $entity['company_name'] ;
            }
            // if (!($entity['status'] === 'Active')) {
                //     $data[$entity['id']] .=  $entity['status'] ;
            // }
        }


        return $this->sendResponse(true, __('strings.list', ['name' => 'Customers']), $entities);
    }


    public function getCustomerContracts(Request $request, $customer_id)
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

        $agreements = PocomosCustomer::with('sales_profile.contract_details.agreement_details')
                    ->whereId($customer_id)->get();

        return $this->sendResponse(true, __('strings.list', ['name' => 'Conracts']), $agreements);
    }



    public function contractWiseJobs(Request $request, $contractId)
    {
        $v = validator($request->all(), [
            'office_id'         => 'required',
            'customer_id'       => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pestContract = PocomosPestContract::select('*', 'pocomos_pest_contracts.id')
                ->join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
                ->where('pocomos_pest_contracts.contract_id', $contractId)
                ->first();

        $pestContractId = isset($pestContract) ? $pestContract->id : null;

        // return $pestContractId;

        $jobs = PocomosJob::select(
            '*',
            'pocomos_jobs.id',
            'pocomos_jobs.date_scheduled',
            'pocomos_jobs.time_scheduled'
        )
                ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
                ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
                ->join('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
                ->leftJoin('pocomos_route_slots as prs', 'pocomos_jobs.slot_id', 'prs.id')
                ->where('pocomos_jobs.contract_id', $pestContractId)
                ->whereIn('pocomos_jobs.status', ['Pending', 'Re-scheduled'])
                ->orderBy('pocomos_jobs.date_scheduled')
                ->get();

        // return $jobs;

        $creditHoldSetting = PocomosCreditHoldSetting::whereOfficeId($request->office_id)->first();

        $creditStatus = true;
        if ($creditHoldSetting && $creditHoldSetting->on_hold== 1) {
            // return 99;
            $dueDays = ($creditHoldSetting->due_days > 0) ? $creditHoldSetting->due_days : 365;

            $customer = PocomosCustomer::with('sales_profile.contract_details')->whereId($request->customer_id)->first();

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

        return $this->sendResponse(true, 'Contract wise jobs', [
            'jobs' => $jobs,
            'credit_status' => $creditStatus,
        ]);
        //job id, date scheduled and time scheduled
    }

    public function createContractJob(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id',
            'route_date_scheduled' => 'required',
            'job_type' => 'required',
            'price' => 'required',
            'job_note' => 'nullable',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // check contract id first
        $contract = PocomosContract::where('id', $request->contract_id)->first();

        $pestContract = PocomosPestContract::where('contract_id', $request->contract_id)->firstorfail();

        $taxCode = PocomosTaxCode::where('id', $contract->tax_code_id)->first();

        // create invoice
        $invoice_input = [];
        $invoice_input['contract_id'] = $request->contract_id;
        $invoice_input['date_due'] = $request->route_date_scheduled;
        $invoice_input['amount_due'] = $request->price;
        $invoice_input['status'] = 'Not sent';
        $invoice_input['balance'] = $request->price;
        $invoice_input['sales_tax'] = $taxCode->tax_rate;
        $invoice_input['tax_code_id'] = $taxCode->id;
        $invoice_input['active'] = 1;
        $invoice_input['closed'] = 0;
        $invoice = PocomosInvoice::create($invoice_input);

        // Add entry into invoice items table
        $invoice_item = [];
        $invoice_item['invoice_id'] = $invoice->id;
        $invoice_item['description'] = $request->job_type . ' Service';
        $invoice_item['price'] = $request->price;
        $invoice_item['active'] = 1;
        $invoice_item['sales_tax'] = $taxCode->tax_rate;
        $invoice_item['tax_code_id'] = $taxCode->id;
        $invoice_item['type'] = '';

        $invoice_item = PocomosInvoiceItems::create($invoice_item);

        $items['date_scheduled'] = date('Y-m-d');
        $items['amount_in_cents'] = $request->price*100;
        $items['status'] = "Unpaid";
        $items['active'] = true;
        $PocomosInvoicePayment = PocomosInvoicePayment::create($items);

        $itempayment['invoice_id'] = $invoice->id;
        $itempayment['payment_id'] = $PocomosInvoicePayment->id;
        $PocomosInvoiceInvoicePayment = PocomosInvoiceInvoicePayment::create($itempayment);

        $input = [];
        $input['contract_id'] = $pestContract->id;
        $input['invoice_id'] = $invoice->id;
        $input['date_scheduled'] = $request->route_date_scheduled;
        $input['type'] = $request->job_type;
        $input['status'] = 'Pending';
        $input['active'] = 1;
        $input['original_date_scheduled'] = $request->route_date_scheduled;
        $input['note'] = $request->job_note ?: '';
        $input['color'] = 'f9f9f9';
        $input['commission_type'] = 'None';
        $input['commission_value'] = 0;
        $input['commission_edited'] = 0;
        $input['technician_note'] = '';
        $input['weather'] = '';
        $input['treatmentNote'] = '';
        $createdJob = PocomosJob::create($input);

        $service = PocomosPestContractServiceType::where('office_id', $request->office_id)->where('active', '1')->first();
        $job_service = [];
        $job_service['service_type_id'] = $service->id;
        $job_service['job_id'] = $createdJob->id;
        $job_service['active'] = 1;
        $insert_job = PocomosJobService::create($job_service);

        return $this->sendResponse(true, 'The service has been created successfully.', $createdJob);
    }

    public function scheduledCustomerInfo(Request $request, $jobId)
    {
        $v = validator($request->all(), [
            'office_id'         => 'required',
            // 'customer_id'       => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $scheduledCustomer = PocomosJob::select(
            '*',
            'pocomos_jobs.id',
            'pocomos_jobs.date_scheduled',
            'pocomos_jobs.time_scheduled',
            'pocomos_jobs.status as job_status',
            'pi.status as invoice_status',
            'pcu.first_name',
            'pcu.last_name',
            'ou.first_name as tech_first_name',
            'ou.last_name as tech_last_name',
            'ppcst.name as service_type',
            'ppc.service_frequency',
            'ous.first_name as salesp_first_name',
            'ous.last_name as salesp_last_name',
            'pcu.id as customer_id'
        )
                ->leftjoin('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
                ->leftjoin('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
                ->leftjoin('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->leftjoin('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
                ->leftjoin('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
                ->leftJoin('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
                ->leftJoin('pocomos_route_slots as prs', 'pocomos_jobs.slot_id', 'prs.id')
                ->leftJoin('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
                ->leftJoin('pocomos_technicians as pt', 'ppc.technician_id', 'pt.id')
                ->leftjoin('pocomos_company_office_users as pcou', 'pt.user_id', 'pcou.id')
                ->leftjoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->leftJoin('pocomos_salespeople as ps', 'pc.salesperson_id', 'ps.id')
                ->leftjoin('pocomos_company_office_users as pcous', 'ps.user_id', 'pcous.id')
                ->leftjoin('orkestra_users as ous', 'pcous.user_id', 'ous.id')
                ->leftJoin('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id')
                ->leftJoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
                ->leftJoin('orkestra_countries_regions as ocr', 'pa.region_id', 'ocr.id')
                ->where('pcsp.office_id', $request->office_id)
                ->where('pocomos_jobs.id', $jobId)
                ->get()->makeHidden('agreement_body');

        return $this->sendResponse(true, 'Contract wise jobs', [
            'scheduled_customer' => $scheduledCustomer,
        ]);
    }


    public function printSummary(Request $request, $routeId)
    {
        // return strtotime('now');

        $v = validator($request->all(), [
            'office_id' => 'required',
            'slots' => 'array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $route = PocomosRoute::whereId($routeId)->with('technician_detail.user_detail.user_details')
                                    ->whereOfficeId($officeId)->first();

        $routeSlots = PocomosRouteSlots::whereRouteId($routeId)->get();

        $pdf = PDF::loadView('pdf.route_summary', ['route' => $route, 'routeSlots' => $routeSlots, 'slots'=>$request->slots]);

        $url =  "route_summary/" .$route->id . '_print_summary_'.strtotime("now") .'.pdf';

        Storage::disk('s3')->put($url, $pdf->output(), 'public');

        $path = Storage::disk('s3')->url($url);

        return $this->sendResponse(true, 'Route summary pdf' , $path);

        // return $pdf->download('route_summary' . $routeId . '.pdf');
    }

    public function printInvoice(Request $request, $routeId)
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

        $route = PocomosRoute::whereId($routeId)->with('technician_detail.user_detail.user_details')
                                ->whereOfficeId($officeId)->firstOrFail();

        $routeSlotIds = PocomosRouteSlots::whereRouteId($routeId)->pluck('id');

        $invoiceIds = PocomosJob::whereIn('slot_id', $routeSlotIds)->get()->pluck('invoice_id');

        // $invoiceIds = [
        //     1234
        // ];

        $pdf = $this->getMutipleInvoiceBasePdf($invoiceIds);

        $url =  "route_invoices/" .$routeId . '-route-invoices-'.strtotime("now") .'.pdf';

        Storage::disk('s3')->put($url, $pdf->output(), 'public');

        $path = Storage::disk('s3')->url($url);

        return $this->sendResponse(true, 'Route invoices pdf' , $path);
        // return $pdf->download('route_invoices_' . $routeId  .strtotime("now") .'.pdf');
    }

    public function rescheduleRoute(Request $request, $routeId)
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

        $route = PocomosRoute::findOrFail($routeId);
        $dateSchedueled = $request->date_scheduled ?: $route->date_scheduled;
        $route->date_scheduled = $dateSchedueled;
        $route->save();

        $routeSlotIds = PocomosRouteSlots::whereRouteId($routeId)->pluck('id');

        $jobs = PocomosJob::whereIn('slot_id', $routeSlotIds)->update([
            'date_scheduled' => $dateSchedueled
        ]);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Rescheduled route']));
    }


    /**
     * Updates all jobs on a route commission settings
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function modifyCommission(Request $request, $routeId)
    {
        $v = validator($request->all(), [
            'use_technician_setting' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if ($request->use_technician_setting) {
            $route = PocomosRoute::with('technician_detail')->findOrFail($routeId);
            $commissionType = isset($route->technician_detail->commission_type) ? $route->technician_detail->commission_type : null;
            $commissionValue = isset($route->technician_detail->commission_value) ? $route->technician_detail->commission_value : null;
        } else {
            $commissionType = $request->commission_type;
            $commissionValue = $request->commission_value;
        }

        $routeSlotIds = PocomosRouteSlots::whereRouteId($routeId)->pluck('id');

        if ($commissionType) {
            PocomosJob::whereIn('slot_id', $routeSlotIds)->update([
                'commission_type' => $commissionType,
                'commission_value' => $commissionValue,
                'commission_edited' => 1
            ]);
        }

        return $this->sendResponse(true, __('strings.update', ['name' => 'Commission settings']));
    }

    public function confirmJobs(Request $request, $routeId)
    {
        $routeSlotIds = PocomosRouteSlots::whereRouteId($routeId)->pluck('id');

        $jobs =PocomosJob::whereIn('slot_id', $routeSlotIds)->get();

        foreach ($jobs as $job) {
            // $this->confirmJob($job);

            if (!($slot = $job->route_detail)) {
                throw new \Exception(__('strings.message', ['message' => 'Job must be assigned to a route in order to be confirmed']));
            }

            if (config('constants.HARD') == $slot->schedule_type || config('constants.HARD_CONFIRMED') == $slot->schedule_type) {
                $slot->schedule_type = config('constants.HARD_CONFIRMED');
            } else {
                $slot->schedule_type = config('constants.CONFIRMED');
            }
            $slot->save();
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Jobs confirmed']));
    }

    public function editCustomerId(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $sql = 'SELECT *
            FROM  pocomos_customer_sales_profiles csp,
                pocomos_customers c
             -- JOIN c.contactAddress a
            WHERE csp.customer_id = '.$custId.'
            AND c.id = '.$custId.'
            AND csp.office_id = '.$officeId.'
            ';

        return DB::select(DB::raw($sql));
    }

    public function updateJobColor(Request $request, $jobId)
    {
        $v = validator($request->all(), [
            'color' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $slot = PocomosJob::findorfail($jobId);
        $slot->color = $request->color;
        $slot->save();

        return $this->sendResponse(true, __('strings.update', ['name' => 'Job color']));
    }


    public function saveChanges(Request $request)
    {
        if ($request->add_routes) {
            $i=0;
            foreach ($request->add_routes as $q) {
                $route = PocomosRoute::create([
                    'office_id' => $q['office_id'],
                    'name' => $q['name'],
                    'date_scheduled' => $q['date_scheduled'],
                    'active' => 1,
                    'locked' => $q['locked'],
                ]);

                if (isset($request->add_routes[$i]['move_slots'])) {
                    foreach ($request->add_routes[$i]['move_slots'] as $q) {
                        $slot = PocomosRouteSlots::findorfail($q['slot_id']);
                        $slot->route_id = $route->id;
                        $slot->time_begin = isset($q['time_begin']) ? $q['time_begin'] : $slot->time_begin;
                        $slot->duration = isset($q['duration']) ? $q['duration'] : $slot->duration;
                        $slot->schedule_type = isset($q['schedule_type']) ? $q['schedule_type'] : $slot->schedule_type;
                        $slot->anytime = isset($q['anytime']) ? $q['anytime'] : $slot->anytime;
                        $slot->save();

                        /* $slot = PocomosRouteSlots::whereId($q['slot_id'])->update([
                            'route_id' => $route->id,
                            'time_begin' => $q['time_begin'],
                            'duration' => $q['duration'],
                        ]); */

                        if (isset($q['job_id'])) {
                            $job = tap(PocomosJob::with([
                                'contract.contract_details.profile_details.customer',
                                'invoice_detail'])->whereId($q['job_id']))->update([
                                'date_scheduled' => $q['route_date_scheduled'],   //routes(key) > date_scheduled
                                'original_date_scheduled' => $q['route_date_scheduled'],
                                'time_scheduled' => $q['time_begin'],
                            ])->first();
                        }

                        $profileId = 'null';
                        if (isset($job->contract->contract_details->profile_id)) {
                            $profileId = $job->contract->contract_details->profile_id;
                        }

                        /* if(isset($job->contract->contract_details->profile_details->customer)){
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
                            $desc .= " with invoice <a href='/pocomos-admin/app/Customers/".$customer->id."/invoice/".$invoice->id."/show'> ".$find_invoice->id." </a> ";
                        }
                        $desc .= '.'; */

                        $sql = 'INSERT INTO pocomos_activity_logs 
                                    (type, office_user_id, customer_sales_profile_id, description, context, date_created) 
                                    VALUES("Job Rescheduled", '.auth()->user()->pocomos_company_office_user->id.', 
                                        '.$profileId.', "", "", "'.date('Y-m-d H:i:s').'")';

                        DB::select(DB::raw($sql));
                    }
                }

                if (isset($request->add_routes[$i]['schedule_customers'])) {
                    foreach ($request->add_routes[$i]['schedule_customers'] as $q) {
                        $slot = PocomosRouteSlots::create([
                            'route_id' => $route->id,   //routes (key from search api)> id
                            'time_begin' => $q['time_begin'],
                            'duration' => $q['duration'],
                            'type' => 'Regular',
                            'type_reason' => '',
                            'schedule_type' => 'Dynamic',
                            'anytime' => $q['anytime'] ?? 0,
                            'active' => 1,
                        ]);

                        $job = tap(PocomosJob::with([
                            'contract.contract_details.profile_details.customer',
                            'invoice_detail'])->whereId($q['job_id']))->update([
                            'slot_id'        => $slot->id,
                            'date_scheduled' => $q['route_date_scheduled'],   //routes (key) > date_scheduled
                            'original_date_scheduled' => $q['route_date_scheduled'],
                            'time_scheduled' => $q['time_begin'],
                        ])->first();


                        $profileId = 'null';
                        if (isset($job->contract->contract_details->profile_id)) {
                            $profileId = $job->contract->contract_details->profile_id;
                        }

                        /* if(isset($job->contract->contract_details->profile_details->customer)){
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

                        $desc .= 'scheduled a job for';

                        if(isset($customer)) {
                            $desc .= " customer <a href='/pocomos-admin/app/Customers/".$customer->id."/service-information'>" . $customer->first_name ." ". $customer->last_name . "</a> ";
                        }
                        if (isset($fromTime) && !empty($fromTime)) {
                            $desc .= ' to '.$fromTime;
                        }
                        if (isset($invoice) && !empty($invoice)) {
                            $desc .= " with invoice <a href='/pocomos-admin/app/Customers/".$customer->id."/invoice/".$invoice->id."/show'> ".$find_invoice->id." </a> ";
                        }
                        $desc .= '.'; */

                        $sql = 'INSERT INTO pocomos_activity_logs 
                                    (type, office_user_id, customer_sales_profile_id, description, context, date_created) 
                                    VALUES("Job Scheduled", '.auth()->user()->pocomos_company_office_user->id.', 
                                        '.$profileId.', "", "", "'.date('Y-m-d H:i:s').'")';

                        DB::select(DB::raw($sql));
                    }
                }

                if (isset($request->add_routes[$i]['block_times'])) {
                    // if($request->block_times){
                    foreach ($request->add_routes[$i]['block_times'] as $q) {
                        PocomosRouteSlots::create([
                            'route_id' => $route->id,
                            'time_begin' => $q['time_begin'],
                            'duration' => $q['duration'],
                            'type' => 'Blocked',
                            'type_reason' => 'Blocked',
                            'schedule_type' => 'Dynamic',
                            'anytime' => 0,
                            'active' => 1,
                        ]);
                    }
                }

                if (isset($request->add_routes[$i]['lunch_breaks'])) {
                    foreach ($request->add_routes[$i]['lunch_breaks'] as $q) {
                        PocomosRouteSlots::create([
                            'route_id' => $route->id,
                            'time_begin' => $q['time_begin'],
                            'duration' => $q['duration'],
                            'type' => 'Lunch',
                            'type_reason' => '',
                            'schedule_type' => 'Dynamic',
                            'anytime' => 0,
                            'active' => 1,
                        ]);
                    }

                    // $identifier = $slot->id;
                }

                $i++;
            }
        }

        if ($request->route_updates) {
            foreach ($request->route_updates as $q) {
                // return $q['route_id'];
                $route = PocomosRoute::findOrFail($q['route_id']);

                $route->locked = $q['locked'] ?? $route->locked;
                $route->name = $q['name'] ?? $route->name;
                $route->technician_id = $q['technician'] ?? null;

                $route->save();
            }
        }

        if ($request->remove_routes) {
            PocomosTeamRouteAssignment::whereIn('route_id', $request->remove_routes)->delete();

            PocomosRouteSlots::whereIn('route_id', $request->remove_routes)->delete();
            PocomosRoute::whereIn('id', $request->remove_routes)->delete();
        }

        //pass slot_id
        //get unassign_sales_teams by comparing route id, time begin, duration of slots and team_assignments
        //for unassign_slots + move calender jobs to pool (both are same)
        // to remove blocks, lunches also
        if ($request->unassign_slots) {
            // $routeIds = PocomosRouteSlots::whereIn('id', $request->unassign_slots)->pluck('route_id');
            // PocomosTeamRouteAssignment::whereIn('route_id', $routeIds)->delete();

            PocomosRouteSlots::whereIn('id', $request->unassign_slots)->delete();
        }

        //unassign_sales_teams
        //pass id (get from route_team_assignments key of search api)
        if ($request->unassign_sales_teams) {
            // $routeIds = PocomosRouteSlots::whereIn('id', $request->unassign_slots)->pluck('route_id');
            PocomosTeamRouteAssignment::whereIn('id', $request->unassign_sales_teams)->delete();

            // PocomosRouteSlots::whereIn('id', $request->unassign_slots)->delete();
        }


        if ($request->block_times) {
            foreach ($request->block_times as $q) {
                $slot = PocomosRouteSlots::create([
                     'route_id' => $q['route_id'],
                     'time_begin' => $q['time_begin'],
                     'duration' => $q['duration'],
                     'type' => 'Blocked',
                     'type_reason' => 'Blocked',
                     'schedule_type' => 'Dynamic',
                     'anytime' => 0,
                     'active' => 1,
                 ]);
            }

            $identifier = $slot->id;
        }

        if ($request->lunch_breaks) {
            foreach ($request->lunch_breaks as $q) {
                $slot = PocomosRouteSlots::updateOrCreate(
                    [
                     'route_id' => $q['route_id']
                ],
                    [
                         'time_begin' => $q['time_begin'],
                         'duration' => $q['duration'],
                         'type' => 'Lunch',
                         'type_reason' => '',
                         'schedule_type' => 'Dynamic',
                         'anytime' => 0,
                         'active' => 1,
                     ]
                );
            }

            $identifier = $slot->id;
        }

        // return $identifier;

        // get slot_id from routes key
        if ($request->update_slots) {
            foreach ($request->update_slots as $q) {
                $slot = PocomosRouteSlots::findorfail($q['slot_id']);
                $slot->route_id = isset($q['route_id']) ? $q['route_id'] : $slot->route_id;
                $slot->time_begin = isset($q['time_begin']) ? $q['time_begin'] : $slot->time_begin;
                $slot->duration = isset($q['duration']) ? $q['duration'] : $slot->duration;
                $slot->type_reason = isset($q['type_reason']) ? $q['type_reason'] : $slot->type_reason;
                $slot->color = isset($q['color']) ? $q['color'] : $slot->color;
                $slot->anytime = isset($q['anytime']) ? $q['anytime'] : $slot->anytime;
                $slot->save();
            }
        }


        //create slot, assign slot to job, add job time scheduled (same as slot time begin)
        //for schedule customer, move pool jobs to calender

        if ($request->schedule_customers) {
            foreach ($request->schedule_customers as $q) {
                $slot = PocomosRouteSlots::create([
                    'route_id' => $q['route_id'],   //routes (key from search api)> id
                    'time_begin' => $q['time_begin'],
                    'duration' => $q['duration'],
                    'type' => 'Regular',
                    'type_reason' => '',
                    'schedule_type' => 'Dynamic',
                    'anytime' => $q['anytime'] ?? 0,
                    'active' => 1,
                ]);

                $job = tap(PocomosJob::with([
                    'contract.contract_details.profile_details.customer',
                    'invoice_detail'])->whereId($q['job_id']))->update([
                    'slot_id'        => $slot->id,
                    'date_scheduled' => $q['route_date_scheduled'],   //routes (key) > date_scheduled
                    'original_date_scheduled' => $q['route_date_scheduled'],
                    'time_scheduled' => $q['time_begin'],
                ])->first();

                $profileId = 'null';
                if (isset($job->contract->contract_details->profile_id)) {
                    $profileId = $job->contract->contract_details->profile_id;
                }

                /* if(isset($job->contract->contract_details->profile_details->customer)){
                    $customer = $job->contract->contract_details->profile_details->customer;
                }

                if(isset($job->invoice_detail)){
                    $invoice = $job->invoice_detail;
                }

                $desc = '';
                if(auth()->user()){
                    $desc .= "<a href='/pocomos-admin/app/employees/users/".auth()->user()->id."/show'>" . auth()->user()->full_name . "</a> ";
                } else {
                    $desc .= 'The system ';
                }

                $desc .= 'scheduled a job for';

                if(isset($customer)) {
                    $desc .= " customer <a href='/pocomos-admin/app/Customers/".$customer->id."/service-information'>" . $customer->first_name ." ". $customer->last_name . "</a> ";
                }
                if (isset($fromTime) && !empty($fromTime)) {
                    $desc .= ' to '.$fromTime;
                }
                if (isset($invoice) && !empty($invoice)) {
                    $desc .= " with invoice <a href='/pocomos-admin/app/Customers/".$customer->id."/invoice/".$invoice->id."/show'> ".$find_invoice->id." </a> ";
                }
                $desc .= '.'; */

                $sql = 'INSERT INTO pocomos_activity_logs 
                            (type, office_user_id, customer_sales_profile_id, description, context, date_created) 
                            VALUES("Job Scheduled", '.auth()->user()->pocomos_company_office_user->id.', 
                                '.$profileId.', "", "", "'.date('Y-m-d H:i:s').'")';

                DB::select(DB::raw($sql));
            }

            $identifier = $slot->id;
        }

        //update job time scheduled
        // to remove the Confirmed or Hard-scheduled status also
        // move slots within calender (except assigned sales team), update slot duration,time, anytime also
        if ($request->move_slots) {
            foreach ($request->move_slots as $q) {
                $slot = PocomosRouteSlots::findorfail($q['slot_id']);
                $slot->route_id = isset($q['route_id']) ? $q['route_id'] : $slot->route_id;
                $slot->time_begin = isset($q['time_begin']) ? $q['time_begin'] : $slot->time_begin;
                $slot->duration = isset($q['duration']) ? $q['duration'] : $slot->duration;
                $slot->schedule_type = isset($q['schedule_type']) ? $q['schedule_type'] : $slot->schedule_type;
                $slot->anytime = isset($q['anytime']) ? $q['anytime'] : $slot->anytime;
                $slot->save();

                // $slot = PocomosRouteSlots::whereId($q['slot_id'])->update([
                //     'route_id' => $q['route_id'],
                //     'time_begin' => $q['time_begin'],
                //     'duration' => $q['duration'],
                //     'schedule_type' => $q['schedule_type'],
                // ]);

                if (isset($q['job_id'])) {
                    $job = tap(PocomosJob::with([
                        'contract.contract_details.profile_details.customer',
                        'invoice_detail'])->whereId($q['job_id']))->update([
                        'date_scheduled' => $q['route_date_scheduled'],   //routes(key) > date_scheduled
                        'original_date_scheduled' => $q['route_date_scheduled'],
                        'time_scheduled' => $q['time_begin'],
                    ])->first();
                }
                $profileId = 'null';
                if (isset($job->contract->contract_details->profile_id)) {
                    $profileId = $job->contract->contract_details->profile_id;
                }
                // return 44;

                /* if(isset($job->contract->contract_details->profile_details->customer)){
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
                $desc .= '.'; */

                $sql = 'INSERT INTO pocomos_activity_logs 
                            (type, office_user_id, customer_sales_profile_id, description, context, date_created) 
                            VALUES("Job Rescheduled", '.auth()->user()->pocomos_company_office_user->id.', 
                                '.$profileId.', "", "", "'.date('Y-m-d H:i:s').'")';

                DB::select(DB::raw($sql));
            }
        }

        if ($request->assign_sales_teams) {
            foreach ($request->assign_sales_teams as $q) {
                // $slot = PocomosRouteSlots::create([
                //             'route_id' => $q['route_id'],   //routes (key) > id
                //             'time_begin' => $q['time_begin'],
                //             'duration' => $q['duration'],
                //             'type' => 'Regular',
                //             'type_reason' => '',
                //             'schedule_type' => 'Dynamic',
                //             'anytime' => 0,
                //             'active' => 1,
                //         ]);

                $teamRouteAssignment = PocomosTeamRouteAssignment::create([
                    'route_id' => $q['route_id'],   //routes (key)> id
                    'team_id' => $q['team_id'],
                    'time_begin' => $q['time_begin'],
                    'duration' => $q['duration'],
                    'active' => 1,
                ]);
            }

            $identifier = $teamRouteAssignment->id;
        }

        if ($request->update_sales_teams) {
            foreach ($request->update_sales_teams as $q) {
                PocomosTeamRouteAssignment::whereId($q['id'])->update([
                    'route_id' => $q['route_id'],   //routes (key)> id
                    'time_begin' => $q['time_begin'],
                    'duration' => $q['duration'],
                    'active' => 1,
                ]);
            }
        }

        // return $identifier;
        return $this->sendResponse(true, __('strings.sucess', ['name' => 'The changes have been saved']), $identifier ?? null);
    }

    public function optimizeRoute(Request $request, $routeId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'slots' => 'nullable|array',
            'tech_start_time' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $route = PocomosRoute::with('slots.job_detail')->whereId($routeId)->first();

        /* $jobs = array();
        foreach ($routes as $route) {
            foreach ($route->slots as $slot) {
                if ($slot->type == 'Regular' && $slot->type == 'Dynamic' && ($job = $slot->job_detail)
                            && !($job->status == 'Complete' && $job->status == 'Cancelled'))
                    $jobs[] = $job;
                }
            } */

        $slots = PocomosRouteSlots::whereIn('id', $request->slots)->whereNotIn('type', ['Lunch','Blocked']);

        if ($request->reverse) {
            $slots->orderBy('time_begin', 'desc');
        } else {
            $slots->orderBy('time_begin');
        }

        $slots = $slots->get();

        $lunch = PocomosRouteSlots::whereIn('id', $request->slots)->whereType('Lunch')->first();
        if ($lunch) {
            $lunchDuration = $lunch->duration;
            $addLunchDuration = strtotime("+".$lunchDuration." minutes", strtotime($lunch->time_begin));
            $endLunchTime  = date('H:i:s', $addLunchDuration);
        }

        $blocks = PocomosRouteSlots::whereIn('id', $request->slots)->orderBy('time_begin', 'asc')
                    ->whereType('Blocked')->get();

        // return $blocks;

        // $selectedTime = "19:45:00";
        // $endTime = strtotime("+15 minutes", strtotime($selectedTime));
        // return date('H:i:s', $endTime);

        $selectedTime = $request->tech_start_time;

        $q = 0;
        foreach ($slots as $s) {
            // return $s;

            if ($lunch || $blocks->first()) {
                if ($lunch) {
                    if (($selectedTime >= $lunch->time_begin && $selectedTime <= $endLunchTime)
                                        || $selectedTime == $lunch->time_begin) {
                        $addL_Duration = strtotime("+".$lunchDuration." minutes", strtotime($lunch->time_begin));
                        $selectedTime  = date('H:i:s', $addL_Duration);
                    }
                }

                if ($blocks) {
                    foreach ($blocks as $b) {
                        // return $b;
                        if ($b->time_begin == $selectedTime) {
                            $duration = $b->duration;
                            $addB_duration = strtotime("+".$duration." minutes", strtotime($b->time_begin));
                            $selectedTime  = date('H:i:s', $addB_duration);
                        }
                    }
                }
            }

            // if($q==1){
            //     return $selectedTime;
            // }
            // return $selectedTime;

            $s->update(['time_begin' => $selectedTime, 'duration' => 30]);

            $add30Mins = strtotime("+30 minutes", strtotime($selectedTime));
            $selectedTime  = date('H:i:s', $add30Mins);

            $q++;
        }

        /* $routeSlotIds = PocomosRouteSlots::whereRouteId($routeId)->whereType('Regular')
        ->whereScheduleType('Dynamic')->pluck('id');

        $jobs =PocomosJob::whereIn('slot_id', $routeSlotIds)->where('status','!=','Complete')
                ->where('status','!=','Cancelled')->get(); */

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'The route has been optimized']));
    }

    public function sendFormLetterAction(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'pest_contract' => 'required',
            'job_id' => 'required',
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

        $customer = PocomosCustomer::with('contact_address', 'sales_profile')->where('id', $custId)->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $jobId = $request->job_id;
        $job = null;
        if ($jobId) {
            // dd(77);
            $job = PocomosJob::join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
                    ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
                    ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                    ->where('pcsp.customer_id', $custId)
                    ->where('pocomos_jobs.id', $jobId)
                    ->first();

            // dd($job);
        }

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

        if ($job) {
            // dd(11);
            $sentCount = $this->sendSmsFormLetterFromJobIds(array($jobId), $letter);
        } else {
            // $contract = (isset($form['contract'])) ? $form['contract']->getData() : null;
            $pestContract = PocomosPestContract::with('service_type_details')->whereId($request->pest_contract)->first();
            $sentCount = $this->sendSmsFormLetter($letter, $customer, $pestContract);
        }
        return $this->sendResponse(true, 'SMS form letter(s) sent');
        /*
        get job id,cust id from routes/jobs key
         */
    }

    public function sendSmsFormLetterFromJobIds(array $jobIds, $letter)
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

    public function viewMap(Request $request, $routeId)
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

        // $startDate = $request->start_date;
        // $endDate = $request->end_date;

        $route = PocomosRoute::whereId($routeId)->firstOrFail();

        /* // for calender slots
        $slotsQuery = PocomosRoute::select(
            '*',
            'pocomos_routes.id',
            'pocomos_routes.name',
            'pocomos_routes.locked',
            'pocomos_routes.technician_id',
            'pocomos_routes.date_scheduled',
            'pi.balance as invoice_balance',
            'prs.time_begin',
            'pj.slot_id as job_slot_id',
            'prs.id as slot_id',
            'prs.type as slot_type',
            'pj.status as job_status',
            'pj.id as job_id',
            'pj.color',
            'prs.color as slot_color',
            'ppc.technician_id as pref_tech_id',
            'pj.commission_type',
            'pj.commission_value',
            'pcu.first_name',
            'pcu.last_name',
            'slot_ou.first_name as slot_ou_fname',
            'slot_ou.last_name as slot_ou_lname',
            'ppcst.name as service_type',
            'ppcst.color as service_type_color'
        )
            ->leftJoin('pocomos_route_slots as prs', 'pocomos_routes.id', 'prs.route_id')
            ->leftJoin('pocomos_jobs as pj', 'prs.id', 'pj.slot_id')
            ->leftJoin('pocomos_technicians as pt', 'pocomos_routes.technician_id', 'pt.id')
            ->leftJoin('pocomos_company_office_users as pcou', 'pt.user_id', 'pcou.id')
            ->leftJoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->leftJoin('pocomos_pest_contracts as ppc', 'pj.contract_id', 'ppc.id')
            ->leftJoin('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->leftJoin('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->leftJoin('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->leftJoin('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
            ->leftJoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->leftJoin('orkestra_countries_regions as ocr', 'pa.region_id', 'ocr.id')
            ->leftJoin('pocomos_invoices as pi', 'pj.invoice_id', 'pi.id')
            ->leftJoin('pocomos_invoice_items as pii', 'pi.id', 'pii.invoice_id')
            ->join('pocomos_company_offices as pco', 'pocomos_routes.office_id', 'pco.id')

            //added
            ->leftJoin('pocomos_company_office_users as slot_pcou', 'prs.office_user_id', 'slot_pcou.id')
            ->leftJoin('orkestra_users as slot_ou', 'pcou.user_id', 'slot_ou.id')
            ->leftJoin('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id'); */

        $slotsQuery = $this->calendarSlotsQuery($officeId)->where('pocomos_routes.id', $routeId);

        $blocks = (clone($slotsQuery))->where(function ($q) {
            $q->where('prs.type', 'Lunch')
            ->orWhere('prs.type', 'Blocked')
            ->orWhere('prs.type', 'Reserved');
        })->get()->makeHidden('agreement_body');

        $slots = (clone($slotsQuery))->whereNotNull('pj.slot_id')->groupBy('pj.slot_id')
                                        ->get()->makeHidden('agreement_body');

        $assignments = PocomosTeamRouteAssignment::select('*', 'pocomos_teams_route_assignments.id')
                    ->join('pocomos_routes as pr', 'pocomos_teams_route_assignments.route_id', 'pr.id')
                    ->join('pocomos_teams as pt', 'pocomos_teams_route_assignments.team_id', 'pt.id')
                    ->where('pr.office_id', $officeId)
                    ->where('pocomos_teams_route_assignments.route_id', $routeId)
                    ->where('pocomos_teams_route_assignments.active', 1)
                    ->get();

        //for green flag(default location)
        $officeAddress = PocomosCompanyOffice::with(['routing_address','contact'])
                            ->whereId($officeId)->first();

        return $this->sendResponse(true, __('strings.details', ['name' => 'Calender routes and pool jobs']), [
            'route' => $route,
            'blocks' => $blocks,
            'slots' => $slots,
            'route_team_assignments' => $assignments,
            'office_address' => $officeAddress,
        ]);
    }

    public function geocode($custId)
    {
        $cust = PocomosCustomer::with('contact_address')->findOrFail($custId);

        return $this->sendResponse(true, 'geocode', $cust);
    }
}
