<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\Recruitement\OfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosRecruitStatus;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Orkestra\OrkestraAccount;
use DB;
use App\Exports\PaymentHistory;
use App\Models\Pocomos\PocomosBill;
use App\Models\Pocomos\PocomosSubCustomer;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosBillGroup;
use App\Models\Pocomos\PocomosBillJob;
use App\Models\Pocomos\PocomosBillInvoice;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosInvoicePayment;
use App\Models\Pocomos\PocomosInvoiceInvoicePayment;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosJob;
use Illuminate\Support\Facades\Mail;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DateTime;
use PDF;
use Illuminate\Support\Facades\Storage;

class BillController extends Controller
{
    use Functions;


    /**
     * Retreives items that are currently valid to be included on a bill
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function validItemsAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sub_customers = PocomosSubCustomer::where('parent_id', $request->customer_id)->pluck('child_id')->toArray();

        array_push($sub_customers, $request->customer_id);

        $sub_customers = $this->convertArrayInStrings($sub_customers);

        $resc = DB::select(DB::raw("SELECT csp.first_name,csp.last_name,csp.id
        FROM pocomos_customers AS csp
        WHERE id  IN($sub_customers) "));

        $resc['0']->parent = 1;

        // $resp = DB::select(DB::raw("SELECT csp.first_name,csp.last_name,csp.id
        // FROM pocomos_customers AS csp
        // WHERE id = $request->customer_id "));

        // $data = [
        //     'child' => $resc,
        //     'parent' => $resp
        // ];

        return $this->sendResponse(true, 'List of child and parent customer', $resc);
    }


    public function listjobs(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        $res = DB::select(DB::raw("SELECT pc.id,pc.status,pc.date_scheduled,pc.original_date_scheduled,cso.amount_due
        FROM pocomos_jobs AS pc
        JOIN pocomos_invoices AS cso ON cso.id  = pc.invoice_id
        JOIN pocomos_pest_contracts AS csp ON csp.id = pc.contract_id
        JOIN pocomos_contracts AS csps ON csps.id = csp.contract_id
        JOIN pocomos_customer_sales_profiles AS cspp ON cspp.id = csps.profile_id
        JOIN pocomos_customers AS pcpc ON pcpc.id = cspp.customer_id
        WHERE cspp.id  = '$profile->id' AND cso.status  IN ('Paid', 'Cancelled')"));

        return $this->sendResponse(true, 'List of Job', $res);
    }


    public function listinvoices(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        $res = DB::select(DB::raw("SELECT pc.id, pc.date_due, pc.amount_due, pc.status, pc.balance
        FROM pocomos_invoices AS pc
        LEFT JOIN pocomos_jobs AS cso ON cso.invoice_id = pc.id
        JOIN pocomos_contracts AS csp ON csp.id = pc.contract_id
        JOIN pocomos_customer_sales_profiles AS cspp ON cspp.id = csp.profile_id
        WHERE cspp.id  = '$profile->id'  ORDER BY pc.date_due DESC"));

        return $this->sendResponse(true, 'List of invoices', $res);
    }
    /**
     * API for list payment history data
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        if (!$customer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to locate Customer Profile.');
        }

        $jobs = PocomosBill::where('profile_id', $profile->id);

        if ($request->search) {
            $search = $request->search;
            $search = $search;
            $date = new DateTime(strtotime($search));
            $date = $date->format('Y-m-d');

            $jobs->where(function ($query) use ($search, $date) {
                $query->where('id', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('date_created', 'like', '%' . $date . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $jobs->count();
        $jobs->skip($perPage * ($page - 1))->take($perPage);

        $jobs = $jobs->get();

        return $this->sendResponse(true, 'List', [
            'Bills' => $jobs,
            'count' => $count,
        ]);
    }

    public function billinfo(Request $request)
    {
        $v = validator($request->all(), [
            'bill_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosBillGroup = PocomosBillGroup::where('bill_id', $request->bill_id)->get();
        $result_array = array();

        foreach ($PocomosBillGroup as $val) {
            $job_array = array();

            $profileofcustomer = PocomosCustomerSalesProfile::findOrFail($val->profile_id);

            $customer = PocomosCustomer::where('id', $profileofcustomer->customer_id)->select('first_name', 'last_name')->first();

            $username = $customer['first_name'] . " " . $customer['last_name'];

            $job_array['name'] = $username;
            $job_array['customer_id'] = $profileofcustomer->customer_id;

            $PocomosBillJob = PocomosBillJob::where('bill_group_id', $val->id)->get();

            $i = 0;
            $j = 0;
            foreach ($PocomosBillJob as $value) {
                $job_array['job_data'][$i] = PocomosJob::with('invoice_detail_bill')->where('id', $value->job_id)->select('id', 'invoice_id', 'date_scheduled', 'status')->first();

                // array_push($job_array, $demo);
                $i++;
            }

            $PocomosBillInvoice = PocomosBillInvoice::where('bill_group_id', $val->id)->get();

            foreach ($PocomosBillInvoice as $values) {
                $job_array['invoice_data'][$j]  = PocomosInvoice::where('id', $values->invoice_id)->select('id', 'date_due', 'balance', 'status')->first();

                // array_push($job_array, $demo_data);
                $j++;
            }
            array_push($result_array, $job_array);
        }

        return $this->sendResponse(true, 'List of Bills', $result_array);
    }

    /**
     * API for Creates a Bill from request
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function createAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_pest_contracts,id',
            'user_details' => 'required|array',
            'user_details.*.customer_id' => 'required|exists:pocomos_customers,id', //customer_id
            'invoice_detail' => 'array',
            'job_detail' => 'array',
            'user_details.*.invoice_detail.*.invoice_id' => 'exists:pocomos_invoices,id', //pocomos_invoices
            'user_details.*.job_detail.*.job_id' => 'exists:pocomos_jobs,id', //job_id
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'emails' => 'array',
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
            return $this->sendResponse(false, 'Unable to locate Profile Entity.');
        }

        $pestContract = PocomosPestContract::findOrFail($request->contract_id);
        $salesContract = PocomosContract::where('id', $pestContract->contract_id)->first();

        $bill['profile_id'] = $profile->id;
        $bill['status'] = 'Not Paid';
        $bill['active'] = 1;
        $bill['name'] = '';
        $PocomosBill = PocomosBill::create($bill);

        foreach ($request->user_details as $user_details) {
            $profile = PocomosCustomerSalesProfile::where('customer_id', $user_details['customer_id'])->first();

            $group['bill_id'] = $PocomosBill->id;
            $group['profile_id'] = $profile->id;
            $group['active'] = 1;
            $BillGroup = PocomosBillGroup::create($group);

            if (isset($user_details['job_detail'])) {
                foreach ($user_details['job_detail'] as $key => $job_detail) {
                    $billjob['job_id']  = $job_detail['job_id'];
                    $billjob['bill_group_id'] = $BillGroup->id;
                    $PocomosBillJob = PocomosBillJob::create($billjob);
                }
            }

            if (isset($user_details['new_item_detail'])) {
                foreach ($user_details['new_item_detail'] as $key => $new_item_detail) {
                    // create invoice
                    $invoice_input['contract_id'] = $pestContract->contract_id;
                    $invoice_input['date_due'] = date('Y-m-d', strtotime('+2 year'));
                    $invoice_input['amount_due'] = $new_item_detail['price'];
                    $invoice_input['status'] = 'Due';
                    $invoice_input['balance'] = $new_item_detail['price'];
                    $invoice_input['active'] = true;
                    $invoice_input['sales_tax'] = $salesContract->sales_tax ?? null;
                    $invoice_input['tax_code_id'] =  $salesContract->tax_code_id ?? null;
                    $invoice_input['closed'] = false;
                    $pocomos_invoice = PocomosInvoice::create($invoice_input);

                    $billinvoice['invoice_id']  =  $pocomos_invoice->id;
                    $billinvoice['bill_group_id'] = $BillGroup->id;
                    $PocomosBillInvoice = PocomosBillInvoice::create($billinvoice);

                    $item['tax_code_id'] = $salesContract->tax_code_id ?? null;
                    $item['sales_tax'] = $salesContract->sales_tax ?? null;
                    $item['description'] = $new_item_detail['description'];
                    $item['price'] = $new_item_detail['price'];
                    $item['active'] = true;
                    $item['invoice_id'] = $pocomos_invoice->id;
                    $item['type'] = "";
                    $PocomosInvoiceItems = PocomosInvoiceItems::create($item);


                    $items['date_scheduled'] = date('Y-m-d');
                    $items['amount_in_cents'] = 0;
                    $items['status'] = "Unpaid";
                    $items['active'] = true;
                    $PocomosInvoicePayment = PocomosInvoicePayment::create($items);

                    $itempayment['invoice_id'] = $pocomos_invoice->id;
                    $itempayment['payment_id'] = $PocomosInvoicePayment->id;
                    $PocomosInvoiceInvoicePayment = PocomosInvoiceInvoicePayment::create($itempayment);
                }
            }


            if (isset($user_details['invoice_detail'])) {
                foreach ($user_details['invoice_detail'] as $key => $invoice_detail) {
                    $billinvoice['invoice_id']  = $invoice_detail['invoice_id'];
                    $billinvoice['bill_group_id'] = $BillGroup->id;
                    $PocomosBillInvoice = PocomosBillInvoice::create($billinvoice);
                }
            }
        }

        $office = PocomosCompanyOffice::where('id', $request->office_id)->first();
        $office_email = unserialize($office->email);

        if (isset($office_email[0])) {
            $from = $office_email[0];
        } else {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        if ($request->emails) {
            foreach ($request->emails as $emails) {
                $data = array(
                    'customer' => $customer,
                );

                $bill_id = $request->bill_id;
                $subject =   'Bill' . ' #' . $bill_id;

                Mail::send('emails.attach_bill', ['data' => $data], function ($message) use ($subject, $bill_id, $emails, $from) {
                    $message->from($from);
                    $message->to($emails);
                    $message->subject($subject);
                    //$message->attachData($pdf->output(),  $bill_id . "_bill.pdf");
                    // $message->attach(storage_path('app/public') . '/pdf/Customers.csv', [
                    //     'as' => 'Customers.csv',
                    //     'mime' => 'application/csv',
                    // ]);
                });
            }
        }

        return $this->sendResponse(true, 'Bill Created Successfully.');
    }

    /**
     * API for Edit a Bill from request
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function editAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_pest_contracts,id',
            'user_details' => 'required|array',
            'user_details.*.customer_id' => 'required|exists:pocomos_customers,id', //customer_id
            'invoice_detail' => 'array',
            'job_detail' => 'array',
            'bill_id' => 'required|exists:pocomos_bills,id',
            'user_details.*.invoice_detail.*.invoice_id' => 'exists:pocomos_invoices,id', //pocomos_invoices
            'user_details.*.job_detail.*.job_id' => 'exists:pocomos_jobs,id', //job_id
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'emails' => 'array',
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
            return $this->sendResponse(false, 'Unable to locate Profile Entity.');
        }

        $pestContract = PocomosPestContract::findOrFail($request->contract_id);
        $salesContract = PocomosContract::where('id', $pestContract->contract_id)->first();

        //$PocomosBillGroup = PocomosBillGroup::where('bill_id', $request->bill_id)->get();
        $PocomosBillGroup = PocomosBillGroup::where('bill_id', $request->bill_id)->pluck('id')->toArray();

        PocomosBillJob::whereIn('bill_group_id', $PocomosBillGroup)->delete();
        PocomosBillInvoice::whereIn('bill_group_id', $PocomosBillGroup)->delete();
        PocomosBillGroup::where('bill_id', $request->bill_id)->delete();

        foreach ($request->user_details as $user_details) {
            $profile = PocomosCustomerSalesProfile::where('customer_id', $user_details['customer_id'])->first();

            $group['bill_id'] = $request->bill_id;
            $group['profile_id'] = $profile->id;
            $group['active'] = 1;
            $BillGroup = PocomosBillGroup::create($group);

            if (isset($user_details['job_detail'])) {
                foreach ($user_details['job_detail'] as $key => $job_detail) {
                    $billjob['job_id']  = $job_detail['job_id'];
                    $billjob['bill_group_id'] = $BillGroup->id;
                    $PocomosBillJob = PocomosBillJob::create($billjob);
                }
            }

            if (isset($user_details['new_item_detail'])) {
                foreach ($user_details['new_item_detail'] as $key => $new_item_detail) {
                    // create invoice
                    $invoice_input['contract_id'] = $pestContract->contract_id;
                    $invoice_input['date_due'] = date('Y-m-d', strtotime('+2 year'));
                    $invoice_input['amount_due'] = $new_item_detail['price'];
                    $invoice_input['status'] = 'Due';
                    $invoice_input['balance'] = $new_item_detail['price'];
                    $invoice_input['active'] = true;
                    $invoice_input['sales_tax'] = $salesContract->sales_tax ?? null;
                    $invoice_input['tax_code_id'] =  $salesContract->tax_code_id ?? null;
                    $invoice_input['closed'] = false;
                    $pocomos_invoice = PocomosInvoice::create($invoice_input);

                    $billinvoice['invoice_id']  =  $pocomos_invoice->id;
                    $billinvoice['bill_group_id'] = $BillGroup->id;
                    $PocomosBillInvoice = PocomosBillInvoice::create($billinvoice);

                    $item['tax_code_id'] = $salesContract->tax_code_id ?? null;
                    $item['sales_tax'] = $salesContract->sales_tax ?? null;
                    $item['description'] = $new_item_detail['description'];
                    $item['price'] = $new_item_detail['price'];
                    $item['active'] = true;
                    $item['invoice_id'] = $pocomos_invoice->id;
                    $item['type'] = "";
                    $PocomosInvoiceItems = PocomosInvoiceItems::create($item);


                    $items['date_scheduled'] = date('Y-m-d');
                    $items['amount_in_cents'] = 0;
                    $items['status'] = "Unpaid";
                    $items['active'] = true;
                    $PocomosInvoicePayment = PocomosInvoicePayment::create($items);

                    $itempayment['invoice_id'] = $pocomos_invoice->id;
                    $itempayment['payment_id'] = $PocomosInvoicePayment->id;
                    $PocomosInvoiceInvoicePayment = PocomosInvoiceInvoicePayment::create($itempayment);
                }
            }


            if (isset($user_details['invoice_detail'])) {
                foreach ($user_details['invoice_detail'] as $key => $invoice_detail) {
                    $billinvoice['invoice_id']  = $invoice_detail['invoice_id'];
                    $billinvoice['bill_group_id'] = $BillGroup->id;
                    $PocomosBillInvoice = PocomosBillInvoice::create($billinvoice);
                }
            }
        }

        $office = PocomosCompanyOffice::where('id', $request->office_id)->first();
        $office_email = unserialize($office->email);

        if (isset($office_email[0])) {
            $from = $office_email[0];
        } else {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        if ($request->emails) {
            foreach ($request->emails as $emails) {
                $data = array(
                    'customer' => $customer,
                );

                $bill_id = $request->bill_id;
                $subject =   'Bill' . ' #' . $bill_id;

                Mail::send('emails.attach_bill', ['data' => $data], function ($message) use ($subject, $bill_id, $emails, $from) {
                    $message->from($from);
                    $message->to($emails);
                    $message->subject($subject);
                    //$message->attachData($pdf->output(),  $bill_id . "_bill.pdf");
                    // $message->attach(storage_path('app/public') . '/pdf/Customers.csv', [
                    //     'as' => 'Customers.csv',
                    //     'mime' => 'application/csv',
                    // ]);
                });
            }
        }

        return $this->sendResponse(true, 'Bill Edited Successfully.');
    }

    public function downloadbill()
    {
        $PocomosBillGroup = PocomosBillGroup::where('bill_id',  $_GET['bill_id'])->get();

        $customer = PocomosCustomer::where('id', $_GET['customer_id'])->first();

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $_GET['customer_id'])->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to locate Profile Entity.');
        }

        $PocomosBillGroup = PocomosBillGroup::where('bill_id', $_GET['customer_id'])->get();
        $result_array = array();

        foreach ($PocomosBillGroup as $val) {
            $job_array = array();

            $profileofcustomer = PocomosCustomerSalesProfile::findOrFail($val->profile_id);

            $customer = PocomosCustomer::where('id', $profileofcustomer->customer_id)->select('first_name', 'last_name')->first();

            $username = $customer['first_name'] . " " . $customer['last_name'];

            $job_array['name'] = $username;
            $job_array['customer_id'] = $profileofcustomer->customer_id;

            $PocomosBillJob = PocomosBillJob::where('bill_group_id', $val->id)->get();

            $i = 0;
            $j = 0;
            foreach ($PocomosBillJob as $value) {
                $job_array['job_data'][$i] = PocomosJob::with('invoice_detail_bill')->where('id', $value->job_id)->select('id', 'invoice_id', 'date_scheduled', 'status')->first();

                // array_push($job_array, $demo);
                $i++;
            }

            $PocomosBillInvoice = PocomosBillInvoice::where('bill_group_id', $val->id)->get();

            foreach ($PocomosBillInvoice as $values) {
                $job_array['invoice_data'][$j]  = PocomosInvoice::where('id', $values->invoice_id)->select('id', 'date_due', 'balance', 'status')->first();

                // array_push($job_array, $demo_data);
                $j++;
            }
            array_push($result_array, $job_array);
        }

        $office = $profile->office_details;
        $customer = $profile->customer_details;

        $serviceCustomer = $billingCustomer = $profile->customer_details;

        $parameters = array(
            'serviceCustomer' => $serviceCustomer,
            'billingCustomer' => $billingCustomer,
            'bill_id' => $_GET['bill_id'],
            'office' => $office,
            'bill_data' => $result_array,
        );

        $pdf = PDF::loadView('pdf.bill', compact('parameters'));

        $url = 'Bill/' . $_GET['bill_id'] . '_bill' . mt_rand(1000000000, time()) . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output());
        $url = Storage::disk('s3')->url($url);

        return $url;
    }

    /**
     * API for Creates a Bill print bill api
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function printcreateAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_pest_contracts,id',
            'user_details' => 'required|array',
            'user_details.*.customer_id' => 'required|exists:pocomos_customers,id', //customer_id
            'invoice_detail' => 'array',
            'job_detail' => 'array',
            'user_details.*.invoice_detail.*.invoice_id' => 'exists:pocomos_invoices,id', //pocomos_invoices
            'user_details.*.job_detail.*.job_id' => 'exists:pocomos_jobs,id', //job_id
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'emails' => 'array',
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
            return $this->sendResponse(false, 'Unable to locate Profile Entity.');
        }

        $office = $profile->office_details;
        $customer = $profile->customer_details;
        $serviceCustomer = $billingCustomer = $profile->customer_details;

        $pestContract = PocomosPestContract::findOrFail($request->contract_id);
        $salesContract = PocomosContract::where('id', $pestContract->contract_id)->first();

        $bill['profile_id'] = $profile->id;
        $bill['status'] = 'Not Paid';
        $bill['active'] = 1;
        $bill['name'] = '';
        $PocomosBill = PocomosBill::create($bill);

        foreach ($request->user_details as $user_details) {
            $profile = PocomosCustomerSalesProfile::where('customer_id', $user_details['customer_id'])->first();

            $group['bill_id'] = $PocomosBill->id;
            $group['profile_id'] = $profile->id;
            $group['active'] = 1;
            $BillGroup = PocomosBillGroup::create($group);

            if (isset($user_details['job_detail'])) {
                foreach ($user_details['job_detail'] as $key => $job_detail) {
                    $billjob['job_id']  = $job_detail['job_id'];
                    $billjob['bill_group_id'] = $BillGroup->id;
                    $PocomosBillJob = PocomosBillJob::create($billjob);
                }
            }

            if (isset($user_details['new_item_detail'])) {
                foreach ($user_details['new_item_detail'] as $key => $new_item_detail) {
                    // create invoice
                    $invoice_input['contract_id'] = $pestContract->contract_id;
                    $invoice_input['date_due'] = date('Y-m-d', strtotime('+2 year'));
                    $invoice_input['amount_due'] = $new_item_detail['price'];
                    $invoice_input['status'] = 'Due';
                    $invoice_input['balance'] = $new_item_detail['price'];
                    $invoice_input['active'] = true;
                    $invoice_input['sales_tax'] = $salesContract->sales_tax ?? null;
                    $invoice_input['tax_code_id'] =  $salesContract->tax_code_id ?? null;
                    $invoice_input['closed'] = false;
                    $pocomos_invoice = PocomosInvoice::create($invoice_input);

                    $billinvoice['invoice_id']  =  $pocomos_invoice->id;
                    $billinvoice['bill_group_id'] = $BillGroup->id;
                    $PocomosBillInvoice = PocomosBillInvoice::create($billinvoice);

                    $item['tax_code_id'] = $salesContract->tax_code_id ?? null;
                    $item['sales_tax'] = $salesContract->sales_tax ?? null;
                    $item['description'] = $new_item_detail['description'];
                    $item['price'] = $new_item_detail['price'];
                    $item['active'] = true;
                    $item['invoice_id'] = $pocomos_invoice->id;
                    $item['type'] = "";
                    $PocomosInvoiceItems = PocomosInvoiceItems::create($item);


                    $items['date_scheduled'] = date('Y-m-d');
                    $items['amount_in_cents'] = 0;
                    $items['status'] = "Unpaid";
                    $items['active'] = true;
                    $PocomosInvoicePayment = PocomosInvoicePayment::create($items);

                    $itempayment['invoice_id'] = $pocomos_invoice->id;
                    $itempayment['payment_id'] = $PocomosInvoicePayment->id;
                    $PocomosInvoiceInvoicePayment = PocomosInvoiceInvoicePayment::create($itempayment);
                }
            }


            if (isset($user_details['invoice_detail'])) {
                foreach ($user_details['invoice_detail'] as $key => $invoice_detail) {
                    $billinvoice['invoice_id']  = $invoice_detail['invoice_id'];
                    $billinvoice['bill_group_id'] = $BillGroup->id;
                    $PocomosBillInvoice = PocomosBillInvoice::create($billinvoice);
                }
            }
        }

        $office = PocomosCompanyOffice::where('id', $request->office_id)->first();
        $office_email = unserialize($office->email);

        if (isset($office_email[0])) {
            $from = $office_email[0];
        } else {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        $PocomosBillGroup = PocomosBillGroup::where('bill_id', $PocomosBill->id)->get();
        $result_array = array();

        foreach ($PocomosBillGroup as $val) {
            $job_array = array();

            $profileofcustomer = PocomosCustomerSalesProfile::findOrFail($val->profile_id);

            $customer = PocomosCustomer::where('id', $profileofcustomer->customer_id)->select('first_name', 'last_name')->first();

            $username = $customer['first_name'] . " " . $customer['last_name'];

            $job_array['name'] = $username;
            $job_array['customer_id'] = $profileofcustomer->customer_id;

            $PocomosBillJob = PocomosBillJob::where('bill_group_id', $val->id)->get();

            $i = 0;
            $j = 0;
            foreach ($PocomosBillJob as $value) {
                $job_array['job_data'][$i] = PocomosJob::with('invoice_detail_bill')->where('id', $value->job_id)->select('id', 'invoice_id', 'date_scheduled', 'status')->first();

                // array_push($job_array, $demo);
                $i++;
            }

            $PocomosBillInvoice = PocomosBillInvoice::where('bill_group_id', $val->id)->get();

            foreach ($PocomosBillInvoice as $values) {
                $job_array['invoice_data'][$j]  = PocomosInvoice::where('id', $values->invoice_id)->select('id', 'date_due', 'balance', 'status')->first();

                // array_push($job_array, $demo_data);
                $j++;
            }
            array_push($result_array, $job_array);
        }

        $parameters = array(
            'serviceCustomer' => $serviceCustomer,
            'billingCustomer' => $billingCustomer,
            'bill_id' => $PocomosBill->id,
            'office' => $office,
        );

        $pdf = PDF::loadView('pdf.bill', compact('parameters'));

        $url = 'Bill/' . $PocomosBill->id . '_bill' . mt_rand(1000000000, time()) . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output());
        $url = Storage::disk('s3')->url($url);

        return $url;
    }




    /**
     * API for Edit a Bill print bill api
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function printeditAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_pest_contracts,id',
            'user_details' => 'required|array',
            'user_details.*.customer_id' => 'required|exists:pocomos_customers,id', //customer_id
            'invoice_detail' => 'array',
            'job_detail' => 'array',
            'bill_id' => 'required|exists:pocomos_bills,id',
            'user_details.*.invoice_detail.*.invoice_id' => 'exists:pocomos_invoices,id', //pocomos_invoices
            'user_details.*.job_detail.*.job_id' => 'exists:pocomos_jobs,id', //job_id
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'emails' => 'array',
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
            return $this->sendResponse(false, 'Unable to locate Profile Entity.');
        }

        $office = $profile->office_details;
        $customer = $profile->customer_details;
        $serviceCustomer = $billingCustomer = $profile->customer_details;

        $pestContract = PocomosPestContract::findOrFail($request->contract_id);
        $salesContract = PocomosContract::where('id', $pestContract->contract_id)->first();

        //$PocomosBillGroup = PocomosBillGroup::where('bill_id', $request->bill_id)->get();
        $PocomosBillGroup = PocomosBillGroup::where('bill_id', $request->bill_id)->pluck('id')->toArray();

        PocomosBillJob::whereIn('bill_group_id', $PocomosBillGroup)->delete();
        PocomosBillInvoice::whereIn('bill_group_id', $PocomosBillGroup)->delete();
        PocomosBillGroup::where('bill_id', $request->bill_id)->delete();

        foreach ($request->user_details as $user_details) {
            $profile = PocomosCustomerSalesProfile::where('customer_id', $user_details['customer_id'])->first();

            $group['bill_id'] = $request->bill_id;
            $group['profile_id'] = $profile->id;
            $group['active'] = 1;
            $BillGroup = PocomosBillGroup::create($group);

            if (isset($user_details['job_detail'])) {
                foreach ($user_details['job_detail'] as $key => $job_detail) {
                    $billjob['job_id']  = $job_detail['job_id'];
                    $billjob['bill_group_id'] = $BillGroup->id;
                    $PocomosBillJob = PocomosBillJob::create($billjob);
                }
            }

            if (isset($user_details['new_item_detail'])) {
                foreach ($user_details['new_item_detail'] as $key => $new_item_detail) {
                    // create invoice
                    $invoice_input['contract_id'] = $pestContract->contract_id;
                    $invoice_input['date_due'] = date('Y-m-d', strtotime('+2 year'));
                    $invoice_input['amount_due'] = $new_item_detail['price'];
                    $invoice_input['status'] = 'Due';
                    $invoice_input['balance'] = $new_item_detail['price'];
                    $invoice_input['active'] = true;
                    $invoice_input['sales_tax'] = $salesContract->sales_tax ?? null;
                    $invoice_input['tax_code_id'] =  $salesContract->tax_code_id ?? null;
                    $invoice_input['closed'] = false;
                    $pocomos_invoice = PocomosInvoice::create($invoice_input);

                    $billinvoice['invoice_id']  =  $pocomos_invoice->id;
                    $billinvoice['bill_group_id'] = $BillGroup->id;
                    $PocomosBillInvoice = PocomosBillInvoice::create($billinvoice);

                    $item['tax_code_id'] = $salesContract->tax_code_id ?? null;
                    $item['sales_tax'] = $salesContract->sales_tax ?? null;
                    $item['description'] = $new_item_detail['description'];
                    $item['price'] = $new_item_detail['price'];
                    $item['active'] = true;
                    $item['invoice_id'] = $pocomos_invoice->id;
                    $item['type'] = "";
                    $PocomosInvoiceItems = PocomosInvoiceItems::create($item);


                    $items['date_scheduled'] = date('Y-m-d');
                    $items['amount_in_cents'] = 0;
                    $items['status'] = "Unpaid";
                    $items['active'] = true;
                    $PocomosInvoicePayment = PocomosInvoicePayment::create($items);

                    $itempayment['invoice_id'] = $pocomos_invoice->id;
                    $itempayment['payment_id'] = $PocomosInvoicePayment->id;
                    $PocomosInvoiceInvoicePayment = PocomosInvoiceInvoicePayment::create($itempayment);
                }
            }


            if (isset($user_details['invoice_detail'])) {
                foreach ($user_details['invoice_detail'] as $key => $invoice_detail) {
                    $billinvoice['invoice_id']  = $invoice_detail['invoice_id'];
                    $billinvoice['bill_group_id'] = $BillGroup->id;
                    $PocomosBillInvoice = PocomosBillInvoice::create($billinvoice);
                }
            }
        }

        $PocomosBillGroup = PocomosBillGroup::where('bill_id', $request->bill_id)->get();
        $result_array = array();

        foreach ($PocomosBillGroup as $val) {
            $job_array = array();

            $profileofcustomer = PocomosCustomerSalesProfile::findOrFail($val->profile_id);

            $customer = PocomosCustomer::where('id', $profileofcustomer->customer_id)->select('first_name', 'last_name')->first();

            $username = $customer['first_name'] . " " . $customer['last_name'];

            $job_array['name'] = $username;
            $job_array['customer_id'] = $profileofcustomer->customer_id;

            $PocomosBillJob = PocomosBillJob::where('bill_group_id', $val->id)->get();

            $i = 0;
            $j = 0;
            foreach ($PocomosBillJob as $value) {
                $job_array['job_data'][$i] = PocomosJob::with('invoice_detail_bill')->where('id', $value->job_id)->select('id', 'invoice_id', 'date_scheduled', 'status')->first();

                // array_push($job_array, $demo);
                $i++;
            }

            $PocomosBillInvoice = PocomosBillInvoice::where('bill_group_id', $val->id)->get();

            foreach ($PocomosBillInvoice as $values) {
                $job_array['invoice_data'][$j]  = PocomosInvoice::where('id', $values->invoice_id)->select('id', 'date_due', 'balance', 'status')->first();

                // array_push($job_array, $demo_data);
                $j++;
            }
            array_push($result_array, $job_array);
        }

        $parameters = array(
            'serviceCustomer' => $serviceCustomer,
            'billingCustomer' => $billingCustomer,
            'bill_id' => $request->bill_id,
            'office' => $office,
        );

        $pdf = PDF::loadView('pdf.bill', compact('parameters'));

        $url = 'Bill/' . $request->bill_id . '_bill' . mt_rand(1000000000, time()) . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output());
        $url = Storage::disk('s3')->url($url);

        return $url;
    }
}
