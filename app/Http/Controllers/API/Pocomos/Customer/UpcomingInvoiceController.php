<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosNote;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosCustomersNote;
use App\Models\Pocomos\PocomosCustomerState;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\PocomosPestContractsPest;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosCustomersFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosInvoice;

class UpcomingInvoiceController extends Controller
{
    use Functions;

    /**
     * API for list upcoming invoice
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function upcomingInvoices(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $contract = PocomosContract::where('profile_id', $profile->id)->first();

        $sql = "SELECT pi.id,pi.date_due,pi.balance,pi.status,pag.name,pj.type as job_type,pj.status as job_status, 
                    pc.id as contract_id, pj.id as job_id
        FROM pocomos_invoices AS pi
        JOIN pocomos_contracts AS pc ON  pc.id = pi.contract_id
        JOIN pocomos_agreements  AS pag ON pag.id   = pc.agreement_id
        JOIN pocomos_customer_sales_profiles AS pcsp ON pcsp.id  = pc.profile_id
        LEFT JOIN pocomos_jobs AS pj ON pj.invoice_id  = pi.id
        WHERE (pi.status  NOT IN ('Paid', 'Cancelled')) AND pcsp.id = $profile->id
        ";

        if ($request->search) {
            $search = "'%" . $request->search . "%'";
            $sql .= ' AND (pi.id LIKE ' . $search . ' OR pi.date_due LIKE ' . $search . ' OR pi.balance LIKE ' . $search . ' 
            OR pi.status LIKE ' . $search . ' OR pag.name LIKE ' . $search . ' OR pj.type LIKE ' . $search . ' 
            OR pj.status LIKE ' . $search . ') OR pc.id LIKE ' . $search . '';
        }

        $sql .= " ORDER BY pi.date_created DESC";

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $batches = DB::select(DB::raw(($sql)));

        return $this->sendResponse(true, 'List', [
            'upcoming_invoices' => $batches,
            'count' => $count,
        ]);
    }

    /**
     * API for Edit Invoice Due Date
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function editduedate(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'invoice_id' => 'required|exists:pocomos_invoices,id',
            'dateDue' => 'required',
            'applyDate' => 'boolean|required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_invoice = PocomosInvoice::findOrFail($request->invoice_id);

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        if ($request->applyDate == 0) {
            $find_invoice->date_due = $request->dateDue;
            $find_invoice->save();
        } elseif ($request->applyDate == 1) {
            // $data = DB::select(DB::raw("SELECT pc.id
            // FROM pocomos_invoices AS pc
            // JOIN pocomos_contracts AS pss ON  pss.id = pc.contract_id
            // JOIN pocomos_customer_sales_profiles AS cca ON cca.id  = pss.profile_id
            // WHERE pc.status = 'Not Sent' AND cca.customer_id = $request->customer_id"));

            $data = DB::select(DB::raw("SELECT pc.id
        FROM pocomos_invoices AS pc
        JOIN pocomos_contracts AS pss ON  pss.id = pc.contract_id
        JOIN pocomos_agreements  AS pcs ON pcs.id   = pss.agreement_id
        JOIN pocomos_customer_sales_profiles AS cca ON cca.id  = pss.profile_id
        LEFT JOIN pocomos_jobs AS ccj ON ccj.invoice_id  = pc.id
        WHERE (pc.status  NOT IN ('Paid, Cancelled')) AND cca.id = $profile->id
        ORDER BY pc.date_due  ASC"));

            if (!$data) {
                return $this->sendResponse(false, 'Unable to find the Invoice.');
            }

            foreach ($data as $invoice) {
                $find_invoice = PocomosInvoice::findOrFail($invoice->id);
                $find_invoice->date_due = $request->dateDue;
                $find_invoice->save();
            }
        }

        return $this->sendResponse(true, 'Due date updated successfully', $find_invoice);
    }
}
