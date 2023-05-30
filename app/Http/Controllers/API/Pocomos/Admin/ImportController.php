<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessCustomerImportJob;
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosImportBatch;
use App\Models\Pocomos\PocomosImportCustomer;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class ImportController extends Controller
{
    use Functions;
    //* Office List Dropdown */
    public function getOffices()
    {
        $offices = PocomosCompanyOffice::orderBy('list_name')->select(['list_name'])->get();

        $myCollection = collect($offices);
        $uniqueCollection = $myCollection->unique()->values();
        $uniqueCollection->all();
        return $this->sendResponse(true, 'List of offices.', $uniqueCollection);
    }


    /* Create imported batches */
    public function importBatch(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required', //name
            'import_type' => 'required|in:Customers,Leads', //import_type
            'service_frequency' => 'required', //service_frequency
            'office_id' => 'required|exists:pocomos_company_offices,id', //office_id
            'service_type_id' => 'required|exists:pocomos_pest_contract_service_types,id', //service_type_id
            'salesperson_id' => 'required|exists:pocomos_salespeople,id', //salesperson_id
            'technician_id' => 'required|exists:pocomos_technicians,id', //technician_id
            'contract_type' => 'required', //pest_agreement_id
            'marketing_type' => 'required', //found_by_type_id
            'tax_code' => 'required', //tax_code_id
            'csv_file' => 'required',
            'active' => 'boolean',
            'imported' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $request['service_schedule'] = 'a:0:{}';
        $input_details = $request->only(
            'name',
            'import_type',
            'service_frequency',
            'office_id',
            'service_type_id',
            'salesperson_id',
            'technician_id',
            'active',
            'imported',
            'service_schedule'
        );

        $PocomosImport =  PocomosImportBatch::create($input_details);

        // return $this->sendResponse(true, 'Import successfully.', $PocomosImport);

        $handle = fopen($request->csv_file, "r");
        $header = true;
        $csvArray = [];
        while ($csvLine = fgetcsv($handle, 1000, ",")) {
            if ($header) {
                $header = false;
            } else {
                PocomosImportCustomer::create([

                    'region_id' => null,
                    'billing_region_id' => null,
                    'upload_batch_id' => $PocomosImport->id,
                    'company_name' => $csvLine[0] ?? '',
                    'first_name' => $csvLine[1] ?? '',
                    'last_name' => $csvLine[2] ?? '',
                    'email' => $csvLine[3] ?? '',
                    'phone' => $csvLine[4] ?? '',
                    'alt_phone' => $csvLine[5] ?? '',
                    'street' => $csvLine[6] ?? '',
                    'suite' => $csvLine[7] ?? '',
                    'city' => $csvLine[8] ?? '',
                    'region' => $csvLine[9] ?? '',
                    'postal_code' => $csvLine[10] ?? '',
                    'map_code' => $csvLine[12] ?? '',
                    'name_on_card' => $csvLine[13] ?? '',
                    'card_number' => $csvLine[14] ?? '',
                    'exp_month' => $csvLine[15] ?? '',
                    'exp_year' => $csvLine[16] ?? '',
                    'billing_street' => $csvLine[17] ?? '',
                    'billing_suite' => $csvLine[18] ?? '',
                    'billing_city' => $csvLine[19] ?? '',
                    'billing_region' => $csvLine[20] ?? '',
                    'billing_postal_code' => $csvLine[21] ?? '',
                    'date_signed_up' => $csvLine[22] ?? '',
                    'date_last_service' => $csvLine[23] ?? '',
                    'date_next_service' => $csvLine[24] ?? '',
                    'service_frequency' => $csvLine[25] ?? '',
                    'service_type_id' => null,
                    'week_of_the_month' => $csvLine[28] ?? '',
                    'day_of_the_week' => $csvLine[29] ?? '',
                    'external_identifer' => $csvLine[30] ?? '',
                    'salesperson_id' => null,
                    'previous_balance' => $csvLine[33] ?? '',
                    'original_last_technician' => $csvLine[34] ?? '',
                    'original_tax_code' => $csvLine[35] ?? '',
                    'imported' => '1',
                    'active' => '1',
                    'original_county' => $csvLine[11],
                    'errors' => 'a:0:{}',
                    'original_service_frequency' => 'Quarterly',
                    'original_salesperson' => '',
                    'original_found_by_type' => '',
                    'notes' => '',
                    'original_day_of_the_week' => '',
                    'original_week_of_the_month' => '',
                    'initial_service_price' => '',
                    'original_service_type' => ''
                    // 'upload'
                    //  'name' => $csvLine[0] . ' ' . $csvLine[1],
                    //  'job' => $csvLine[2],
                ]);
            }
        }
        // dd($csvArray);
        return $this->sendResponse(true, 'Data Added Successfully');
    }

    public function list(Request $request, $id)
    {
        // $batches = PocomosImportBatch::where('office_id', $id)->with('office_detail', 'import_details')->get();
        // // $batches = $batches->orderBy('created_at','desc')->get();
        // $batches->map(function ($batches_data) {
        //     $find = PocomosImportCustomer::where('upload_batch_id', $batches_data['id'])->get()->count();
        //     $batches_data['count'] = $find;
        // });

        $sql = "SELECT
                    b.id AS 'id',
                    b.name AS 'batch_name',
                    IF(b.import_type = '', 'Customers', b.import_type) AS 'import_type',
                    o.name AS 'office_name',
                    (SELECT COUNT(*) FROM pocomos_import_customers c WHERE c.upload_batch_id = b.id) AS 'count',
                    b.date_created AS 'date_created',
                    CASE WHEN b.imported = 1 THEN 'Yes' ELSE 'No' END AS 'imported'
                FROM pocomos_import_batches b
                  JOIN pocomos_company_offices o ON b.office_id = o.id
                WHERE b.active = true
                  AND o.active = true";

        if ($request->search) {
            $search = "'%".$request->search."%'";
            $sql .= ' AND (b.name LIKE '.$search.' OR o.name LIKE '.$search.')';
        }

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $batches = DB::select(DB::raw(($sql)));

        return $this->sendResponse(true, 'List', [
            'batches' => $batches,
            'count' => $count,
        ]);
    }

    /**Batch records list details */
    public function getBatchRecords(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'batch_id' => 'required|exists:pocomos_import_batches,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $batch_id = $request->batch_id;

        $sql = "SELECT
                    c.id AS 'id',
                    CONCAT(c.first_name, ' ', c.last_name) AS 'name',
                    c.email AS 'email',
                    c.street AS 'street',
                    c.city AS 'city',
                    c.postal_code AS 'postal_code',
                    c.errors AS 'errors',
                    CASE WHEN c.imported = 1 THEN 'Yes' ELSE 'No' END AS 'imported'
                FROM pocomos_import_customers c
                WHERE c.upload_batch_id = $batch_id
                ";

        if (($request->search)) {
            $search = $request->search;
            $sql .= ' AND (first_name LIKE "'.$search.'" OR last_name LIKE "'.$search.'" OR street LIKE "'.$search.'" OR city LIKE "'.$search.'" OR postal_code LIKE "'.$search.'")';
        }
        $results = DB::select(DB::raw($sql));

        $results = array_map(function ($value) {
            $value = (array)$value;
            $value['errors'] = count(unserialize($value['errors']));

            return $value;
        }, $results);

        return $this->sendResponse(true, __('strings.list', ['name' => 'Batch records']), $results);
    }

    /**Get batch details */
    public function getBatchDetails($id)
    {
        $batch = PocomosImportBatch::with('office_detail', 'pest_agreement_detail')->findOrFail($id);
        $batchCustomers = PocomosImportCustomer::where('upload_batch_id', $id)->count();
        $batch->total_records = $batchCustomers;
        return $this->sendResponse(true, __('strings.details', ['name' => 'Batch']), $batch);
    }

    /**Finish batch */
    /**
     * Shows a single batch upload
     */
    public function finishBatch($id)
    {
        $uploadBatch = PocomosImportBatch::findOrFail($id);

        $args = array(
            'id' => $id,
            'alertReceivingUsers' => array(Session::get(config('constants.ACTIVE_OFFICE_USER_ID'))),
            // 'returnUrl' => $this->generateUrl('batch_show', array('id' => $uploadBatch->getId())),
            // 'linkText' => 'View Details'
        );
        $job = ProcessCustomerImportJob::dispatch($args);

        return $this->sendResponse(true, __('strings.message', ['message' => 'A job has been dispatched. You will receive an alert when it is completed']));
    }

    /* Update imported customer */
    public function updateImportCustomer(Request $request, $id)
    {
        $v = validator($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'company_name' => 'required',
            'street' => 'required',
            'suite' => 'required',
            'map_code' => 'required',
            'city' => 'required',
            'region' => 'required',
            'postal_code' => 'required',
            'phone' => 'required',
            'alt_phone' => 'required',
            'email' => 'required',
            'name_on_card' => 'required',
            'card_number' => 'required',
            'expiry_month' => 'required',
            'expiry_year' => 'required',
            'billing_street' => 'required',
            'billing_suite' => 'required',
            'billing_city' => 'required',
            'billing_region' => 'required',
            'billing_postal_code' => 'required',
            'tax_code_id' => 'required|exists:pocomos_tax_codes,id',
            'original_tax_code' => 'required',
            'recurring_price' => 'required',
            'previous_balance' => 'required',
            'salesperson_id' => 'required|exists:pocomos_salespeople,id',
            'last_technician_id' => 'required|exists:pocomos_technicians,id',
            'found_by_type_id' => 'required|exists:pocomos_marketing_types,id',
            'service_type_id' => 'required|exists:pocomos_pest_contract_service_types,id',
            'county_id' => 'required|exists:pocomos_counties,id',
            'service_frequency' => 'required',
            'week_of_the_month' => 'required',
            'day_of_the_week' => 'required',
            'date_signed_up' => 'required|date_format:Y-m-d',
            'date_last_service' => 'required|date_format:Y-m-d',
            'date_next_service' => 'required|date_format:Y-m-d',
            'region_id' => 'required|exists:orkestra_countries_regions,id',
            'billing_region_id' => 'required|exists:orkestra_countries_regions,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $input_details = $request->all();
        $import_customer =  PocomosImportCustomer::findOrFail($id);

        $update_details = [
            'region_id' => $input_details['region_id'] ?? null,
            'billing_region_id' => $input_details['billing_region_id'] ?? null,
            'company_name' => $input_details['company_name'] ?? null,
            'first_name' => $input_details['first_name'] ?? null,
            'last_name' => $input_details['last_name'] ?? null,
            'email' => $input_details['email'] ?? null,
            'phone' => $input_details['phone'] ?? null,
            'alt_phone' => $input_details['alt_phone'] ?? null,
            'street' => $input_details['street'] ?? null,
            'suite' => $input_details['suite'] ?? null,
            'city' => $input_details['city'] ?? null,
            'region' => $input_details['region'] ?? null,
            'postal_code' => $input_details['postal_code'] ?? null,
            'map_code' => $input_details['map_code'] ?? null,
            'name_on_card' => $input_details['name_on_card'] ?? null,
            'card_number' => $input_details['card_number'] ?? null,
            'exp_month' => $input_details['expiry_month'] ?? null,
            'exp_year' => $input_details['expiry_year'] ?? null,
            'billing_street' => $input_details['billing_street'] ?? null,
            'billing_suite' => $input_details['billing_suite'] ?? null,
            'billing_city' => $input_details['billing_city'] ?? null,
            'billing_region' => $input_details['billing_region'] ?? null,
            'billing_postal_code' => $input_details['billing_postal_code'] ?? null,
            'date_signed_up' => $input_details['date_signed_up'] ?? null,
            'date_last_service' => $input_details['date_last_service'] ?? null,
            'date_next_service' => $input_details['date_next_service'] ?? null,
            'service_frequency' => $input_details['service_frequency'] ?? null,
            'service_type_id' => $input_details['service_type_id'] ?? null,
            'week_of_the_month' => $input_details['week_of_the_month'] ?? null,
            'day_of_the_week' => $input_details['day_of_the_week'] ?? null,
            'external_identifer' => $input_details['first_name'] ?? null,
            'salesperson_id' => $input_details['salesperson_id'] ?? null,
            'previous_balance' => $input_details['previous_balance'] ?? null,
            'last_technician_id' => $input_details['last_technician_id'] ?? null,
            'tax_code_id' => $input_details['tax_code_id'] ?? null,
            'original_tax_code' => $input_details['tax_code_id'] ?? null,
            'found_by_type_id' => $input_details['found_by_type_id'] ?? null,
            // 'imported' => true,
            // 'active' => true,
            'original_county' => $input_details['county_id'] ?? null,
            // 'errors' => 'a:0:{}',
            'original_service_frequency' => $input_details['service_frequency'] ?? null,
            'original_salesperson' => $input_details['salesperson_id'] ?? null,
            'original_found_by_type' => $input_details['found_by_type_id'] ?? null,
            // 'notes' => '',
            'original_day_of_the_week' => $input_details['day_of_the_week'] ?? null,
            'original_week_of_the_month' => $input_details['week_of_the_month'] ?? null,
            'initial_service_price' => $input_details['recurring_price'] ?? null,
            'original_service_type' => $input_details['service_type_id'] ?? null
        ];

        $import_customer->update($update_details);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Details']));
    }

    /**Download import template */
    public function downloadImportTemplate()
    {
        return new Response($this->getCsvTemplate(), 200, array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="upload-template.csv"',
        ));
    }

    /**Get batch record details */
    public function getBatchRecordDetail($id)
    {
        $batchRecord = PocomosImportCustomer::with('batch_details', 'region_details', 'billing_region_details', 'county_details', 'salesperson_details', 'found_by_type_details', 'technician_details', 'tax_code_details', 'service_type_details')->findOrFail($id);
        return $this->sendResponse(true, __('strings.details', ['name' => 'Batch record']), $batchRecord);
    }
}
