<?php

namespace App\Http\Controllers\API\Pocomos\Routing;

use DB;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosRoute;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Orkestra\OrkestraCountry;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class RouteMapController extends Controller
{
    use Functions;

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
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
            ->join('pocomos_routes as pr', 'pocomos_technicians.id', 'pr.technician_id')
            ->join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
            ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->where('pr.office_id', $officeId)
            ->where('pocomos_technicians.active', 1)
            ->where('pcou.active', 1)
            ->where('ou.active', 1)
            ->groupBy('pocomos_technicians.id')
            ->get();

        $agreements = PocomosAgreement::whereOfficeId($request->office_id)->whereActive(true)->get(['id', 'name']);

        $tags = PocomosTag::whereOfficeId($officeId)->whereActive(1)->get();

        $customFields = PocomosPestOfficeSetting::with('custom_field_configurations')->whereOfficeId($officeId)->get();

        //for green flag(default location)
        $officeAddress = PocomosCompanyOffice::with(['routing_address','contact'])->whereId($officeId)->first();

        return $this->sendResponse(true, 'Reminder filters', [
            'technicians'   => $technicians,
            'agreements'   => $agreements,
            'tags'   => $tags,
            'custom_fields'   => $customFields,
            'office_address' => $officeAddress,
        ]);
    }

    public function getAllZipCodes(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $sql = 'SELECT DISTINCT a.postal_code as zipcode, a.postal_code as id
                FROM pocomos_addresses a
                    JOIN pocomos_customers c ON c.contact_address_id = a.id
                    JOIN pocomos_customer_sales_profiles csp ON csp.customer_id = c.id
                    -- WHERE csp.office_id = '.$officeId.'
                    ';

        if ($request->search) {
            $search = '"%'.$request->search.'%"';
            $sql .= ' AND a.postal_code LIKE '.$search.'';
        }

        $sql .= ' AND a.postal_code IS NOT NULL';
        $sql .= ' AND a.postal_code != " "';

        $sql .= ' ORDER BY a.postal_code ASC';

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $zipcodes = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'All customers zipcodes', [
            'zipcodes'   => $zipcodes,
            'count'   => $count,
        ]);
    }

    public function getAllMapCodes(Request $request)
    {
        $sql = 'SELECT pcc.map_code as mapcode, pcc.map_code as id 
                    FROM pocomos_pest_contracts pcc
                    JOIN pocomos_contracts c ON pcc.contract_id = c.id
                    JOIN pocomos_customer_sales_profiles csp ON c.profile_id = csp.id
                    JOIN pocomos_customers cu ON csp.customer_id = cu.id
                    WHERE csp.office_id = '.$request->office_id.' ';

        if ($request->search) {
            $search = '"%'.$request->search.'%"';
            $sql .= ' AND pcc.map_code LIKE '.$search.'';
        }

        $sql .= ' AND pcc.map_code IS NOT NULL';
        $sql .= ' AND pcc.map_code != " "';

        $sql .= ' GROUP BY pcc.map_code';

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $mapcodes = DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'All customers mapcodes', [
            'mapcodes'   => $mapcodes,
            'count'   => $count,
        ]);
    }

    public function jobs(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $dateStart = $request->start_date;
        $dateEnd = $request->end_date;
        $lastServiceStartDate = $request->last_service_start_date;
        $lastServiceEndDate = $request->last_service_end_date;

        $bottomLeftLat = $request->bottomLeftLat;
        $bottomLeftLong = $request->bottomLeftLong;
        $topRightLat = $request->topRightLat;
        $topRightLong = $request->topRightLong;

        // return $customFiltersNew;

        $technicians = $request->technician_ids ? $request->technician_ids : 0;

        $officeId = $request->office_id;
        $dateSchedule = $request->date_schedule;

        $missingSQL = 'SELECT COUNT(DISTINCT(c.id)) AS missingAddressCount
                FROM pocomos_addresses a
                    JOIN pocomos_customers c ON c.contact_address_id = a.id
                    LEFT JOIN pocomos_customer_state cus on cus.customer_id = c.id
                    JOIN pocomos_customer_sales_profiles csp ON csp.customer_id = c.id
                    JOIN pocomos_contracts pc ON pc.profile_id = csp.id
                    JOIN pocomos_pest_contracts pcc ON pcc.contract_id = pc.id
                    LEFT JOIN pocomos_agreements pa ON pc.agreement_id = pa.id
                    LEFT JOIN pocomos_pest_agreements pca on pcc.agreement_id = pca.id
                    JOIN pocomos_jobs j ON j.contract_id = pcc.id
                    LEFT JOIN pocomos_pest_contracts_tags pt on pcc.id = pt.contract_id
                    LEFT JOIN pocomos_route_slots rs ON j.slot_id = rs.id
                    LEFT JOIN pocomos_routes r ON rs.route_id = r.id
                WHERE csp.office_id = ' . $officeId . '
                AND (a.latitude IS NULL OR a.longitude IS NULL)
                AND j.date_scheduled BETWEEN "' . $dateStart . '" AND "' . $dateEnd . '"
                AND c.status = "Active"
                AND pc.status = "Active" ';

        if (is_array($technicians)) {
            $techniciansStr = implode(',', $technicians);
            $missingSQL .= ' AND (pcc.technician_id in ('.$techniciansStr.') OR r.technician_id in ('.$techniciansStr.'))';
        }

        if ($lastServiceStartDate && $lastServiceEndDate) {
            $missingSQL .= ' AND cus.last_service_date BETWEEN "'.$lastServiceStartDate.'" AND "'.$lastServiceEndDate.'" ';
        }

        if ($request->agreements) {
            $agreements = implode(',', $request->agreements);
            $missingSQL .= ' AND pcc.agreement_id IN ('.$agreements.')';
        }

        if ($request->tags) {
            $tags = implode(',', $request->tags);
            $missingSQL .= ' AND pt.tag_id IN ('.$tags.')';
        }

        if ($request->mapcodes) {
            $mapcodes = implode(',', $request->mapcodes);
            $missingSQL .= ' AND pcc.map_code IN ('.$mapcodes.')';
        }

        if ($request->zipcodes) {
            $zipcodes = implode(',', $request->zipcodes);
            $missingSQL .= ' AND a.postal_code IN ('.$zipcodes.')';
        }

        $missingStatement = DB::select(DB::raw($missingSQL));
        $missingAddressCount = $missingStatement;

        $markerColorScheme = PocomosPestOfficeSetting::whereOfficeId($officeId)->firstOrFail()->route_map_coloring_scheme;

        //  return   $PocomosAddress =  PocomosAddress::whereBetween('latitude', [$bottomLeftLat, $topRightLat])
    //                     ->whereBetween('longitude', [$bottomLeftLong, $topRightLong])
    //                 ->first();

        $query = PocomosJob::with('route_detail.route_detail')
            ->select(
                '*',
                'pocomos_jobs.id',
                'pocomos_jobs.slot_id',
                'pocomos_jobs.date_scheduled',
                'pocomos_jobs.type as job_type',
                'pocomos_jobs.status as job_status',
                'pocomos_jobs.contract_id as job_contract',
                'ppc_pt.color as tech_color',
                'ppcst.name as contract_type',
                'ptr.color as route_tech_color',
                'pr.technician_id as tech_id',
                'pcu.id as customer_id',
                'pi.balance as invoice_balance',
                'pcu.first_name',
                'pcu.last_name',
                'ou.first_name as pref_tech_fname',
                'ou.last_name as pref_tech_lname',
                'pag.name as agreement_name',
                'pn.summary as cust_route_map_note',
                'ppc.service_frequency',
                'ppc.technician_id as pest_contract_tech',
                'pcs.last_service_date as last_job',
                'pcs.next_service_date as next_job',
                'pcs.balance_overall',
                'prs.schedule_type as slot_schedule_type',
                'prs.id as qqq'
            )
            ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->join('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->leftJoin('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
            ->leftJoin('pocomos_pest_contracts_tags as ppct', 'ppc.id', 'ppct.contract_id')
            ->leftJoin('pocomos_tags as pt', 'ppct.tag_id', 'pt.id')
            ->leftJoin('pocomos_route_slots as prs', 'pocomos_jobs.slot_id', 'prs.id')
            ->leftJoin('pocomos_routes as pr', 'prs.route_id', 'pr.id')
            ->leftJoin('pocomos_pest_agreements as ppa', 'ppc.agreement_id', 'ppa.id')
            ->leftJoin('pocomos_agreements as pag_ppc', 'ppa.agreement_id', 'pag_ppc.id')

            // added
            ->join('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
            ->join('pocomos_technicians as ppc_pt', 'ppc.technician_id', 'ppc_pt.id')
            ->join('pocomos_company_office_users as pcou', 'ppc_pt.user_id', 'pcou.id')
            ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')

            ->leftJoin('pocomos_technicians as ptr', 'pr.technician_id', 'ptr.id')

            ->leftJoin('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id')
            ->leftJoin('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
            ->leftJoin('orkestra_countries_regions as ocr', 'pa.region_id', 'ocr.id')
            ->leftJoin('pocomos_notes as pn', 'pcu.route_map_note', 'pn.id')

            ->where('pc.status', 'Active')
            ->where('pcu.status', 'Active')
            ->whereNotIn('pocomos_jobs.status', ['Complete', 'Cancelled'])
            ->whereBetween('pocomos_jobs.date_scheduled', [$dateStart, $dateEnd])
            // ->whereBetween('pa.latitude', [$bottomLeftLat, $topRightLat])
            // ->whereBetween('pa.longitude', [$bottomLeftLong, $topRightLong])
            // ->whereBetween('pa.latitude', [$bottomLeftLat, $bottomLeftLat + 1.5])
            // ->whereBetween('pa.longitude', [$bottomLeftLong, $bottomLeftLong + 7])
        ;

        if ($lastServiceStartDate && $lastServiceEndDate) {
            $query->whereBetween('pcs.last_service_date', [$lastServiceStartDate, $lastServiceEndDate]);
        }

        if ($request->agreements) {
            $query->whereIn('pag.id', $request->agreements);
        }

        if ($request->tags) {
            $query->whereIn('ppct.tag_id', $request->tags);
        }

        if ($request->mapcodes) {
            $query->whereIn('ppc.map_code', $request->mapcodes);
        }

        if ($request->zipcodes) {
            $query->whereIn('pa.postal_code', $request->zipcodes);
        }

        if (is_array($technicians)) {
            // return 11;
            $query->where(function ($q) use($technicians) { 
                    $q->whereIn('ppc.technician_id', $technicians)
                ->orWhereIn('pr.technician_id', $technicians);
           });
            
        } elseif ($technicians == 'no') {
            // return 11;
            if ($markerColorScheme == 'Preferred') {
                $query->whereNull('ppc.technician_id')->whereNull('pr.technician_id');
            } elseif ($markerColorScheme == 'Scheduled') {
                $query->whereNull('pocomos_jobs.slot_id')->whereNull('pr.technician_id');
            }
        }

        // $customFields = "&customField_1524=&customField_1533=";
        $customFields =$request->custom_fields;

        if ($customFields) {
            $str = ltrim($customFields, '&');
            $customFiltersAnd = explode('&', $str);
            $customFiltersNew = [];
            foreach ($customFiltersAnd as $custom) {
                $customFiltersNew[] = explode('=', $custom);
            }
        } else {
            $customFiltersNew = [];
        }

        if ($customFiltersNew) {
            $index = 0;
            foreach ($customFiltersNew as  $data) {
                $key = $data[0];
                $keyEnd = explode('_', $key);   //customField_1524
                $label = end($keyEnd);
                $value = $data[1];

                if ($value!=0) {
                    $alias = 'cfc' . $index;
                    $fromAlias = 'cf' . $index;

                    $query->join('pocomos_custom_fields as '.$fromAlias.'', 'ppc.id', $fromAlias.'.pest_control_contract_id')
                            ->join(
                                'pocomos_custom_field_configuration as '.$alias.'',
                                $fromAlias.'.custom_field_configuration_id',
                                $alias.'.id'
                            )
                        ->where($alias.'.id', $label)
                        ->where($fromAlias.'.value', 'like', '%'.$value.'%');

                    // $customFields = PocomosCustomField::join('pocomos_custom_field_configuration as '.$alias.'',
                    //                             'pocomos_custom_fields.custom_field_configuration_id', $alias.'.'.'id')
                    //                 ->where($alias.'.'.'id', $label)
                    //                 ->where('pocomos_custom_fields.value','like', '%'.$value.'%')
                    //                 ->get();
                }
                $index++;
            }
        }

        $jobs = (clone($query))->whereBetween('pa.latitude', [$bottomLeftLat, $topRightLat])
                   ->whereBetween('pa.longitude', [$bottomLeftLong, $topRightLong])
                   ->get()->makeHidden('agreement_body');

        $allPolylines = (clone($query))->whereNotNull('pr.technician_id')
                ->orderBy('pocomos_jobs.date_scheduled')->get()->makeHidden('agreement_body');

        // array_shift($allPolylines);

        // $polylines = [];
        $temp = [];

        $i=0;
        $p=0;

        foreach ($allPolylines as $q) {
            foreach ($allPolylines as $w) {
                if ($q->id !== $w->id && $q->date_scheduled == $w->date_scheduled && $q->tech_id == $w->tech_id) {
                    if (isset($polylines)) {
                        $u = $i-1;
                        if ($q->date_scheduled !== $polylines[$p]['path'][$u]['date_scheduled'] && $q->tech_id !== $polylines[$p]['path'][$u]['tech_id']) {
                            $p++;
                            $i=0;
                        }
                    }

                    $polylines[$p]['path'][$i]['id'] = $q->id;
                    $polylines[$p]['path'][$i]['date_scheduled'] = $q->date_scheduled;
                    $polylines[$p]['path'][$i]['tech_id'] = $q->tech_id;
                    $polylines[$p]['path'][$i]['latitude'] = $q->latitude;
                    $polylines[$p]['path'][$i]['longitude'] = $q->longitude;
                    $polylines[$p]['path'][$i]['route_tech_color'] = $q->route_tech_color;

                    // if($i==1){
                    // return $polylines;
                    // return $q->date_scheduled;
                    // }

                    // if($q->date_scheduled !== $polylines[$p]['path'][$i]['date_scheduled'] && $q->tech_id !== $polylines[$p]['path'][$i]['tech_id']){
                    //     // return 11;
                    //     $p++;
                    // }
                    $i++;
                }
            }

            // if(count($polylines)){
            //     $temp[$k]['path'] = $polylines;
            //     $k++;
            // }
        }

        // return $jobs;

        if(!$jobs->first()){
            // return 11;
            $polylines = [];
        }

        // return $temp;
        // return $polylines[0];

        // return count($jobs);

        return $this->sendResponse(true, __('strings.details', ['name' => 'Map jobs']), [
            'jobs' => $jobs,
            'missingAddress' => $missingAddressCount,
            // 'allPolylines' => $allPolylines,
            'polylines' => $polylines ?? [],
            'colorScheme' => $markerColorScheme
        ]);

        /*
        pin number = day of the month of job date_scheduled
        pin color = tech_color of contract (grey if not exist)
        polyline color= tech color of slot (route_tech_color , black if not exist)

        //pin details on click
        customer name
        contract_type
        service frequency
        preferred tech is of contract (pref_tech_fname/lname)

        //for rounded jobs detail
        value = sum of all jobs price/balance
        invoice_balance
        pref_tech_fname/lname
        contract = agreement_name
        service type = contract_type
        job = job_type
        cust_route_map_note
        this job = job date scheduled
        last_job
        next_job
        Days Since Last Job = this job-last job

        //draw polyline:
        tech_id != null, from/to same date scheduled , slot(tech) same (tech_id),
        */
    }


    public function scheduleAction(Request $request)
    {
        $v = validator($request->all(), [
            'job_ids' => 'required|array',
            'date_scheduled' => 'required',
            'offset_future_jobs' => 'nullable|boolean',
            'reschedule_future_jobs' => 'nullable|boolean',
            'week_of_the_month' => 'nullable',
            'day_of_the_week' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $options['date_scheduled'] = $request->date_scheduled;
        $options['offset_future_jobs'] = $request->offset_future_jobs;
        $options['reschedule_future_jobs'] = $request->reschedule_future_jobs;
        $options['week_of_the_month'] = $request->week_of_the_month;
        $options['day_of_the_week'] = $request->day_of_the_week;

        $jobs = PocomosJob::with('invoice_detail')->whereIn('id', $request->job_ids)->get();

        foreach ($jobs as $j) {
            try {
                $this->rescheduleJobWithOptionsImproved($options, $j);
              } catch (\Exception $e) {

                // Log::error($e);

                return $this->sendResponse(true, ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
              }
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Jobs rescheduled']));
    }


    public function preferredTechnicianList(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $technicians = PocomosTechnician::select('*', 'pocomos_technicians.id')
            ->join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
            ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->where('pcou.office_id', $request->office_id)
            ->where('ou.active', 1)
            ->where('pocomos_technicians.active', 1)
            ->get();

        return $this->sendResponse(true, 'technicians', $technicians);
    }

    public function updateTechnician(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'job_ids' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;
        $jobIds = $request->job_ids;

        foreach ($jobIds as $jobId) {
            $job = PocomosJob::with('contract')
                ->select('*', 'pocomos_jobs.contract_id')->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
                ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
                ->where('pcsp.office_id', $officeId)
                ->where('pocomos_jobs.id', $jobId)
                ->first();

            if ($job->contract) {
                $contract = $job->contract->update([
                    'technician_id' => $request->technician_id,
                ]);
            }
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Updated technician']));
    }

    public function getRouteAndTechnicianByDate(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'pick_date' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $pickDate = $request->pick_date;

        $route = PocomosRoute::select('*', 'pocomos_routes.id')
            ->leftJoin('pocomos_technicians as pt', 'pocomos_routes.technician_id', 'pt.id')
            ->leftJoin('pocomos_company_office_users as pcou', 'pt.user_id', 'pcou.id')
            ->leftJoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->where('pocomos_routes.date_scheduled', $pickDate)
            ->where('pocomos_routes.office_id', $officeId)
            ->get();

        return $this->sendResponse(true, 'Technician and route', $route);
    }


    public function createRouteAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'picked_date' => 'required',
            'job_ids' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $routeId = $request->route_id;
        $officeId = $request->office_id;

        if ($request->add_to_existing_route && $routeId) {
            $route = PocomosRoute::findOrFail($routeId, );
        } else {
            $route = $this->createRoute_routeFactory($officeId, $request->picked_date, $request->technician_id, 'MapController:406');

            // $route = PocomosRoute::create([
            //     'office_id' => $officeId,
            //     'name' => 'Route',
            //     'technician_id' => $request->technician_id ?? null,
            //     'date_scheduled' => $request->picked_date,
            //     'active' => 1,
            //     'locked' => 0,
            //     'created_by' => 'No idea'
            // ]);
        }

        // return 11;
        $q=0;

        foreach ($request->job_ids as $jobId) {
            $job = PocomosJob::select('*', 'pocomos_jobs.id', 'ppc.id as contract_id')
               ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
               ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
               ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
               ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
               ->where('pcsp.office_id', $officeId)
               ->where('pocomos_jobs.id', $jobId)
               ->first();

            // return  $job->contract->contract_details->agreement_details->office_details;

            if ($job) {
                $this->assignJobToRouteNew($job, $route, null, false, null, $q);
            }
            $q++;

        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Created route']), $route);
    }

    public function unresolvedMarkers(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        $dateStart = $request->dateStart;
        $dateEnd = $request->dateEnd;

        $sql = 'SELECT c.id AS id, a.id as qqq
                 FROM pocomos_addresses a
                     JOIN pocomos_customers c ON c.contact_address_id = a.id
                     JOIN pocomos_customer_sales_profiles csp ON csp.customer_id = c.id
                     JOIN pocomos_contracts pc ON pc.profile_id = csp.id
                     JOIN pocomos_pest_contracts pcc ON pcc.contract_id = pc.id
                     JOIN pocomos_jobs j ON j.contract_id = pcc.id
                    --  where 1=1
                 WHERE csp.office_id = '.$request->office_id.'
                 AND (a.latitude IS NULL OR a.longitude IS NULL)
                 AND j.date_scheduled BETWEEN "'.$dateStart.'" AND "'.$dateEnd.'"
                 AND c.status = "Active"
                 AND pc.status = "Active"
                 ';

        $missingStatement = DB::select(DB::raw($sql));

        $custIdsArr = array_map(function ($row) {
            return $row->id;
        }, $missingStatement);

        $custIds = implode(',', $custIdsArr);

        if ($custIds == null) {
            $customers = array();
        } else {
            $dql = 'SELECT *
                    FROM  pocomos_customer_sales_profiles csp
                    JOIN pocomos_customers c on csp.customer_id=c.id
                    JOIN pocomos_addresses a on c.contact_address_id=a.id
                    LEFT JOIN orkestra_countries_regions reg on a.region_id = reg.id
                    LEFT JOIN pocomos_phone_numbers ppn ON a.phone_id = ppn.id

                    where c.id IN ('.$custIds.')
                    AND csp.office_id = '.$request->office_id.'
                    ';

            if ($request->search) {
                $search = '"%' . $request->search . '%"';
                $dql .= ' AND (
                         c.first_name LIKE ' . $search . ' 
                        OR c.last_name LIKE ' . $search . ' 
                        OR CONCAT(c.first_name, \' \', c.last_name) LIKE ' . $search . '
                        OR a.street LIKE ' . $search . '
                        OR a.city LIKE ' . $search . '
                        OR reg.name LIKE ' . $search . '
                        OR a.postal_code LIKE ' . $search . '
                        )';
            }

            /**For pagination */
            $count = count(DB::select(DB::raw($dql)));

            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $dql .= " LIMIT $perPage offset $page";

            $customers = DB::select(DB::raw($dql));
        }

        $countryRegions = OrkestraCountry::with('countryregion')->get();
        // OfficeController@countryregionlist

        return $this->sendResponse(true, 'List of Unresolved Markers', [
            'customers' => $customers,
            'count' => $count ?? 0,
            // 'country_regions' => $countryRegions,
        ]);

        /*
        company name = company_name
        state = region_id
        enable email dlvry = deliver_email
        account_type
        */
    }


    public function updateAddress(Request $request, $custId)
    {
        $customer = PocomosCustomer::with('contact_address.primaryPhone')->findOrFail($custId);

        $customer->update([
            'company_name' => $request->company_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'deliver_email' => $request->deliver_email,
            'secondary_emails' => implode(',', $request->secondary_emails),
            'account_type' => $request->account_type,
        ]);

        if ($customer->contact_address) {
            $customer->contact_address->update([
                'street' => $request->street,
                'suite' => $request->suite,
                'city' => $request->city,
                'region_id' => $request->region_id,
                'postal_code' => $request->postal_code,
            ]);
        }

        // return $customer->contact_address->primaryPhone;

        // if($customer->contact_address->primaryPhone){
        $customer->contact_address->primaryPhone->update([
            'number' => $request->number,
        ]);
        // }

        return $this->sendResponse(true, __('strings.update', ['name' => 'Customer information has been ']), $customer);
    }

    public function updateGeocode(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
            'override_geocode' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::with('contact_address')->findOrFail($custId);

        if ($customer->contact_address) {
            $customer->contact_address->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'override_geocode' => $request->override_geocode,
                'valid' => true
            ]);
        }

        return $this->sendResponse(true, __('strings.update', ['name' => 'Customer GeoCode has been']), $customer);
    }
}
