<?php

namespace App\Http\Controllers\API\Pocomos\Financial;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosTag;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosOfficeOpiniionSetting;
use App\Models\Pocomos\PocomosDocusendConfiguration;
use App\Models\Pocomos\PocomosFormLetter;
use App\Models\Pocomos\PocomosInvoice;
use App\Jobs\ExportUnpaidInvoicesJob;
use App\Jobs\UnpaidIndividualInvoice;
use App\Jobs\UnpaidInvoiceSummary;
use App\Jobs\PaidInvoiceSummary;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosUserTransaction;
use App\Models\Pocomos\PocomosInvoiceTransaction;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Orkestra\OrkestraTransaction;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use App\Exports\ExportPrepaidInvoice;
use Excel;
use PDF;
use Illuminate\Support\Facades\Storage;

class PrepaidInvoiceController extends Controller
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

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $query = PocomosInvoice::select(
            '*',
            'pocomos_invoices.*',
            'pj.id AS job_id',
            'pcu.id AS customerId',
            'pit.transaction_id'
        )
            ->leftJoin('pocomos_jobs as pj', 'pocomos_invoices.id', 'pj.invoice_id')
            ->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->join('pocomos_invoice_transactions as pit', 'pocomos_invoices.id', 'pit.invoice_id')
            ->whereBetween('pocomos_invoices.date_due', [$startDate, $endDate])
            ->where('pcsp.office_id', $officeId);

        if ($request->invoice_status) {
            $query->where('pocomos_invoices.status', $request->invoice_status);
        }

        if ($request->search) {
            $search = '%'.$request->search.'%';

            $formatDate = date('Y/m/d', strtotime($request->search));
            $date = str_replace("/", "-", $formatDate);

            $query->where(function ($query) use ($search, $date) {
                $query->where('pocomos_invoices.id', 'like', $search)
                 ->orWhere(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'LIKE', $search)
                 ->orWhere('amount_due', 'like', $search)
                 ->orWhere('pocomos_invoices.balance', 'like', $search)
                //  ->orWhere('pocomos_invoices.date_due', 'like', '%'.$date.'%')
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

        $prepaidInvoices = $query->get();

        if ($request->download) {
            return Excel::download(new ExportPrepaidInvoice($prepaidInvoices), 'ExportPrepaidInvoice.csv');
        }

        return $this->sendResponse(true, 'Prepaid invoices', [
            'prepaid_invoices' => $prepaidInvoices,
            'count' => $count
        ]);
    }
}
