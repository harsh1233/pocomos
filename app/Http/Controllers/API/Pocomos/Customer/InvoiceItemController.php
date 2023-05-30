<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosPestInvoiceSetting;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosOfficeOpiniionSetting;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosPestContractsInvoice;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosCustomerState;
use App\Jobs\PaidServiceHistorySummary;
use DB;
use App\Models\Pocomos\PocomosCustomersNotifyMobilePhone;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosJobService;
use App\Models\Pocomos\PocomosDiscount;

class InvoiceItemController extends Controller
{
    use Functions;

    /**
     * Displays a form to make Bulk Invoice Items or Add Services+ Invoice Items. Or discounts.
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function bulkNewAction(Request $request)
    {
        $v = validator($request->all(), [
            'invoices' => 'required|array|exists:pocomos_invoices,id',
            'itemType' => 'required|in:service,item',
            'description' => 'required',
            'price' => 'required',
            'sales_tax' => 'required',
            'service_type_id' => 'exists:pocomos_pest_contract_service_types,id',
            'value_type' => 'required|in:static,Percent',
            'type' => 'required|in:Regular,Discount,add_tax',
            'discount_id' => 'nullable|exists:pocomos_discounts,id',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        foreach ($request->invoices as $invoice) {
            $find_invoice = PocomosInvoice::findOrFail($invoice);
            $find_job_id = PocomosJob::where('invoice_id', $invoice)->first();

            if ($request->itemType == 'service') {
                // Add entry into invoice items table
                $invoice_item = [];
                $invoice_item['invoice_id'] = $invoice;
                $invoice_item['description'] = $request->description;
                $invoice_item['price'] = $request->price;
                $invoice_item['active'] = '1';
                $invoice_item['sales_tax'] = $request->sales_tax;
                $invoice_item['tax_code_id'] = $find_invoice->tax_code_id;
                $invoice_item['type'] = 'Regular';
                $invoice_item = PocomosInvoiceItems::create($invoice_item);


                $job_service = [];
                $job_service['service_type_id'] = $request->service_type_id;
                $job_service['job_id'] = $find_job_id->id ?? null;
                $job_service['active'] = '1';
                $insert_job = PocomosJobService::create($job_service);

                $find_invoice->amount_due = $find_invoice->amount_due + $request->price;
                $find_invoice->balance = $find_invoice->balance + $request->price;
                $find_invoice->save();
            } elseif ($request->itemType == 'item') {
                if ($request->type == 'Regular') {
                    // Add entry into invoice items table
                    $invoice_item = [];
                    $invoice_item['invoice_id'] = $invoice;
                    $invoice_item['description'] = $request->description;
                    $invoice_item['price'] = $request->price;
                    $invoice_item['active'] = '1';
                    $invoice_item['sales_tax'] = $request->sales_tax;
                    $invoice_item['tax_code_id'] = $find_invoice->tax_code_id;
                    $invoice_item['type'] = 'Regular';
                    $invoice_item = PocomosInvoiceItems::create($invoice_item);

                    $find_invoice->amount_due = $find_invoice->amount_due + $request->price;
                    $find_invoice->balance = $find_invoice->balance + $request->price;
                    $find_invoice->save();
                } elseif ($request->type == 'Discount') {
                    $find_invoice = PocomosInvoice::findOrFail($invoice);

                    $valueType = $request->value_type ?? 'static';
                    $invoice_item['description'] = $request->description;

                    if (isset($request->discount_id)) {
                        $discount = PocomosDiscount::where('id', $request->discount_id)->first();
                        if ($request->discount_id != null) {
                            if ($valueType == 'static') {
                                $invoice_item['description'] = $discount->name . ' ' . ':' . ' ' . $request->description;
                            } else {
                                $invoice_item['description'] =  $request->price . '%' . ' ' . ':' . ' ' . $discount->name . ' ' . ':' . ' ' . $request->description;
                            }
                        }
                    }


                    $invoice_item['price'] = $request->price;
                    $invoice_item['itemType'] = 'Discount';

                    $result = $this->addInvoiceItems($find_invoice, $invoice_item, $valueType);
                }
            }
        }
        return $this->sendResponse(true, __('strings.update', ['name' => 'The invoices have been']));
    }


    public function listinvoices(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->firstOrFail();

        $res = DB::select(DB::raw("SELECT pc.id, pc.date_due, pc.amount_due, pc.status, pc.balance,cspa.name
        FROM pocomos_invoices AS pc
        LEFT JOIN pocomos_jobs AS cso ON cso.invoice_id = pc.id
        JOIN pocomos_contracts AS csp ON csp.id = pc.contract_id
        JOIN pocomos_agreements AS cspa ON cspa.id = csp.agreement_id
        JOIN pocomos_customer_sales_profiles AS cspp ON cspp.id = csp.profile_id
        WHERE cspp.id  = '$profile->id' AND pc.status NOT IN ('Paid','Paid') AND (cso.status NOT IN ('Cancelled','Cancelled') OR cso.invoice_id is null) ORDER BY pc.date_due ASC"));

        return $this->sendResponse(true, 'List of invoices', $res);
    }


    // bulk edit
    public function editBulk(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'job_ids' => 'required|array|exists:pocomos_jobs,id',
            'type' => 'required|in:Initial,Regular,Inspection,Re-service,Follow-up,Pickup Service',
            'status' => 'required|in:Pending,Complete,Cancelled,Re-scheduled',
            'amountDue' => 'required',
            'technician_id' => 'exists:pocomos_technicians,id',
            'completedDate' => 'nullable',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        foreach ($request->job_ids as $job_id) {
            // find job
            $find_job = PocomosJob::findOrFail($job_id);
            $find_invoice = PocomosInvoice::findOrFail($find_job->invoice_id);
            $find_invoice_item = PocomosInvoiceItems::where('invoice_id', $find_job->invoice_id)->first();

            $find_job->status = $request->status;
            $find_job->type = $request->type;

            $find_invoice->amount_due = $request->amountDue;

            $find_invoice_item->price = $request->amountDue;

            if ($request->status == "Cancelled") {
                $find_job->date_cancelled =  date('Y-m-d H:i:s');
            }

            if ($request->status == "Complete") {
                $find_job->technician_id =  $request->technician_id ?? null;
                $find_job->date_completed =  $request->completedDate ?? null;
                $find_job->force_completed =  1;
                $find_invoice->date_due =  date('Y-m-d');
            }

            $find_job->save();
            $find_invoice->save();
            $find_invoice_item->save();
        }
        return $this->sendResponse(true, 'Bulk edited successfully');
    }


    // Displays a form to make a Discount InvoiceItem
    public function discountAction(Request $request)
    {
        $v = validator($request->all(), [
            'discount_id' => 'nullable|exists:pocomos_discounts,id',
            'invoice_id' => 'required|exists:pocomos_invoices,id',
            'description' => 'required',
            'value_type' => 'required|in:static,Percent',
            'price' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_invoice = PocomosInvoice::findOrFail($request->invoice_id);

        $invoice_item['description'] = $request->description;

        $valueType = $request->value_type ?? 'static';

        if (isset($request->discount_id)) {
            $discount = PocomosDiscount::where('id', $request->discount_id)->first();
            if ($request->discount_id != null) {
                if ($valueType == 'static') {
                    $invoice_item['description'] = $discount->name . ' ' . ':' . ' ' . $request->description;
                } else {
                    $invoice_item['description'] =  $request->price . '%' . ' ' . ':' . ' ' . $discount->name . ' ' . ':' . ' ' . $request->description;
                }
            }
        }


        $invoice_item['price'] = $request->price;
        $invoice_item['itemType'] = 'Discount';

        $result = $this->addInvoiceItems($find_invoice, $invoice_item, $valueType);

        return $this->sendResponse(true, 'The discount has been applied successfully.', $invoice_item);
    }
}
