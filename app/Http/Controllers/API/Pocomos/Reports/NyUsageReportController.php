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
use App\Exports\ExportNyUsageReport;

class NyUsageReportController extends Controller
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

        return $this->sendResponse(true, 'NY Usage Report filters', [
            'counties' => $counties,
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

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $countyIds      = $request->county_ids ? implode(',', $request->county_ids) : null;
        $technicianIds  = $request->technician_ids ? $request->technician_ids : [];

        $query = PocomosJobProduct::select(DB::raw('ppp.epa_code, ppp.name as productName, 
        pocomos_jobs_products.amount, ppp.unit, pj.date_completed, pco.name as countyName, ou.first_name,
         ou.last_name, pa.street, pa.suite, pa.city, pa.postal_code'))
            ->join('pocomos_pest_products as ppp', 'pocomos_jobs_products.product_id', 'ppp.id')
            ->join('pocomos_jobs as pj', 'pocomos_jobs_products.job_id', 'pj.id')
            ->leftJoin('pocomos_technicians as pt', 'pj.technician_id', 'pt.id')
            ->leftJoin('pocomos_company_office_users as pcou', 'pt.user_id', 'pcou.id')
            ->leftJoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->join('pocomos_pest_contracts as ppc', 'pj.contract_id', 'ppc.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->join('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->leftJoin('pocomos_counties as pco', 'ppc.county_id', 'pco.id')
            ->whereBetween('pj.date_completed', [$startDate, $endDate])
             ->where('pcsp.office_id', $officeId)
            ->orderBy('pj.date_completed');

        if ($countyIds) {
            $query->where('ppc.county_id', $countyIds);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ppp.epa_code', 'like', '%' . $search . '%')
                    ->orWhere('ppp.name', 'like', '%' . $search . '%')
                    ->orWhere('pocomos_jobs_products.amount', 'like', '%' . $search . '%')
                    ->orWhere('ppp.unit', 'like', '%' . $search . '%')
                    ->orWhere('pj.date_completed', 'like', '%' . $search . '%')
                    ->orWhere('pco.name', 'like', '%' . $search . '%')
                    ->orWhere('ou.first_name', 'like', '%' . $search . '%')
                    ->orWhere('ou.last_name', 'like', '%' . $search . '%')
                    ->orWhere('pa.street', 'like', '%' . $search . '%')
                    ->orWhere('pa.suite', 'like', '%' . $search . '%')
                    ->orWhere('pa.city', 'like', '%' . $search . '%')
                    ->orWhere('pa.postal_code', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $query->count();
        $query->skip($perPage * ($page - 1))->take($perPage);

        $results = $query->get();

        if ($request->download) {
            return Excel::download(new ExportNyUsageReport($results, $startDate, $endDate), 'ExportNyUsageReport.csv');
        }

        return $this->sendResponse(true, 'NY Usage Report results', [
            'results' => $results,
            'count' => $count,
        ]);
    }
}
