<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use DB;
use Excel;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosJob;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Orkestra\OrkestraUser;
use App\Exports\TechnicianDetailsReport;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosReportSummerTotalConfiguration;
use App\Models\Pocomos\PocomosReportSummerTotalConfigurationStatus;

class DetailedTechReportController extends Controller
{
    use Functions;

    public function getForm(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id','list_name']);

        $officeIds      = $request->office_ids ? $request->office_ids : $branches->pluck('id')->toArray();

        $startDate = date("Y-m-d 0:0:0", strtotime($request->start_date));
        $endDate = date("Y-m-d 23:59:59", strtotime($request->end_date));

        $ids = PocomosCompanyOfficeUser::whereIn('office_id', $officeIds)->whereActive(true)->pluck('id');

        $technicians = PocomosTechnician::with('user_detail.user_details:id,first_name,last_name')->whereIn('user_id', $ids)->whereActive(true)->get();

        $agreements = PocomosAgreement::with('office_details')->whereOfficeId($request->office_id)->whereActive(true)->get(['id','name']);

        return $this->sendResponse(true, 'Detailed Technician Report Form', [
            'branches'      => $branches,
            // 'technicians'   => $technicians,
            'agreements'    => $agreements,
        ]);
    }

    public function technicians(Request $request)
    {
        $v = validator($request->all(), [
            'office_ids' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $ids            = PocomosCompanyOfficeUser::whereIn('office_id', $request->office_ids)->whereActive(true)->pluck('id');
        // $userIds        = PocomosTechnician::whereIn('user_id', $ids)->whereActive(true)->pluck('user_id');
        // $techIds        = PocomosTechnician::with('user_detail.user_details')->whereIn('user_id', $ids)->whereActive(true)->pluck('id');
        $technicians        = PocomosTechnician::with('user_detail.user_details')->whereIn('user_id', $ids)->whereActive(true)->get();
        // $OfficeUserIds  = PocomosCompanyOfficeUser::whereIn('id',$userIds)->pluck('user_id');
        // $technicians    = OrkestraUser::whereIn('id',$OfficeUserIds)->whereActive(true)->get(['first_name','last_name']);

        // $techniciansIds = implode(',',$technicians->pluck('id')->toArray());

        return $this->sendResponse(true, 'Technicians list', [
            'technicians'    => $technicians,
        ]);
    }


    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id','name']);

        $officeIds      = $request->office_ids ? $request->office_ids : $branches->pluck('id')->toArray();

        $startDate = date("Y-m-d 0:0:0", strtotime($request->start_date));
        $endDate = date("Y-m-d 23:59:59", strtotime($request->end_date));

        $ids = PocomosCompanyOfficeUser::whereIn('office_id', $officeIds)->whereActive(true)->pluck('id');
        // $userIds        = PocomosTechnician::whereIn('user_id', $ids)->whereActive(true)->pluck('user_id');
        // $OfficeUserIds  = PocomosCompanyOfficeUser::whereIn('id',$userIds)->pluck('user_id');
        // $technicians    = OrkestraUser::whereIn('id',$OfficeUserIds)->whereActive(true)->get(['id','first_name','last_name']);
        // $techniciansIds = implode(',',$technicians->pluck('id')->toArray());

        // $techIds = $request->technician_ids ? implode(',',$request->technician_ids) : $techniciansIds;

        $technicians = PocomosTechnician::whereIn('user_id', $ids)->whereActive(true)->pluck('user_id')->toArray();

        $technicianIds = $request->technician_ids ? $request->technician_ids : $technicians;

        $technicians = PocomosTechnician::with('user_detail.user_details:id,first_name,last_name')->whereIn('user_id', $technicianIds)->whereActive(true)->get();

        $jobStatus = $request->job_status ? $request->job_status : ["Pending","Complete","Re-scheduled","Cancelled"];

        $agreements = PocomosAgreement::whereOfficeId($request->office_id)->whereActive(true)->get(['id','name']);
        $agreementIds = $agreements->pluck('id')->toArray();

        $agreementIds = $request->agreement_ids ? $request->agreement_ids : $agreementIds;

        $billingFrequencies = $request->billing_frequency ? $request->billing_frequency : ['Per service','Monthly','Initial monthly','Due at signup','Two payments', 'installments'];

        // $techJobs = [];
        foreach ($technicians as $technician) {
            $technicianId = $technician->id;
            $query = PocomosJob::join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
                ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
                ->join('pocomos_agreements as pa', 'pc.agreement_id', 'pa.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
                ->join('pocomos_technicians as pt', 'pocomos_jobs.technician_id', 'pt.id')
                ->whereBetween('pocomos_jobs.date_scheduled', [$startDate, $endDate])
                ->where('pocomos_jobs.technician_id', $technicianId)

                ->whereIn('pocomos_jobs.status', $jobStatus)

                ->whereIn('pa.id', $agreementIds)

                ->whereIn('pc.billing_frequency', $billingFrequencies)

                ->orderBy('date_scheduled', 'desc')
                ->get();

            // return $query;
            // return count($query);

            if (count($query) > 0) {
                $techJobs[$technicianId] = $query;
            }
        }

        return $this->sendResponse(true, 'Detailed Technician Report', [
            // 'branches'      => $branches,
            // 'technicians'   => $technicians,
            // 'agreements'    => $agreements,
            'results'       => $techJobs,
        ]);
    }

    public function getTechnicians(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'technicians' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // Setup our office information
        $officeIds = $request->office_id;
        $officeIds = explode(',', $officeIds);

        $offices = PocomosCompanyOffice::whereIn('id', $officeIds)->get()->toArray();

        $officeIds = array_map(function ($office) {
            return $office['id'];
        }, $offices);

        // Do we have permission to see other tech information?
        $techIds = [];
        if (config('constants.ROLE_TECHNICIAN')
            && !config('constants.ROLE_DASHBOARD_READ')
        ) {
            //THIS IS NOW MANAGE BASED ON ALL OFFICE USERS FETCH TECHNICIANS ONCE ADD LOGIN MODULE THEN NEED TO ADD LOGGED IN OFFICE USER BASE
            $office_users_ids = PocomosCompanyOfficeUser::whereIn('office_id', $officeIds)->pluck('id')->toArray();
            $techIds = PocomosTechnician::whereIn('user_id', $office_users_ids)->pluck('id')->toArray();
        } else {
            // Get TechIDs from the form req ( if any )
            $techIds = $request->technicians ?? null;
            if ($techIds) {
                $techIds = explode(',', $techIds);
            } else {
                $techIds = [];
                // Iterate our offices and pull all technicians
                foreach ($offices as $tempOffice) {
                    $tempOffice = (object)$tempOffice;

                    $foundTechs = DB::select(DB::raw("SELECT t.*
                    FROM pocomos_technicians AS t
                    JOIN pocomos_company_office_users AS u ON t.user_id = u.id
                    JOIN pocomos_company_offices AS o ON u.office_id = o.id
                    WHERE o.id = '$tempOffice->id'"));

                    foreach ($foundTechs as $foundTech) {
                        array_push($techIds, $foundTech->id);
                    }
                }
            }
            $techIds = array_unique($techIds);
        }
        $techIds = $this->convertArrayInStrings($techIds);
        $officeIds = $this->convertArrayInStrings($officeIds);

        $techDetails = DB::select(DB::raw("SELECT ou.*, t.id as 'technician_id'
        FROM pocomos_technicians AS t
        JOIN pocomos_company_office_users AS u ON t.user_id = u.id
        JOIN pocomos_company_offices AS o ON u.office_id = o.id
        JOIN orkestra_users AS ou ON u.user_id = ou.id
        WHERE o.id IN ($officeIds) AND t.id IN ($techIds)"));

        return $this->sendResponse(true, __('strings.list', ['name' => 'Technicians']), $techDetails);
    }

    public function getTechnicianReportDetails(Request $request)
    {
        $v = validator($request->all(), [
            'technican_id' => 'required',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'tech_status_type' => 'nullable|array',
            'tech_status_agreements' => 'nullable|array',
            'tech_status_billing_frequency' => 'nullable|array',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $select_sql = "SELECT cu.id as 'customer_id', CONCAT(cu.first_name, ' ' , cu.last_name ) as 'customer_name', j.invoice_id, pa.street, pa.postal_code, j.type, pcst.name as 'service_type', pmt.name as 'marketing_type', j.date_completed, j.time_begin, j.time_end
        , j.commission_value as 'total_commission', pcc.recurring_price, c.billing_frequency, t.commission_value, t.commission_type, pcc.initial_price, i.balance, pcc.service_frequency, j.status, ca.initial_price as 'agreement_initial_price'
        FROM pocomos_jobs AS j";

        // $select_sql = "SELECT j.*, pcc.*, c.*, ca.*, csp.*, cu.*, t.* FROM pocomos_jobs AS j ";

        // DATEDIFF(MINUTE, j.time_begin, j.time_end) as 'total_time'

        $join_sql = " JOIN pocomos_pest_contracts AS pcc ON j.contract_id = pcc.id
        JOIN pocomos_contracts AS c ON pcc.contract_id = c.id
        JOIN pocomos_agreements AS ca ON c.agreement_id = ca.id
        JOIN pocomos_customer_sales_profiles AS csp ON c.profile_id = csp.id
        JOIN pocomos_customers AS cu ON csp.customer_id = cu.id
        JOIN pocomos_technicians AS t ON j.technician_id = t.id
        JOIN pocomos_addresses AS pa ON cu.contact_address_id = pa.id
        JOIN pocomos_pest_contract_service_types AS pcst ON pcc.service_type_id = pcst.id
        JOIN pocomos_marketing_types AS pmt ON c.found_by_type_id = pmt.id
        JOIN pocomos_company_office_users AS u ON t.user_id = u.id
        JOIN pocomos_company_offices AS o ON u.office_id = o.id
        JOIN orkestra_users AS ou ON u.user_id = ou.id
        JOIN pocomos_invoices AS i ON j.invoice_id = i.id
        ";

        $start_date = date('Y-m-d', strtotime($request->start_date)).' 0:0:0';
        $end_date = date('Y-m-d', strtotime($request->end_date)).' 0:0:0';
        $technican_id = $request->technican_id;

        $where_sql = " WHERE j.date_scheduled between '$start_date' and '$end_date' AND j.technician_id = $technican_id ";

        if (count($request->tech_status_type)) {
            $status = $this->convertArrayInStrings($request->tech_status_type);
            $where_sql .= " AND j.status IN ($status)";
        }
        if (count($request->tech_status_agreements)) {
            $agreements = $this->convertArrayInStrings($request->tech_status_agreements);
            $where_sql .= " AND ca.id IN ($agreements)";
        }
        if (count($request->tech_status_billing_frequency)) {
            $billingFrequency = $this->convertArrayInStrings($request->tech_status_billing_frequency);
            $where_sql .= " AND c.billing_frequency IN ($billingFrequency)";
        }

        $orderby_sql = " ORDER BY j.date_scheduled ASC ";

        $outstanding_price = 0 ;
        $commission_price = 0 ;
        $invoice_price = 0 ;
        $total_time = 0 ;
        $monthly_price_total = 0 ;

        //Calculate for some total values 
        $techDetailsTotalRes = DB::select(DB::raw($select_sql.' '.$join_sql.' '.$where_sql.' '.$orderby_sql));
        foreach($techDetailsTotalRes as $result){
            $dateCompleted = $result->date_completed;
            $beginTime = $result->time_begin;
            $endTime = $result->time_end;
            $jobServiceDate = $dateCompleted;
            if($dateCompleted){
                $day = date('d', strtotime($result->date_completed));
                $month = date('m', strtotime($result->date_completed));
                $year = date('Y', strtotime($result->date_completed));
                $newDate = $year .'-'. $month .'-'. ($day+1);
                $jobServiceDate = (date('U', strtotime($result->time_end)) < date('U', strtotime($result->time_begin)) ? date("Y-m-d", strtotime($newDate)) : date("Y-m-d", strtotime($result->date_completed)));
            }
            $start_date_time = date("Y-m-d", strtotime($dateCompleted)) . ' ' . date("H:i:s", strtotime($result->time_begin));
            $end_date_time = $jobServiceDate . ' ' . date("H:i:s", strtotime($result->time_end));

            $calculatedBeginTime = date('U', strtotime($beginTime));

            $calculatedEndTime = date('U', strtotime($endTime));

            $calculatedBeginTime = date('U', strtotime($start_date_time));

            $calculatedEndTime = date('U', strtotime($end_date_time));

            $calculatedTime = round(($calculatedEndTime - $calculatedBeginTime)/60, 1);

            if($calculatedTime > 0){
                $total_time = $total_time + $calculatedTime;
            }
            $invoice_price = $invoice_price + $result->balance;

            $outstanding_price = $outstanding_price + $result->initial_price;
            if($result->status == 'Complete'){
                if($result->commission_type == 'Flat'){
                    $commission_price = $commission_price + $result->commission_value;
                    $commission_rate =  $result->commission_value;
                }elseif($result->commission_type == 'Rate'){
                    $commission_price = $commission_price + ($result->commission_value * $result->price);
                    $commission_rate =  $result->commission_value * 100;
                }else{
                    $commission_rate =  0;
                }
            }else{
                $commission_rate =  0;
            }

            if($result->billing_frequency == "Monthly"){
                $monthly_price_total = $monthly_price_total + $result->recurring_price;
            }
        }
        //End logic for calculate some total values

        /**For pagination */
        $count = count(DB::select(DB::raw($select_sql.' '.$join_sql.' '.$where_sql.' '.$orderby_sql)));
        /**If result data are from DB::row query then `true` else `false` normal laravel get listing */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $limit_sql = " LIMIT $perPage offset $page";
        /**End */

        $merge_sql = $select_sql.' '.$join_sql.' '.$where_sql.' '.$orderby_sql.' '.$limit_sql;

        $techDetails = DB::select(DB::raw($merge_sql));

        //THIS IS FOR CONVERT TIME INTO TIMESTAMP BECASE REEACT.JS THIS IS NOT POSSIBLE
        //DATE : 31-03-2022
        $techDetails = array_map(function ($val) {
            if ($val->time_begin) {
                $val->time_begin_timestamp = strtotime($val->time_begin);
            } else {
                $val->time_begin_timestamp = 0;
            }

            if ($val->time_end) {
                $val->time_end_timestamp = strtotime($val->time_end);
            } else {
                $val->time_end_timestamp = 0;
            }

            $dateCompleted = $val->date_completed;
            $beginTime = $val->time_begin;
            $endTime = $val->time_end;
            $jobServiceDate = $dateCompleted;
            if($dateCompleted){
                $day = date('d', strtotime($val->date_completed));
                $month = date('m', strtotime($val->date_completed));
                $year = date('Y', strtotime($val->date_completed));
                $newDate = $year .'-'. $month .'-'. ($day+1);
                $jobServiceDate = (date('U', strtotime($val->time_end)) < date('U', strtotime($val->time_begin)) ? date("Y-m-d", strtotime($newDate)) : date("Y-m-d", strtotime($val->date_completed)));
            }
            $start_date_time = date("Y-m-d", strtotime($dateCompleted)) . ' ' . date("H:i:s", strtotime($val->time_begin));
            $end_date_time = $jobServiceDate . ' ' . date("H:i:s", strtotime($val->time_end));

            $calculatedBeginTime = date('U', strtotime($beginTime));

            $calculatedEndTime = date('U', strtotime($endTime));

            $calculatedBeginTime = date('U', strtotime($start_date_time));

            $calculatedEndTime = date('U', strtotime($end_date_time));

            $calculatedTime = round(($calculatedEndTime - $calculatedBeginTime)/60, 1);

            $val->calculated_time = $calculatedTime.' mins.';

            return $val;
        }, $techDetails);
        //END

        $total_details = [
            'total_time'            =>      $total_time.' mins.', 
            'outstanding_price'     =>      number_format($outstanding_price, 2, '.', ','), 
            'commission_price'      =>      number_format($commission_price, 2, '.', ','), 
            'invoice_price'         =>      number_format($invoice_price, 2, '.', ','), 
            'monthly_price_total'   =>      number_format($monthly_price_total, 2, '.', ',')
        ];

        $data = [
            'total_details' =>  $total_details,
            'tech_details'  =>  $techDetails,
            'count'         =>  $count
        ];

        return $this->sendResponse(true, __('strings.details', ['name' => 'Technicians Report']), $data);
    }

    public function downloadTechnicianReportDetails(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'technicians' => 'nullable',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'tech_status_type' => 'nullable|array',
            'tech_status_agreements' => 'nullable|array',
            'tech_status_billing_frequency' => 'nullable|array'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // Setup our office information
        $officeIds = $request->office_id;
        $officeIds = explode(',', $officeIds);

        $offices = PocomosCompanyOffice::whereIn('id', $officeIds)->get()->toArray();

        $officeIds = array_map(function ($office) {
            return $office['id'];
        }, $offices);

        // Do we have permission to see other tech information?
        $techIds = [];
        if (config('constants.ROLE_TECHNICIAN')
            && !config('constants.ROLE_DASHBOARD_READ')
        ) {
            //THIS IS NOW MANAGE BASED ON ALL OFFICE USERS FETCH TECHNICIANS ONCE ADD LOGIN MODULE THEN NEED TO ADD LOGGED IN OFFICE USER BASE
            $office_users_ids = PocomosCompanyOfficeUser::whereIn('office_id', $officeIds)->pluck('id')->toArray();
            $techIds = PocomosTechnician::whereIn('user_id', $office_users_ids)->pluck('id')->toArray();
        } else {
            // Get TechIDs from the form req ( if any )
            $techIds = $request->technicians ?? null;
            if ($techIds) {
                $techIds = explode(',', $techIds);
            } else {
                $techIds = [];
                // Iterate our offices and pull all technicians
                foreach ($offices as $tempOffice) {
                    $tempOffice = (object)$tempOffice;

                    $foundTechs = DB::select(DB::raw("SELECT t.*
                    FROM pocomos_technicians AS t
                    JOIN pocomos_company_office_users AS u ON t.user_id = u.id
                    JOIN pocomos_company_offices AS o ON u.office_id = o.id
                    WHERE o.id = '$tempOffice->id'"));

                    foreach ($foundTechs as $foundTech) {
                        array_push($techIds, $foundTech->id);
                    }
                }
            }
            $techIds = array_unique($techIds);
        }
        $techIds = $this->convertArrayInStrings($techIds);
        $officeIds = $this->convertArrayInStrings($officeIds);

        $select_sql = "SELECT cu.id as 'customer_id', CONCAT(cu.first_name, ' ' , cu.last_name ) as 'customer_name', j.invoice_id, pa.street, pa.postal_code, j.type, pcst.name as 'service_type', pmt.name as 'marketing_type', j.date_completed, j.time_begin, j.time_end
        , j.commission_value as 'total_commission', pcc.recurring_price, c.billing_frequency, t.commission_value, t.commission_type, pcc.initial_price, CONCAT(ou.first_name, ' ' , ou.last_name ) as 'technician_name', i.balance, pcc.service_frequency, j.status, ca.initial_price as 'agreement_initial_price'
        FROM pocomos_jobs AS j";

        $join_sql = " JOIN pocomos_pest_contracts AS pcc ON j.contract_id = pcc.id
        JOIN pocomos_contracts AS c ON pcc.contract_id = c.id
        JOIN pocomos_agreements AS ca ON c.agreement_id = ca.id
        JOIN pocomos_customer_sales_profiles AS csp ON c.profile_id = csp.id
        JOIN pocomos_customers AS cu ON csp.customer_id = cu.id
        JOIN pocomos_technicians AS t ON j.technician_id = t.id
        JOIN pocomos_addresses AS pa ON cu.contact_address_id = pa.id
        JOIN pocomos_pest_contract_service_types AS pcst ON pcc.service_type_id = pcst.id
        JOIN pocomos_marketing_types AS pmt ON c.found_by_type_id = pmt.id
        JOIN pocomos_company_office_users AS u ON t.user_id = u.id
        JOIN pocomos_company_offices AS o ON u.office_id = o.id
        JOIN orkestra_users AS ou ON u.user_id = ou.id
        JOIN pocomos_invoices AS i ON j.invoice_id = i.id
        ";

        $start_date = date('Y-m-d', strtotime($request->start_date)).' 0:0:0';
        $end_date = date('Y-m-d', strtotime($request->end_date)).' 0:0:0';

        $where_sql = " WHERE j.date_scheduled between '$start_date' and '$end_date' AND o.id IN ($officeIds) AND j.technician_id IN ($techIds) ";

        if (count($request->tech_status_type)) {
            $status = $this->convertArrayInStrings($request->tech_status_type);
            $where_sql .= " AND j.status IN ($status)";
        }
        if (count($request->tech_status_agreements)) {
            $agreements = $this->convertArrayInStrings($request->tech_status_agreements);
            $where_sql .= " AND ca.id IN ($agreements)";
        }
        if (count($request->tech_status_billing_frequency)) {
            $billingFrequency = $this->convertArrayInStrings($request->tech_status_billing_frequency);
            $where_sql .= " AND c.billing_frequency IN ($billingFrequency)";
        }

        $orderby_sql = " ORDER BY j.date_scheduled ASC ";
        $merge_sql = $select_sql.' '.$join_sql.' '.$where_sql.' '.$orderby_sql;

        $techDetails = DB::select(DB::raw($merge_sql));

        return Excel::download(new TechnicianDetailsReport($techDetails), 'TechnicianReportDetails.csv');
    }
}
