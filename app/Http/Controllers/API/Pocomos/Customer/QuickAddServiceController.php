<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use DB;
use DateTime;
use Twilio\Rest\Client;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosJob;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosNote;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosRoute;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosJobPest;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosDiscount;
use App\Models\Pocomos\PocomosJobService;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosCustomersNote;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosInvoicePayment;
use App\Models\Pocomos\PocomosBestfitThreshold;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosInvoiceInvoicePayment;
use App\Models\Pocomos\PocomosCustomersWorkorderNote;
use App\Models\Pocomos\PocomosPestContractServiceType;

class QuickAddServiceController extends Controller
{
    use Functions;

    /**
     * API for send sms
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            // 'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_pest_contracts,id',
            'dateScheduled' => 'required',
            'type' => 'required',
            'targeted_pests' => 'required|array|exists:pocomos_pests,id',
            'amountDue' => 'required',
            'note' => 'nullable',
            'treatment_note' => 'nullable',
            'color' => 'nullable',
            'route' => 'nullable',
            'slot' => 'nullable',
            'time_scheduled' => 'nullable',
            'duplicateTreatmentNote' => 'nullable'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $customer = $this->findOneByIdAndOffice_customerRepo($custId, $officeId);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find Customer entity.']));
        }

        $pestContract = PocomosPestContract::whereId($request->contract_id)->first();


        $jobModel['customer'] = $customer;

        if ($request->slot) {
            // return $request->slot;
            list($routeId, $beginTime) = explode('-', $request->slot);

            $route = PocomosRoute::whereId($routeId)->whereOfficeId($officeId)->first();

            $dateScheduled = new DateTime($route->date_scheduled);

            $slotTime = $dateScheduled->format('Y-m-d ') . $beginTime;

            $jobModel['route'] = $route;
            $jobModel['timeScheduled'] = $slotTime;

            // return $jobModel;
        } else {
            $timeScheduled = $request->time_scheduled;

            $routeId = $request->route;
            if ($routeId != '') {
                $route = PocomosRoute::whereId($routeId)->whereOfficeId($officeId)->first();

                $jobModel['route'] = $route;
            }

            if ($timeScheduled) {
                $timeScheduled = new DateTime($timeScheduled);
                $jobModel['timeScheduled'] = $timeScheduled;
            }
        }

        if ($request->duplicateTreatmentNote) {
            $permanent_job_note_data['summary'] = $request->note;
            $permanent_job_note_data['user_id'] = auth()->user()->id;
            $permanent_job_note_data['interaction_type'] = 'Other';
            $permanent_job_note_data['active'] = true;
            $permanent_job_note_data['body'] = '';
            $permanent_job_note = PocomosNote::create($permanent_job_note_data);

            PocomosCustomersNote::create(['customer_id' => $custId, 'note_id' => $permanent_job_note->id]);
        }

        $lastCompletedJob = 0;

        if ($request->type == 'Re-service') {
            // $contract = $this->getCurrentContract($customer);
            if ($pestContract) {
                $jobs = $this->findCompletedServicesForContract($pestContract->id)->get();
                $jobIds = [];
                foreach ($jobs as $job) {
                    $jobIds[] = $job->id;
                }
                // return $jobIds;
                if ($jobIds) {
                    $lastCompletedJob = max($jobIds);
                }
            } else {
                $jobs = array();
            }
        }

        // $entity = $this->createJobFromModel($jobModel);

        // if(!empty($lastCompletedJob)){
        //     $entity->setLastRegularService($lastCompletedJob);
        // }

        $find_contract = PocomosContract::where('id', $pestContract->contract_id)->first();

        if (!$find_contract) {
            return $this->sendResponse(false, 'Contract not found.');
        }

        $taxCode = PocomosTaxCode::where('id', $find_contract->tax_code_id)->first();

        // create invoice
        $invoice_input = [];
        $invoice_input['contract_id'] = $find_contract->id;
        $invoice_input['date_due'] = $request->dateScheduled;
        $invoice_input['amount_due'] = $request->amountDue;
        $invoice_input['status'] = 'Not sent';
        $invoice_input['balance'] = $request->amountDue;
        $invoice_input['sales_tax'] = $taxCode->tax_rate;
        $invoice_input['tax_code_id'] = $taxCode->id;
        $invoice_input['active'] = 1;
        $invoice_input['closed'] = 0;

        $invoice = PocomosInvoice::create($invoice_input);

        // Add entry into invoice items table
        $invoice_item = [];
        $invoice_item['invoice_id'] = $invoice->id;
        $invoice_item['description'] = $request->type . ' Check Service';
        $invoice_item['price'] = $request->amountDue;
        $invoice_item['active'] = '1';
        $invoice_item['sales_tax'] = $taxCode->tax_rate;
        $invoice_item['tax_code_id'] = $taxCode->id;
        $invoice_item['type'] = '';

        $invoice_item = PocomosInvoiceItems::create($invoice_item);

        $items['date_scheduled'] = date('Y-m-d');
        $items['amount_in_cents'] = 0;
        $items['status'] = "Unpaid";
        $items['active'] = true;
        $PocomosInvoicePayment = PocomosInvoicePayment::create($items);

        $itempayment['invoice_id'] = $invoice->id;
        $itempayment['payment_id'] = $PocomosInvoicePayment->id;
        $PocomosInvoiceInvoicePayment = PocomosInvoiceInvoicePayment::create($itempayment);

        $input = [];
        $input['contract_id'] = $pestContract->id;
        $input['invoice_id'] = $invoice->id;
        $input['date_scheduled'] = $request->dateScheduled;
        $input['type'] = $request->type;
        $input['status'] = 'Pending';
        $input['active'] = '1';
        $input['original_date_scheduled'] = $request->dateScheduled;
        $input['note'] = $request->note ?? '';
        $input['color'] = $request->color ?? '';
        $input['commission_type'] = 'None';
        $input['commission_value'] = 0;
        $input['commission_edited'] = 0;
        $input['technician_note'] = '';
        $input['weather'] = '';
        $input['treatmentNote'] = $request->treatmentNote ?? '';
        $create_job = PocomosJob::create($input);

        $service = PocomosPestContractServiceType::where('office_id', $request->office_id)->where('active', '1')->first();
        $job_service = [];
        $job_service['service_type_id'] = $service->id;
        $job_service['job_id'] = $create_job->id;
        $job_service['active'] = '1';
        $insert_job = PocomosJobService::create($job_service);

        foreach ($request->targeted_pests as $targeted_pest) {
            $job_service = [];
            $job_service['pest_id'] = $targeted_pest;
            $job_service['job_id'] = $create_job->id;
            $insert_job = PocomosJobPest::create($job_service);
        }

        // $profileId = 'null';
        // if ($customer->sales_profile) {
        $profileId = $customer->profile_id;
        // }

        $desc = '';
        if (auth()->user()) {
            $desc .= "<a href='/pocomos-admin/app/employees/users/" . auth()->user()->id . "/show'>" . auth()->user()->full_name . "</a> ";
        } else {
            $desc .= "The system ";
        }

        $desc .= "created a job for";

        if (isset($customer)) {
            $desc .= " customer <a href='/pocomos-admin/app/Customers/" . $customer->id . "/service-information'>" . $customer->first_name . " " . $customer->last_name . "</a>.";
        }
        $desc .= '.';

        $sql = 'INSERT INTO pocomos_activity_logs
                    (type, office_user_id, customer_sales_profile_id, description, context, date_created)
                    VALUES("Job Created", ' . auth()->user()->pocomos_company_office_user->id . ',
                        ' . $profileId . ', "' . $desc . '", "", "' . date('Y-m-d H:i:s') . '")';

        DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'The service has been created successfully.');
    }



    public function contractList(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',

        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $contractList = PocomosPestContract::select(
            '*',
            'pocomos_pest_contracts.id',
            'pc.id as contractId',
            'pa.name as agreementName'
        )
            ->join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
            ->join('pocomos_agreements as pa', 'pc.agreement_id', 'pa.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->where('pc.status', '=', 'Active')
            ->where('pcsp.customer_id', $request->customer_id)
            ->get();

        return $this->sendResponse(true, __('strings.list', ['name' => 'contractList']), $contractList);
    }

    public function serviceTypes($id)
    {
        // find service types based on office id
        $services = PocomosPestContractServiceType::where('office_id', $id)->where('active', '1')->get();
        return $this->sendResponse(true, 'List of services', $services);
    }

    public function listServices($id)
    {
        $pocomos_contracts = PocomosContract::where('profile_id', $id)->pluck('id');
        $find_pest_contract_id = PocomosPestContract::whereIn('contract_id', $pocomos_contracts)->first();
        $find_jobs = PocomosJob::whereIn('contract_id', $find_pest_contract_id)->with('invoice_detail.invoice_items', 'service_details.service_data')->get();
        return $this->sendResponse(true, 'List of services', $find_jobs);
    }

    public function listschduleServices(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_pest_contracts,id',
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

        $pestContract = PocomosPestContract::findOrFail($request->contract_id);

        $jobs = PocomosJob::with('technician_detail.user_detail.user_details_name')->with(['invoice' => function ($query) {
            $query->select('id', 'date_due', 'amount_due', 'balance');
        }])
            ->where('contract_id', $request->contract_id)->whereIn('status', ['Pending', 'Re-scheduled'])->orderBy('date_scheduled', 'DESC');

        if ($request->search) {
            $search = $request->search;
            $jobs->where(function ($query) use ($search) {
                $query->where('date_scheduled', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%');
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
            'Schedule_services' => $jobs,
            'count' => $count,
        ]);
    }

    // Add discount
    public function addDiscuount(Request $request)
    {
        $v = validator($request->all(), [
            'invoice_id' => 'required',
            'description' => 'required',
            'price' => 'required',
            'type' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // find invoice from invoice_id;

        $find_invoice = PocomosInvoice::where('id', $request->invoice_id)->first();
        if (!$find_invoice) {
            return $this->sendResponse(false, 'Invoice not found.');
        }

        if ($request->type == 'static') {
            $balance = '-' . $request->price;
            $amount = $find_invoice->balance - $request->price;
        } else {
            $find_percentage = ($find_invoice->balance * ($request->price / 100));
            $balance = '-' . $request->price;
            $amount = $find_invoice->balance - $find_percentage;
        }

        // Add entry into invoice items table
        $invoice_item = [];
        $invoice_item['invoice_id'] = $request->invoice_id;
        $invoice_item['description'] = $request->description;
        $invoice_item['price'] = $balance;
        $invoice_item['active'] = '1';
        $invoice_item['sales_tax'] = '0.0000';
        $invoice_item['tax_code_id'] = $find_invoice->tax_code_id;
        $invoice_item['type'] = 'Discount';
        $invoice_item = PocomosInvoiceItems::create($invoice_item);

        $find_invoice->balance = $amount;
        $find_invoice->save();
        return $this->sendResponse(true, 'Discuount added successfully', $invoice_item);
    }

    // Payment Details
    public function paymentDetails($id)
    {
        $find_invoice = PocomosInvoice::where('id', $id)->first();
        if (!$find_invoice) {
            return $this->sendResponse(false, 'Invoice not found.');
        }
        $find_invoice_items = PocomosInvoiceItems::where('invoice_id', $id)->get();
        return $this->sendResponse(true, 'Discuount added successfully', $find_invoice_items);
    }

    // Edit invoice items
    public function editInvoiceItem(Request $request)
    {
        $v = validator($request->all(), [
            'item_id' => 'required|exists:pocomos_invoice_items,id',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'invoice_id' => 'required|exists:pocomos_invoices,id',
            'description' => 'required',
            'price' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_invoice_items = PocomosInvoiceItems::where('id', $request->item_id)->where('invoice_id', $request->invoice_id)->first();
        if (!$find_invoice_items) {
            return $this->sendResponse(false, 'Invoice item not found.');
        }

        $taxCode = PocomosTaxCode::findOrFail($find_invoice_items->tax_code_id);

        $result = $this->updateInvoiceItem($find_invoice_items, $request->price, $find_invoice_items->price, $taxCode);

        $find_invoice_items->description = $request->description;
        $find_invoice_items->save();

        return $this->sendResponse(true, 'The invoice item has been updated successfully', $result);
    }

    // Delete invoice item
    public function deleteInvoiceItem($id)
    {
        $find_invoice_items = PocomosInvoiceItems::where('id', $id)->first();

        $find_invoice = PocomosInvoice::where('id', $find_invoice_items->invoice_id)->first();

        $amount = $find_invoice->balance + (abs($find_invoice_items->price));
        $find_invoice->balance = $amount;
        $find_invoice->save();
        $find_invoice_items->delete();
        return $this->sendResponse(true, 'Invoice item deleted successfully', $find_invoice);
    }

    // Cancel invoice
    public function cancelInvoice($id)
    {
        $find_invoice = PocomosInvoice::where('id', $id)->first();
        if (!$find_invoice) {
            return $this->sendResponse(false, 'Invoice not found.');
        }
        $find_invoice->status = 'Cancelled';
        $find_invoice->balance = '0.00';
        $find_invoice->save();


        // removed in symphony
        /* $profileId = 'null';
        if(isset($customer->sales_profile)){
            $profileId = $customer->sales_profile->id;
        }

        $desc = '';
        if (auth()->user()) {
            $desc .= "<a href='/pocomos-admin/app/employees/users/".auth()->user()->id."/show'>" . auth()->user()->full_name . "</a> ";
        } else {
            $desc .= 'The system ';
        }

        $desc .= 'cancelled a invoice for';

        if (isset($customer)) {
            $desc .= ' customer <a href="/pocomos-admin/app/Customers/".$customer->id."/service-information">' . $customer->first_name ." ". $customer->last_name . '</a> ';
        }
        $desc .= '.';

        $sql = 'INSERT INTO pocomos_activity_logs
                    (type, office_user_id, customer_sales_profile_id, description, context, date_created)
                    VALUES("Invoice Cancelled", '.auth()->user()->pocomos_company_office_user->id.',
                        '.$profileId.', "", "", "'.date('Y-m-d H:i:s').'")';

        DB::select(DB::raw($sql)); */

