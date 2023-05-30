<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosJobProduct;
use App\Models\Pocomos\PocomosCounty;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use Excel;
use App\Exports\ExportUsageReport;

class UsageReportController extends Controller
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
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $counties = PocomosCounty::whereOfficeId($officeId)->whereActive(true)->get();

        $ids = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereActive(true)->pluck('id');

        $technicians = PocomosTechnician::with('user_detail.user_details:id,first_name,last_name')->whereIn('user_id', $ids)->whereActive(true)->get();

        return $this->sendResponse(true, 'Usage Report filters', [
            'counties'      => $counties,
            'technicians'   => $technicians,
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
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $countyIds      = $request->county_ids ?? [];
        $technicianIds      = $request->technician_ids ? $request->technician_ids : [];

        $query = PocomosJobProduct::select('ppp.name as product_name',
                'ppp.epa_code',
                'ppp.unit',
                DB::raw(
                    'COUNT(pocomos_jobs_products.id) AS applications',
                 ),
                DB::raw(
                    'SUM(pocomos_jobs_products.amount) AS amount'
            ))
            ->join('pocomos_pest_products as ppp', 'pocomos_jobs_products.product_id', 'ppp.id')
            ->join('pocomos_jobs as pj', 'pocomos_jobs_products.job_id', 'pj.id')
            ->join('pocomos_pest_contracts as ppc', 'pj.contract_id', 'ppc.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->leftJoin('pocomos_route_slots as prs', 'pj.slot_id', 'pcsp.id')
            ->leftJoin('pocomos_routes as pr', 'prs.route_id', 'pr.id')
             ->whereBetween('pj.date_completed', [$startDate, $endDate])
            ->where('pcsp.office_id', $officeId)
            ->groupBy('pocomos_jobs_products.product_id');

        if ($countyIds) {
            $query->whereIn('ppc.county_id', $countyIds);
        }

        if ($technicianIds) {
            $query->whereIn('pr.technician_id', $technicianIds);
        }

        /* if ($request->search) {
            $search = $request->search;
            $query->where(function ($query) use ($search) {
                $query->where('ppp.name', 'like', '%' . $search . '%')
                    ->orWhere('ppp.epa_code', 'like', '%' . $search . '%')
                    ->orWhere('ppp.unit', 'like', '%' . $search . '%')
                    // ->orWhere('COUNT(pocomos_jobs_products.id)', 'like', '%' . $search . '%')
                    // ->orWhere('SUM(pocomos_jobs_products.amount)', 'like', '%' . $search . '%');
            });
        } */

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $query->get()->count();
        $query->skip($perPage * ($page - 1))->take($perPage);
        $results = $query->get();

        $numberOfJobs = $this->getNumberOfJobs($startDate, $endDate, $officeId, $countyIds, $technicianIds);

        if ($request->download) {
            return Excel::download(new ExportUsageReport($results, $numberOfJobs, $startDate, $endDate), 'ExportUsageReport.csv');
        }

        return $this->sendResponse(true, 'Usage Report results', [
            'results' => $results,
            'count' => $count,
            'number_of_jobs' => $numberOfJobs,
        ]);
    }


    public function getNumberOfJobs($startDate, $endDate, $officeId, $countyIds, $technicianIds)
    {
        $query = PocomosJobProduct::select(DB::raw('COUNT(pocomos_jobs_products.id) AS jobs'))
            ->join('pocomos_jobs as pj', 'pocomos_jobs_products.job_id', 'pj.id')
            ->join('pocomos_pest_contracts as ppc', 'pj.contract_id', 'ppc.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->leftJoin('pocomos_route_slots as prs', 'pj.slot_id', 'pcsp.id')
            ->leftJoin('pocomos_routes as pr', 'prs.route_id', 'pr.id')
            ->whereBetween('pj.date_completed', [$startDate, $endDate])
            ->where('pcsp.office_id', $officeId)
            ->groupBy('pocomos_jobs_products.job_id');

        if ($countyIds) {
            $query->whereIn('ppc.county_id', $countyIds);
        }

        if (count($technicianIds)) {
            $query->whereIn('pr.technician_id', $technicianIds);
        }

        return $results = $query->get();
    }
}
