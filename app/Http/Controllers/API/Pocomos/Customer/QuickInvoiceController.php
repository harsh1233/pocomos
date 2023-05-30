<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosJob;
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
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosInvoice;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosTaxCode;
use Carbon\Carbon;
use App\Models\Pocomos\PocomosPestContractsPest;
use App\Models\Pocomos\PocomosCustomersFile;

class QuickInvoiceController extends Controller
{
    use Functions;

    /**
     * API for List of quick invoices
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|integer|min:1',
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

        $contract = PocomosContract::where('profile_id', $profile->id)->pluck('id');

        $pocomos_invoice = PocomosInvoice::whereIn('contract_id', $contract)->where('status', '!=', 'Cancelled')->orWhere('status', '!=', 'Paid')->get();

        $pocomos_invoice->map(function ($invoice_data) {
            // find invoice id in pocomos jobs table
            // logic is in invoice we are showing quick invoice data aswell as quick add service data

            $find_invoice_in_job = PocomosJob::where('invoice_id', $invoice_data->id)->first();
            if ($find_invoice_in_job) {
                $invoice_data->type = 'Service (' . $find_invoice_in_job->status . ' )';
            } else {
                $invoice_data->type = 'Misc.';
            }
        });
        return $this->sendResponse(true, 'List of upcoming invoices', $pocomos_invoice);
    }

    /**
     * API for Edit Invoice Due Date
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $v = validator($request->all(), [
            'invoice_id' => 'required',
            'due_date' => 'required',
            'same_for_other' => 'nullable'
        ]);
        $pocomos_invoice = PocomosInvoice::where('id', $request->invoice_id)->first();
        $pocomos_invoice->date_due = $request->due_date;
        $pocomos_invoice->save();
        return $this->sendResponse(true, 'Due date updated', $pocomos_invoice);
    }
}
