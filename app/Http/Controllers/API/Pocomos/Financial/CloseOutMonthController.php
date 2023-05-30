<?php

namespace App\Http\Controllers\API\Pocomos\Financial;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class CloseOutMonthController extends Controller
{
    use Functions;

    public function list(Request $request)
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

        $dateDueStart = date('Y-m-01');
        $dateDueEnd = date('Y-m-t');

        $search = $request->search;

        $query = PocomosInvoice::select(
            'pocomos_invoices.id',
            'pj.id as jobId',
            'pcu.first_name',
            'pcu.last_name',
            'pcu.last_name',
            'pcu.id as cust_id',
            'pocomos_invoices.amount_due',
            'pocomos_invoices.balance',
            'pocomos_invoices.date_due',
            'pocomos_invoices.status'
        )
                    ->leftJoin('pocomos_jobs as pj', 'pocomos_invoices.id', 'pj.invoice_id')
                    ->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
                    ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                    ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
                    ->whereBetween('pocomos_invoices.date_due', [$dateDueStart, $dateDueEnd])
                    ->whereClosed(0)
                    ->where('pcsp.office_id', $officeId);

        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('pocomos_invoices.id', 'like', '%' . $request->search . '%')
                ->orWhere(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'LIKE', "%".$request->search."%")
                ->orWhere('pocomos_invoices.amount_due', 'like', '%' . $request->search . '%')
                ->orWhere('pocomos_invoices.balance', 'like', '%' . $request->search . '%')
                ->orWhere('pocomos_invoices.date_due', 'like', '%' . $request->search . '%')
                ->orWhere('pocomos_invoices.status', 'like', '%' . $request->search . '%')
                ;
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $query->count();
        $query->skip($perPage * ($page - 1))->take($perPage);

        $invoices = $query->get();

        // return $this->sendResponse(true, 'Closeout month list', [$invoices]);

        return $this->sendResponse(true, 'Closeout month list', [
            'invoices' => $invoices,
            'count' => $count,
        ]);
    }

    public function closeMonth(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'year_month' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $dateDueStart = date('Y-m-01', strtotime('01-'.$request->year_month));
        $dateDueEnd = date('Y-m-t', strtotime('01-'.$request->year_month));

        $invoices   = PocomosInvoice::select(
            'pocomos_invoices.id',
            'pj.id as jobId',
            'pcu.first_name',
            'pcu.last_name',
            'pcu.last_name',
            'pcu.id as cust_id',
            'pocomos_invoices.amount_due',
            'pocomos_invoices.balance',
            'pocomos_invoices.date_due',
            'pocomos_invoices.status'
        )
                ->leftJoin('pocomos_jobs as pj', 'pocomos_invoices.id', 'pj.invoice_id')
                ->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
                ->whereBetween('pocomos_invoices.date_due', [$dateDueStart, $dateDueEnd])
                ->whereClosed(0)
                ->where('pcsp.office_id', $officeId)
                ->update(['closed'=>1]);

        return $this->sendResponse(true, 'Month '.$request->year_month.' closed');
    }
}
