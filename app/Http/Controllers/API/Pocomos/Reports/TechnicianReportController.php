<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosJob;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Exports\ExportTechnicianReport;
use DB;
use Excel;

class TechnicianReportController extends Controller
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

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id','list_name']);

        // $ids            = PocomosCompanyOfficeUser::whereIn('office_id', $request->office_ids)->whereActive(true)->pluck('id');
        // $userIds        = PocomosTechnician::whereIn('user_id', $ids)->whereActive(true)->pluck('user_id');
        // $OfficeUserIds  = PocomosCompanyOfficeUser::whereIn('id',$userIds)->pluck('user_id');
        // $technicians    = OrkestraUser::whereIn('id',$OfficeUserIds)->whereActive(true)->get(['id','first_name','last_name']);
        // $techniciansIds = implode(',',$technicians->pluck('id')->toArray());

        return $this->sendResponse(true, 'Technician Report filters', [
            'branches'    => $branches,
            // 'technicians'    => $technicians,
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
        $userIds        = PocomosTechnician::whereIn('user_id', $ids)->whereActive(true)->pluck('user_id');
        $OfficeUserIds  = PocomosCompanyOfficeUser::whereIn('id', $userIds)->pluck('user_id');
        $technicians    = OrkestraUser::whereIn('id', $OfficeUserIds)->whereActive(true)->get(['id','first_name','last_name']);
        // $techniciansIds = implode(',',$technicians->pluck('id')->toArray());

        return $this->sendResponse(true, 'Technicians list', [
            'technicians'    => $technicians,
        ]);
    }

    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1'
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

        $branches = PocomosCompanyOffice::whereId($officeId)->orWhere('parent_id', $officeId)->get(['id','name']);

        $officeIds = $request->office_ids ? $request->office_ids : $branches->pluck('id')->toArray();
        $officeIds = implode(',', $officeIds);

        $ids            = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereActive(true)->pluck('id');
        $userIds        = PocomosTechnician::whereIn('user_id', $ids)->whereActive(true)->pluck('user_id');
        $OfficeUserIds  = PocomosCompanyOfficeUser::whereIn('id', $userIds)->pluck('user_id');
        $technicians    = OrkestraUser::whereIn('id', $OfficeUserIds)->whereActive(true)->get(['id','first_name','last_name']);
        $techniciansIds = implode(',', $technicians->pluck('id')->toArray());

        // $techIds = $request->technician_ids ? $request->technician_ids : $techniciansIds;
        $techIds = $request->technician_ids ? implode(',', $request->technician_ids) : [];

        $jobTypes = [
            'Initial' => 'Jobs that were Initial Jobs or the customers first service',
            'Regular' => 'Jobs assigned that were regular services',
            'Re_service' => 'Jobs that were re-services during time period',
            'Pickup_Service' => 'Jobs that were pickup services during time period.',
        ];

        $jobStatuses = [
            'Complete' => 'Jobs assigned to technician that were completed during time period by that technician',
            'Cancelled' => 'Jobs assigned to technician that were cancelled.',
            'Re_scheduled' => 'Jobs assigned to a technician that were re-scheduled during the time period, and that were not completed by the technician.',
            'Pending' => 'Jobs assigned to technician that remain unchanged. They are still on the route and not completed',
        ];

        $sql = "SELECT 
            concat(users.first_name, ' ', users.last_name) AS `name`,
            technicians.id As techId,
            COUNT(*) AS `Total_Count`,
            SUM(invoices.amount_due) AS `Total_Value`,
            SUM(if(jobs.at_fault BETWEEN '".$startDate."' AND '".$endDate."', invoices.amount_due, 0)) AS `At_Fault_Value`,
            SUM(if(jobs.at_fault BETWEEN '".$startDate."' AND '".$endDate."', 1, 0)) AS `At_Fault_Count`,
            SUM(if(jobs.at_fault BETWEEN '".$startDate."' AND '".$endDate."', 1, 0)) / COUNT(*) * 100 AS `At_Fault_Percentage`,";

        foreach ($jobStatuses as $key => $jobStatus) {
            $sql .= "SUM(if(jobs.status = '".$key."', 1, 0)) AS `".$key."_Count`, ";
            $sql .= "SUM(if(jobs.status = '".$key."', invoices.amount_due, 0)) AS `".$key."_Value`, ";
            $sql .= "SUM(if(jobs.status = '".$key."', 1, 0)) / COUNT(*) * 100 as `".$key."_Percentage`, ";
        }

        foreach ($jobTypes as $key => $jobType) {
            $sql .= "SUM(if(jobs.type = '".$key."', 1, 0)) AS `".$key."_Count`, ";
            $sql .= "SUM(if(jobs.type = '".$key."', invoices.amount_due, 0)) AS `".$key."_Value`, ";
            $sql .= "SUM(if(jobs.type = '".$key."', 1, 0)) / COUNT(*) * 100 as `".$key."_Percentage`, ";
        }

        $sql .=
            "SUM(
                CASE
                    WHEN jobs.commission_edited = 1
                THEN CASE
                    WHEN jobs.commission_type = 'Flat'
                    THEN jobs.commission_value
                ELSE invoices.amount_due * (jobs.commission_value)
                END
                ELSE CASE
                    WHEN technicians.commission_type = 'Flat'
                    THEN technicians.commission_value
                    ELSE invoices.amount_due * technicians.commission_value
                END
                END) AS `Total_Commission`

            FROM pocomos_invoices invoices
            LEFT JOIN pocomos_jobs jobs
                ON invoices.id = jobs.invoice_id
            LEFT JOIN pocomos_technicians technicians
                ON jobs.technician_id = technicians.id
            LEFT JOIN pocomos_company_office_users company_office_users
                ON technicians.user_id = company_office_users.id
            LEFT JOIN orkestra_users users
                ON company_office_users.user_id = users.id
            LEFT JOIN pocomos_route_slots route_slots
                ON jobs.slot_id = route_slots.id
            LEFT JOIN pocomos_routes routes
                ON routes.id = route_slots.route_id
            -- where 1=1 
            WHERE company_office_users.office_id IN (".$officeIds.")
            AND (
                routes.date_scheduled BETWEEN '".$startDate."' AND '".$endDate."'
            OR (
                jobs.date_scheduled BETWEEN '".$startDate."' AND '".$endDate."'
                OR jobs.date_completed BETWEEN '".$startDate."' AND '".$endDate."'
            )
            )
            AND
                ( jobs.technician_id IS NOT NULL
                OR jobs.slot_id IS NOT NULL
            ) ";

        if ($techIds) {
            $sql .= 'AND ( ( jobs.technician_id IN ( '.$techIds.' ) )
                    OR ( routes.technician_id IN ( '.$techIds.' ) ) ) ';
        }

        $sql .= 'GROUP BY users.id ';
        $sql .= 'ORDER BY users.first_name ASC';

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));
        /**If result data are from DB::row query then `true` else `false` normal laravel get listing */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";
        /**End */

        $stats = DB::select(DB::raw($sql));
        $results = $stats;

        $totals = array();
        foreach ($stats as $technician) {
            foreach ($technician as $key => $value) {
                if (isset($totals[$key])) {
                    if (is_numeric($value)) {
                        $totals[$key] += $value;
                    }
                } else {
                    $totals[$key] = $value;
                }
            }
        }

        $fieldNames = array_merge($jobTypes, $jobStatuses);
        $fieldNames['At-Fault'] = 'Jobs performed by this Technician that were re-serviced during the specified time period';

        $jobs = $this->findCompletedJobsByOfficeInRange($officeIds, $startDate, $endDate);

        $badTechnicians = array();
        $badTechnicianIds = array();

        foreach ($jobs as $job) {
            if ($job->type == 'RESERVICE') {
                // return $job->id;
                $q = PocomosJob::with(['contract.contract_details.profile_details','invoice'])->whereInvoiceId($job->invoice_id)->first();
                $jobId     = $q->id;
                $profileId = $q->contract->contract_details->profile_details->id;
                $officeId  = $q->contract->contract_details->profile_details->office_id;
                $realJobs = $this->findCompletedJobPriorToJobByIdAndOffice($jobId, $profileId, $officeId);

                if ($realJobs) {
                    foreach ($realJobs as $realJob) {
                        // return $realJobs['technician_id'];
                        // return $realJobs;
                        if ($realJob !== $job->id) {
                            $badTech = $realJobs['technician_id'];
                            if ($badTech) {
                                $badTechId = $realJobs['technician_id'];
                                // $invoice = $realJob->getInvoice();
                                $badJobAmount = $realJobs['invoice']['amount_due'];
                                $badTechnicians[$badTechId][] = $badJobAmount;
                                $badTechnicianIds[] = $badTechId;
                            }
                        }
                    }
                }
            }
        }

        // foreach ($results as $key => $result) {
        //     if (in_array($result['techId'], $badTechnicianIds)) {
        //         $results[$key]['At-Fault Value'] = array_sum($badTechnicians[$result['techId']]);
        //         $results[$key]['At-Fault Count'] = count($badTechnicians[$result['techId']]);
        //     }
        // }

        /*
        $results = [
            [
                "name" => "john doe",
                "techId" => 9978,
                "Total_Count" => 1,
                "Total_Value" => "100.00",
                "At_Fault_Value" => "10.00",
                "At_Fault_Count" => "10",
                "At_Fault_Percentage" => "10.0000",
                "Complete_Count" => "40",
                "Complete_Value" => "40.00",
                "Complete_Percentage" => "40.0000",
                "Cancelled_Count" => "30",
                "Cancelled_Value" => "30.00",
                "Cancelled_Percentage" => "30.0000",
                "Re_scheduled_Count" => "50",
                "Re_scheduled_Value" => "50.00",
                "Re_scheduled_Percentage" => "50.0000",
                "Pending_Count" => "20",
                "Pending_Value" => "20.00",
                "Pending_Percentage" => "20.0000",
                "Initial_Count" => "30",
                "Initial_Value" => "30.00",
                "Initial_Percentage" => "30.0000",
                "Regular_Count" => "60",
                "Regular_Value" => "60.00",
                "Regular_Percentage" => "60.0000",
                "Re_service_Count" => "70",
                "Re_service_Value" => "70.00",
                "Re_service_Percentage" => "70.0000",
                "Pickup_Service_Count" => "40",
                "Pickup_Service_Value" => "40.00",
                "Pickup_Service_Percentage" => "40.0000",
                "Total_Commission" => "90.0000"
            ],
            [
                "name" => "john doe",
                "techId" => 9978,
                "Total_Count" => 1,
                "Total_Value" => "100.00",
                "At_Fault_Value" => "10.00",
                "At_Fault_Count" => "10",
                "At_Fault_Percentage" => "10.0000",
                "Complete_Count" => "40",
                "Complete_Value" => "40.00",
                "Complete_Percentage" => "40.0000",
                "Cancelled_Count" => "30",
                "Cancelled_Value" => "30.00",
                "Cancelled_Percentage" => "30.0000",
                "Re_scheduled_Count" => "50",
                "Re_scheduled_Value" => "50.00",
                "Re_scheduled_Percentage" => "50.0000",
                "Pending_Count" => "20",
                "Pending_Value" => "20.00",
                "Pending_Percentage" => "20.0000",
                "Initial_Count" => "30",
                "Initial_Value" => "30.00",
                "Initial_Percentage" => "30.0000",
                "Regular_Count" => "60",
                "Regular_Value" => "60.00",
                "Regular_Percentage" => "60.0000",
                "Re_service_Count" => "70",
                "Re_service_Value" => "70.00",
                "Re_service_Percentage" => "70.0000",
                "Pickup_Service_Count" => "40",
                "Pickup_Service_Value" => "40.00",
                "Pickup_Service_Percentage" => "40.0000",
                "Total_Commission" => "90.0000"
            ],
            [
                "name" => "john doe",
                "techId" => 9978,
                "Total_Count" => 1,
                "Total_Value" => "100.00",
                "At_Fault_Value" => "10.00",
                "At_Fault_Count" => "10",
                "At_Fault_Percentage" => "10.0000",
                "Complete_Count" => "40",
                "Complete_Value" => "40.00",
                "Complete_Percentage" => "40.0000",
                "Cancelled_Count" => "30",
                "Cancelled_Value" => "30.00",
                "Cancelled_Percentage" => "30.0000",
                "Re_scheduled_Count" => "50",
                "Re_scheduled_Value" => "50.00",
                "Re_scheduled_Percentage" => "50.0000",
                "Pending_Count" => "20",
                "Pending_Value" => "20.00",
                "Pending_Percentage" => "20.0000",
                "Initial_Count" => "30",
                "Initial_Value" => "30.00",
                "Initial_Percentage" => "30.0000",
                "Regular_Count" => "60",
                "Regular_Value" => "60.00",
                "Regular_Percentage" => "60.0000",
                "Re_service_Count" => "70",
                "Re_service_Value" => "70.00",
                "Re_service_Percentage" => "70.0000",
                "Pickup_Service_Count" => "40",
                "Pickup_Service_Value" => "40.00",
                "Pickup_Service_Percentage" => "40.0000",
                "Total_Commission" => "90.0000"
            ]
        ];

        $totals = [
                "name" => "first_name last_name",
                "techId" => 9978,
                "Total_Count" => 3,
                "Total_Value" => "300.00",
                "At_Fault_Value" => "30.00",
                "At_Fault_Count" => "30",
                "At_Fault_Percentage" => "30.0000",
                "Complete_Count" => "120",
                "Complete_Value" => "120.00",
                "Complete_Percentage" => "120.0000",
                "Cancelled_Count" => "430",
                "Cancelled_Value" => "330.00",
                "Cancelled_Percentage" => "330.0000",
                "Re_scheduled_Count" => "150",
                "Re_scheduled_Value" => "150.00",
                "Re_scheduled_Percentage" => "150.0000",
                "Pending_Count" => "60",
                "Pending_Value" => "60.00",
                "Pending_Percentage" => "60.0000",
                "Initial_Count" => "90",
                "Initial_Value" => "90.00",
                "Initial_Percentage" => "90.0000",
                "Regular_Count" => "180",
                "Regular_Value" => "180.00",
                "Regular_Percentage" => "180.0000",
                "Re_service_Count" => "210",
                "Re_service_Value" => "210.00",
                "Re_service_Percentage" => "210.0000",
                "Pickup_Service_Count" => "80",
                "Pickup_Service_Value" => "80.00",
                "Pickup_Service_Percentage" => "80.0000",
                "Total_Commission" => "270.0000"
        ];
        */

        if ($request->download) {
            return Excel::download(new ExportTechnicianReport($results), 'ExportTechnicianReport.csv');
        }

        $data = [
            'results'    => $results,
            'totals'     => $totals,
            'count'      => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Technician report']), $data);
    }


    public function findCompletedJobPriorToJobByIdAndOffice($jobId, $profileId, $officeId)
    {
        return PocomosJob::with('invoice')->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_pest_agreements as ppa', 'ppc.agreement_id', 'ppa.id')
            ->join('pocomos_agreements as pa', 'ppa.agreement_id', 'pa.id')
            ->join('pocomos_company_offices as pco', 'pa.office_id', 'pco.id')
            ->where('pa.office_id', $officeId)
            ->where('pocomos_jobs.status', 'complete')
            ->where('pocomos_jobs.id', '<', $jobId)
            ->whereType('RESERVICE')
            ->where('pc.profile_id', $profileId)
            ->orderBy('date_completed', 'desc')
            ->first();
    }

    public function findCompletedJobsByOfficeInRange($officeIds, $startDate, $endDate)
    {
        $completedJobs = PocomosJob::join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
            ->join('pocomos_pest_agreements as ppa', 'ppc.agreement_id', 'ppa.id')
            ->join('pocomos_agreements as pa', 'ppa.agreement_id', 'pa.id')
            ->join('pocomos_company_offices as pco', 'pa.office_id', 'pco.id')
            ->whereIn('pa.office_id', explode(',', $officeIds))
            ->where('pocomos_jobs.status', 'complete')
            ->whereBetween('pocomos_jobs.date_completed', [$startDate, $endDate])
            ->get();

        return $completedJobs;
    }
}
