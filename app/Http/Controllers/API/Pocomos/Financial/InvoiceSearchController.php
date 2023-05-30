<?php

namespace App\Http\Controllers\API\Pocomos\Financial;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use Excel;
use App\Exports\ExportInvoiceSearch;
use DB;

class InvoiceSearchController extends Controller
{
    use Functions;

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

        $dueDateStart = $request->due_date_start;
        $dueDateEnd = $request->due_date_end;

        $invoiceNumber = $request->invoice_number;

        $totalAmountLow = $request->total_amount_low;
        $totalAmountHigh = $request->total_amount_high;

        $balanceLow = $request->balance_low;
        $balanceHigh = $request->balance_high;

        $status = $request->status;

        $search = $request->search;

        $query = PocomosInvoice::select(
            'pocomos_invoices.id',
            'pj.id as jobId',
            'pcu.first_name',
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
                    ->whereBetween('date_due', [$dueDateStart, $dueDateEnd])
                    ->where('pcsp.office_id', $officeId);

        if ($invoiceNumber) {
            $query->where('pocomos_invoices.id', 'like', '%'.$invoiceNumber.'%');
        }

        if ($totalAmountLow) {
            $query->where('pocomos_invoices.amount_due', '>=', $totalAmountLow);
        }

        if ($totalAmountHigh) {
            $query->where('pocomos_invoices.amount_due', '<=', $totalAmountHigh);
        }

        if ($balanceLow) {
            $query->where('pocomos_invoices.balance', '>=', $balanceLow);
        }

        if ($balanceHigh) {
            $query->where('pocomos_invoices.balance', '<=', $balanceHigh);
        }

        if ($status) {
            $query->where('pocomos_invoices.status', $status);
        }

        if ($request->search) {
            $search = '%'.$request->search.'%';

            $query->where(function ($query) use ($search) {
                $query->where('pocomos_invoices.id', 'like', $search)
                ->orWhere(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'like', $search)
                ->orWhere('pocomos_invoices.amount_due', 'like', $search)
                ->orWhere('pocomos_invoices.balance', 'like', $search)
                ->orWhere('pocomos_invoices.date_due', 'like', $search)
                ->orWhere('pocomos_invoices.status', 'like', $search)
                ;
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $query->count();
        $query->skip($perPage * ($page - 1))->take($perPage);

        $result = $query->get();

        if ($request->download) {
            return Excel::download(new ExportInvoiceSearch($result), 'ExportInvoiceSearch.csv');
        }

        return $this->sendResponse(true, 'Invoice search list', [
            'result' => $result,
            'count' => $count,
        ]);
    }
}
