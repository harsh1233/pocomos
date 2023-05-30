<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use DB;
use Excel;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosJob;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Orkestra\OrkestraUser;
use App\Exports\ExportCancelledReport;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosFormLetter;
use App\Models\Pocomos\PocomosJobProduct;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosSmsFormLetter;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosInvoice;

class CreditHoldReportController extends Controller
{
    use Functions;

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $startDate = date("Y-m-d", strtotime("-2000 days"));
        $endDate =   date("Y-m-d", strtotime("+30 days"));

        $query = PocomosInvoice::select(
            '*',
            'pocomos_invoices.id',
        )
            ->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->join('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
            ->where('pcsp.office_id', $officeId)
            ->where('pocomos_invoices.balance', '>', 0)
            ->whereIn('pocomos_invoices.status', ['Due','Past due'])
            ->whereNotNull('pocomos_invoices.date_due')
            ->whereBetween('pocomos_invoices.date_due', [$startDate, $endDate])
            ->where('pcs.balance_overall', '>', 0)
            ->groupBy('pcu.id');

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $query->count();
        $query->skip($perPage * ($page - 1))->take($perPage);

        if ($request->search) {
            $search = '%'.$request->search.'%';

            $formatDate = date('Y/m/d', strtotime($request->search));
            $date = '%'.str_replace("/", "-", $formatDate).'%';

            $query->where(function ($query) use ($search, $date) {
                $query->where('date_due', 'like', $date)
                ->orWhere(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'LIKE', $search)
                ->orWhere('balance_overall', 'like', $search);
            });
        }

        $results = $query->get();

        return $this->sendResponse(true, 'Credit Hold Report Report list', [
            'results' => $results,
            'count' => $count,
        ]);

        //when = date_due
        //balance = balance_overall
    }
}