        return $this->sendResponse(true, 'Invoice cancelled successfully', $find_invoice);
    }

    // add new invoice item
    public function addInvoiceItem(Request $request)
    {
        $v = validator($request->all(), [
            'invoice_id' => 'required|exists:pocomos_invoices,id',
            'item_type' => 'required|in:Regular,Discount,Additional Tax',
            'discount_id' => 'nullable|exists:pocomos_discounts,id',
            'description' => 'required',
            'value_type' => 'nullable|in:static,Percent',
            'price' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_invoice = PocomosInvoice::where('id', $request->invoice_id)->first();

        if ($find_invoice->closed == 1) {
            return $this->sendResponse(false, 'Invoice Closed Unable to edit invoice item.');
        }

        $valueType = $request->value_type ?? 'static';
        $itemType = $request->item_type;

        $invoice_item['price'] = $request->price;
        $invoice_item['description'] = $request->description;
        $invoice_item['itemType'] = $itemType;

        if ($itemType == 'Discount') {
            $discount = PocomosDiscount::where('id', $request->discount_id)->first();

            if ($request->discount_id != null) {
                if ($valueType == 'static') {
                    $invoice_item['description'] = $discount->name . ' ' . ':' . ' ' . $request->description;
                } else {
                    $invoice_item['description'] =  $request->price . '%' . ' ' . ':' . ' ' . $discount->name . ' ' . ':' . ' ' . $request->description;
                }
            }
        }

        $result = $this->addInvoiceItems($find_invoice, $invoice_item, $valueType);

        return $this->sendResponse(true, 'Invoice item added successfully', $result);
    }

    // note details get
    public function noteDetails(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',

        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $jobs = PocomosCustomersNote::where('customer_id', $request->customer_id)->orderBy('note_id', 'DESC')->first();

        $find_job = PocomosNote::findOrFail($jobs->note_id);
        if (!$find_job) {
            return $this->sendResponse(false, 'Note not found.');
        }

        return $this->sendResponse(true, 'Note details', $find_job);
    }

    // Edit note
    public function editNote(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'note_id' => 'nullable|exists:pocomos_notes,id',
            'user_id' => 'exists:orkestra_users,id',
            'job_id' => 'required|exists:pocomos_jobs,id',
            'note' => 'required',
            'duplicate' => 'required|boolean',
            'displayAtTimeOfService' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_job = PocomosJob::findOrFail($request->job_id);
        if (!$find_job) {
            return $this->sendResponse(false, 'Job not found.');
        }

        if ($request->duplicate == 1) {
            $initial_job_note_data = [];
            $initial_job_note_data['user_id'] = auth()->user()->id;
            $initial_job_note_data['summary'] = $request->note;
            $initial_job_note_data['interaction_type'] = 'Other';
            $initial_job_note_data['active'] = true;
            $initial_job_note_data['body'] = '';
            $initial_job_note = PocomosNote::create($initial_job_note_data);

            PocomosCustomersNote::create(['customer_id' => $request->customer_id, 'note_id' => $initial_job_note->id]);

            if ($request['displayAtTimeOfService'] == 1) {
                PocomosCustomersWorkorderNote::create(['customer_id' => $request->customer_id, 'note_id' => $initial_job_note->id]);
            }
        }

        $find_job->update([
            'note' => $request->note
        ]);

        return $this->sendResponse(true, 'Note edited successfully', $find_job);
    }

    public function editService(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'job_id' => 'required|exists:pocomos_jobs,id',
            'type' => 'required|in:Initial,Regular,Re-service,Inspection,Follow-up,Pickup Service',
            'status' => 'required|in:Pending,Complete,Re-scheduled,Cancelled',
            'amountDue' => 'required',
            'commissionType' => 'required|in:None,Flat,Rate',
            'commissionValue' => 'required'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        // find job
        $find_job = PocomosJob::with('contract.contract_details.profile_details.customer')->findOrFail($request->job_id);
        $find_invoice = PocomosInvoice::findOrFail($find_job->invoice_id);
        $find_invoice_item = PocomosInvoiceItems::where('invoice_id', $find_job->invoice_id)->first();

        $find_job->status = $request->status;
        $find_job->type = $request->type;
        $find_job->commission_value = $request->commissionValue;
        $find_job->commission_type = $request->commissionType;

        $find_invoice->amount_due = $request->amountDue;

        $find_invoice_item->price = $request->amountDue;

        if (isset($find_job->contract->contract_details->profile_details->customer)) {
            $customer = $find_job->contract->contract_details->profile_details->customer;
        }

        if ($request->status == "Cancelled") {
            $find_job->date_cancelled =  date('Y-m-d H:i:s');
            $activityType = 'Job Cancelled';

            $desc = '';
            if (auth()->user()) {
                $desc .= "<a href='/pocomos-admin/app/employees/users/" . auth()->user()->id . "/show'>" . auth()->user()->full_name . "</a> ";
            } else {
                $desc .= 'The system ';
            }

            $desc .= 'cancelled a job for';

            if (isset($customer)) {
                $desc .= " customer <a href='/pocomos-admin/app/Customers/" . $customer->id . "/service-information'>" . $customer->first_name . " " . $customer->last_name . "</a>.";
            }

            if (isset($find_invoice) && !empty($find_invoice)) {
                $desc .= " with invoice <a href='/pocomos-admin/app/Customers/" . $customer->id . "/invoice/" . $find_invoice->id . "/show'> " . $find_invoice->id . " </a> ";
            }

            $desc .= '.';
        }

        if ($request->status == "Complete") {
            $find_job->technician_id =  $request->technician_id ?? null;
            $find_job->date_completed =  $request->completedDate ?? null;
            $find_job->force_completed =  1;
            $find_invoice->date_due =  date('Y-m-d');

            $activityType = 'Job Completed';

            $desc = '';
            if (auth()->user()) {
                $desc .= "<a href='/pocomos-admin/app/employees/users/" . auth()->user()->id . "/show'>" . auth()->user()->full_name . "</a> ";
            } else {
                $desc .= 'The system ';
            }

            $desc .= 'completed a job for';

            if (isset($customer)) {
                $desc .= " customer <a href='/pocomos-admin/app/Customers/" . $customer->id . "/service-information'>" . $customer->first_name . " " . $customer->last_name . "</a>.";
            }

            if (isset($find_invoice) && !empty($find_invoice)) {
                $desc .= " with invoice <a href='/pocomos-admin/app/Customers/" . $customer->id . "/invoice/" . $find_invoice->id . "/show'> " . $find_invoice->id . " </a> ";
            }

            $desc .= '.';
        }

        if ($request->status == "Rescheduled") {
            $activityType = 'Job Rescheduled';

            $fromTime = $find_job->date_scheduled;

            // dd($fromTime);

            if ($find_job->time_scheduled != null) {
                $fromTime = $fromTime . ' at ' . $find_job->time_scheduled;
                $toTime = $find_job->date_scheduled;
                $toTime = $toTime . ' at ' . $find_job->time_scheduled;
            } else {
                $fromTime = $fromTime . ' at anytime';
                $toTime = $fromTime . ' at anytime';
            }

            $desc = '';
            if (auth()->user()) {
                $desc .= "<a href='/pocomos-admin/app/employees/users/" . auth()->user()->id . "/show'>" . auth()->user()->full_name . "</a> ";
            } else {
                $desc .= 'The system ';
            }

            $desc .= 'rescheduled a job for';

            if (isset($customer)) {
                $desc .= " customer <a href='/pocomos-admin/app/Customers/" . $customer->id . "/service-information'>" . $customer->first_name . " " . $customer->last_name . "</a> ";
            }
            if (isset($fromTime) && !empty($fromTime)) {
                $desc .= ' to ' . $fromTime;
            }
            if (isset($find_invoice) && !empty($find_invoice)) {
                $desc .= " with invoice <a href='/pocomos-admin/app/Customers/" . $customer->id . "/invoice/" . $find_invoice->id . "/show'> " . $find_invoice->id . " </a> ";
            }
            $desc .= '.';
        } else {
            $activityType = 'Job Updated';

            $desc = '';
            if (auth()->user()) {
                $desc .= "<a href='/pocomos-admin/app/employees/users/" . auth()->user()->id . "/show'>" . auth()->user()->full_name . "</a> ";
            } else {
                $desc .= 'The system ';
            }

            $desc .= 'updated a job for';

            if (isset($customer)) {
                $desc .= " customer <a href='/pocomos-admin/app/Customers/" . $customer->id . "/service-information'>" . $customer->first_name . " " . $customer->last_name . "</a>.";
            }

            $desc .= '.';
        }

        $find_job->save();
        $find_invoice->save();
        $find_invoice_item->save();

        $profileId = 'null';
        if (isset($find_job->contract->contract_details->profile_id)) {
            $profileId = $find_job->contract->contract_details->profile_id;
        }

        $sql = 'INSERT INTO pocomos_activity_logs
                    (type, office_user_id, customer_sales_profile_id, description, context, date_created)
                    VALUES("' . $activityType . '", ' . auth()->user()->pocomos_company_office_user->id . ',
                        ' . $profileId . ', "", "", "' . date('Y-m-d H:i:s') . '")';

        DB::select(DB::raw($sql));

        return $this->sendResponse(true, 'The service has been updated successfully.');
    }

    /**
     * Displays a form to create a new Job entity.
     */

    public function getBestFitAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::where('id', $request->customer_id)->first();

        // dd($customer);

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $address = $customer->contact_address;

        if (($address->validated) && !($address->valid)) {
            return $this->sendResponse(false, 'Customer address could not be validated. Best fit could not be found.');
        }

        $duration = $customer->default_job_duration;
        $date = date('Y-m-d H:i:s');

        // dd(22);
        $result = $this->getBestFit($date, $request->office_id, $duration, $address);

        $routeIds = collect($result)->pluck('route_id');

        $routes = PocomosRoute::with('technician_detail.user_detail.user_details_name')->whereIn('id', $routeIds)->get();

        // $q = 0;
        // foreach ($routes as $r) {
        //     $r->slot_time = $result[$q]['time'];
        //     $r->delta     = $result[$q]['delta'];

        //     $q++;
        // }

        // array_walk($result, function ($slot)  {
        //     // dd($slot);
        //     $slot = $this->normalize($slot);
        // });


        // for color
        $PocomosPestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->first();
        $bestfitThreshold = PocomosBestfitThreshold::whereOfficeConfigurationId($PocomosPestOfficeSetting->id)->whereActive(1)
            ->orderBy('threshold')->get();

        // $result = [
        //     [
        //         "route_id"=> 18,
        //         "time"=> "10:00:00",
        //         "delta"=> 0
        //     ],
        //     [
        //         "route_id"=> 18,
        //         "time"=> "10:30:00",
        //         "delta"=> 0
        //     ],
        //     [
        //         "route_id"=> 18,
        //         "time"=> "11:00:00",
        //         "delta"=> 0
        //     ]
        //     ];

        $routeModal = PocomosRoute::query();

        $q = 0;
        foreach ($result as $r) {

            $route = (clone ($routeModal))->with('technician_detail.user_detail.user_details_name')
                ->whereId($r['route_id'])->first();

            $result[$q]['unique_id'] = $q;
            $result[$q]['route'] = $route;
            $q++;
        }

        // return $result;


        return $this->sendResponse(true, 'List of best fit slots', [
            'slots' => $result,
            // 'routes' => $routes,
            'bestfit_threshold' => $bestfitThreshold,
        ]);

        // return $this->sendResponse(true, 'Edited successfully');
    }
}
