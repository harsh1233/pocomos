<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosJob;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosArea;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosPest;
use App\Http\Controllers\Controller;
use App\Models\Orkestra\OrkestraFile;
use App\Http\Requests\CustomerRequest;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosJobPest;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosWebhook;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosJobProduct;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosSubCustomer;
use App\Models\Pocomos\PocomosCustomerNote;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosJobChecklist;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosCustomersNote;
use Illuminate\Support\Facades\DB as FacadesDB;
use App\Models\Pocomos\PocomosJobsProductsAreas;
use App\Models\Pocomos\PocomosPestInvoiceSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosTechnicianChecklist;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosCustomersNotifyMobilePhone;
use App\Models\Pocomos\PocomosCustomerState;
use App\Models\Pocomos\PocomosPestOfficeDefaultInvoiceNote;

class ServiceInvoiceController extends Controller
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

        $find_invoice_in_job = PocomosJob::whereIn('contract_id', $contract)->where('date_scheduled', Carbon::now()->format('Y-m-d'))->first();

        return $this->sendResponse(true, 'List of upcoming invoices', $find_invoice_in_job);
    }

    // for basic data also (before begin service)
    // get prefilled service info., chem sheets data
    public function get(Request $request, $jobId)
    {
        // return 11;
        $v = validator($request->all(), [
            'customer_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $job = PocomosJob::with([
            'invoice',
            'pestContractTags',
            'contract',
            'contract.service_type_details',
            'contract.contract_details.profile_details.customer.state_details',
            'jobs_pests',
            'customFields.custom_field' => function ($q) {
                $q->whereTechVisible(1);
            },
            'signature_detail',
            'job_checklists'
            ])
            ->whereId($jobId)->first();

        $jobProducts = PocomosJobProduct::with('product', 'areas', 'service', 'invoice_item')->whereJobId($jobId)->get();

        $custWorkOrderNote = PocomosCustomer::with('worker_notes_details.note')->whereId($request->customer_id)->first();

        // Date Serviced = job date_completed
        // job time_begin, time_end
        // service type = type
        // jobs_pests for targeted pests
        // job_products for chem sheet data

        return $this->sendResponse(true, 'Service invoice data', [
            'job'   => $job,
            'customer_workorder_note'   => $custWorkOrderNote,
            'job_products'   => $jobProducts,
        ]);
    }


    public function startService(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'job_id' => 'required',
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = $this->findOneByIdAndOffice_customerRepo($custId, $request->office_id);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Customer.']));
        }

        $job = PocomosJob::whereId($request->job_id)->firstOrFail();
        $job->time_begin = date('H:i:s');
        $job->save();

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Service started']));
    }

    public function getFormData(Request $request)
    {
        $v = validator($request->all(), [
            // 'job_id' => 'required',
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        /* $technicians = PocomosTechnician::join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
                ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->where('pcou.office_id', $officeId)
                ->where('pocomos_technicians.active', 1)
                ->get(); */

        $technicians = PocomosTechnician::select('*', 'pocomos_technicians.id')
                ->with('licenses')
                ->join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
                ->join('pocomos_company_offices as pco', 'pcou.office_id', 'pco.id')
                ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->where('pco.id', $officeId)
                ->where('pocomos_technicians.active', 1)
                ->where('pcou.active', 1)
                ->where('ou.active', 1)
                ->get();

        //for pests (may require whereType('Regular'))
        $pests = PocomosPest::whereOfficeId($officeId)->whereActive(1)->orderBy('position')->get();

        $products = PocomosPestProduct::whereOfficeId($officeId)->whereActive(1)->whereEnabled(1)->get();

        $areas = PocomosArea::whereOfficeId($officeId)->whereActive(1)->orderBy('position')->get();

        //for application type
        $services = PocomosService::whereOfficeId($officeId)->whereActive(1)->get();

        //for technician note templates
        $invoiceConfig = PocomosPestInvoiceSetting::where('office_id', $officeId)->firstOrFail();

        $pestOfficeDefaultInvoiceNotes = PocomosPestOfficeDefaultInvoiceNote::whereOfficeConfigurationId($invoiceConfig->id)
                                    ->whereActive(1)->whereDeleted(0)->get();

        $technicianChecklist = PocomosTechnicianChecklist::whereOfficeId($officeId)->whereActive(1)->whereDeleted(0)->get();

        $taxCode = PocomosTaxCode::first();

        return $this->sendResponse(true, 'Service invoice data', [
            'technicians'   => $technicians,
            'pests'   => $pests,
            'products'   => $products,
            'areas'   => $areas,
            'services'   => $services,
            'tech_note_templates'   => $pestOfficeDefaultInvoiceNotes,
            'tech_checklist'   => $technicianChecklist,
            'tax_code'   => $taxCode,
        ]);
    }

    public function getWindDirectionName($directionDegree)
    {
        if ($directionDegree < 90) {
            return 'North';
        }

        if ($directionDegree < 180) {
            return 'East';
        }

        if ($directionDegree < 270) {
            return 'South';
        }

        return 'West';
    }

    public function weatherToString($weather)
    {
        $result = [];

        if (array_key_exists('weather_conditions', $weather) && strlen($weather['weather_conditions'])) {
            $result[] = $weather['weather_conditions'];
        }

        if (array_key_exists('temp', $weather) && strlen($weather['temp'])) {
            $result[] = "Temperature: {$weather['temp']} F";
        }

        if (array_key_exists('humidity', $weather) && strlen($weather['humidity'])) {
            $result[] = "Humidity: {$weather['humidity']}%";
        }

        if (array_key_exists('wind_speed', $weather) && strlen($weather['wind_speed'])) {
            $result[] = "Wind speed: {$weather['wind_speed']}m/h";
        }

        if (array_key_exists('wind_direction', $weather) && strlen($weather['wind_direction'])) {
            $result[] = "Wind direction: {$weather['wind_direction']}";

            if (array_key_exists('wind_degree', $weather) && strlen($weather['wind_degree'])) {
                $result[] = "{$weather['wind_degree']} Deg.";
            }
        }

        return join(', ', $result);
    }

    public function getWeather(Request $request, $custId)
    {
        $v = validator($request->all(), [
            // 'job_id' => 'required',
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $customer = PocomosCustomer::with('contact_address.region')->whereId($custId)->first();

        $postalCode = $customer->contact_address->postal_code;
        $countryCode = $customer->contact_address->region->country_detail->code;

        // $postalCode = 98290;
        // $countryCode = 'us';

        // $openweatherApiKey = 'cdbc0127020f1106568a02706d064195';

        // return env('OPEN_WEATHER_APIKEY');

        $apiUrl = 'https://api.openweathermap.org/data/2.5/weather?APPID=' . env('OPEN_WEATHER_APIKEY') . '&units=imperial&zip=' . $postalCode . ',' . $countryCode;


        $response = json_decode(file_get_contents($apiUrl), true);
        // return $response;
        // return 22;

        if ($response['cod'] == 200) {
            if ($response['wind']['deg'] == null || $response['wind']['deg'] == 'undefined') {
                $result = [
                    'success' => true,
                    'weather_conditions' => ucfirst($response['weather'][0]['description']),
                    'temp' => $response['main']['temp'],
                    'humidity' => $response['main']['humidity'],
                    'wind_speed' => $response['wind']['speed'],
                    'wind_direction' => 'N/A',
                    'wind_degree' => 'N/A',
                ];
            } else {
                $result = [
                    'success' => true,
                    'weather_conditions' => ucfirst($response['weather'][0]['description']),
                    'temp' => $response['main']['temp'],
                    'humidity' => $response['main']['humidity'],
                    'wind_speed' => $response['wind']['speed'],
                    'wind_direction' => $this->getWindDirectionName($response['wind']['deg']),
                    'wind_degree' => $response['wind']['deg'],
                ];
            }
            $result['formatted'] = self::weatherToString($result);
        } else {
            $result = [
                'success' => false,
                'message' => $response['message']
            ];
        }

        return $this->sendResponse(true, 'weather', $result);
    }


    public function resendMailForm(Request $request, $custId)
    {
        $v = validator($request->all(), [
            // 'job_id' => 'required',
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $customer = PocomosCustomer::with('sales_profile')->findorfail($custId);

        $profileId = $customer->sales_profile->id;

        $invoices = PocomosInvoice::leftJoin('pocomos_jobs as pj', 'pocomos_invoices.id', 'pj.invoice_id')
            ->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->where('pc.profile_id', $profileId)
            ->whereNotIn('pocomos_invoices.status', ['Paid', 'Cancelled'])
            ->where('pj.status', '!=', 'Cancelled')
            ->orderBy('pocomos_invoices.date_due')
            ->get();

        $contract = PocomosPestContract::join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_agreements as pa', 'pc.agreement_id', 'pa.id')
                ->where('pcsp.profile_id', $profileId)
                ->get();

        return $this->sendResponse(true, 'resendMailForm', [
            'invoices'   => $invoices,
            'contract'   => $contract,
        ]);
    }


    // for invoice items table, completeJobAction
    public function invoiceItems(Request $request, $jobId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $profile = PocomosCustomerSalesProfile::with('points_account')->where('customer_id', $request->customer_id)->first();

        $job = PocomosJob::with('invoice.invoice_items')->findOrFail($jobId);

        // a/c credit = profile > points_account > balance

        return $this->sendResponse(
            true,
            __('strings.details', ['name' => 'Invoice items table']),
            [
                'profile' => $profile,
                'job' => $job
            ]
        );
    }

    public function updateinvoiceItem(Request $request, $itemId)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'invoice_id' => 'required|exists:pocomos_invoices,id',
            'description' => 'required',
            'price' => 'required',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // $customerId = $request->customer_id;

        $invoice = $this->getInvoice($request->customer_id, $request->invoice_id);

        if ($invoice->closed) {
            return $this->sendResponse(false, 'Invoice Closed Unable to edit invoice item');
        }

        $invoiceItem = PocomosInvoiceItems::where('id', $itemId)->firstOrFail();
        $invoiceItem->description = $request->description;
        $invoiceItem->price = $request->price;
        $invoiceItem->save();

        // $this->getSession()->getFlashBag()->add('success', 'The invoice item has been updated successfully.');
        return $this->sendResponse(true, __('strings.update', ['name' => 'The invoice item has been']));
    }

    private function getInvoice($customerId, $invoiceId)
    {
        $invoice = PocomosInvoice::join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->where('pcsp.customer_id', $customerId)
            ->where('pocomos_invoices.id', $invoiceId)
            ->first();

        if (!$invoice) {
            $subCustomer = PocomosSubCustomer::whereChildId($customerId)->first();
            if ($subCustomer && $subCustomer->parent_id !== null) {
                $parentCust = $subCustomer->getParentNew();
                $invoice = $this->getInvoice($parentCust->id, $invoiceId);
            } else {
                throw new \Exception(__('strings.message', ['message' => 'Unable to locate Invoice entity']));
            }
        }

        return $invoice;
    }



    public function saveAndComplete(Request $request, $jobId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'targeted_pests' => 'required|array',
            'tech_checklists' => 'array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        // return 11;
        $job = PocomosJob::with('invoice_detail')->findOrFail($jobId);
        // return 11;
        $job->time_begin = $request->time_begin;
        $job->time_end = $request->time_end;
        $job->date_completed = $request->date_serviced;
        $job->technician_id = $request->technician_id;
        $job->technician_note = $request->technician_note ?? "";
        $job->weather = $request->weather ?? "";

        if ($request->complete_job) {
            $job->status = 'Complete';
        }

        $job->save();

        PocomosJobPest::whereJobId($jobId)->delete();

        foreach ($request->targeted_pests as $targeted_pest) {
            $job_service['pest_id'] = $targeted_pest;
            $job_service['job_id'] = $jobId;
            $insert_job = PocomosJobPest::create($job_service);
        }

        if(isset($request->price_total)){
            $job->invoice->update(['balance' => $request->price_total]);

            if(isset($request->current_contract)){
                $invIds = $this->findCompletedServicesForContract($request->current_contract)->pluck('invoice_id');

                $totalInvBal = PocomosInvoice::find($invIds)->sum('balance');

                if(isset($request->customer_id)){
                    PocomosCustomerState::whereCustomerId($request->customer_id)->update([
                        'balance_outstanding' => $totalInvBal
                    ]);
                }
            }
        }

        if ($request->chemsheets) {
            // return 11;
            foreach ($request->chemsheets as $product) {
                if (isset($product['price'])) {
                    // return $product['price']['price'];
                    // $desc = $product['amount'].''.$product['dilution_unit'];

                    $item['tax_code_id'] = $product['price']['tax_code_id'];    // get form data
                    $item['sales_tax'] =  $product['price']['tax_rate'];    // get form data
                    $item['invoice_id'] = $job->invoice_id;
                    $item['description'] = '';  //$desc
                    $item['price'] = $product['price']['price'];
                    $item['active'] = true;
                    $item['type'] = '';
                    $invoiceItem = PocomosInvoiceItems::create($item);
                }

                $jobProduct = PocomosJobProduct::create([
                    'job_id' => $jobId,
                    'product_id' => $product['product_id'],
                    'dilution_rate' => $product['dilution_rate'],
                    'dilution_quantity' => 'per',
                    'dilution_unit' => $product['dilution_unit'],                   // get from pest_products
                    'dilution_liquid_unit' => $product['dilution_liquid_unit'],
                    'service_id' => $product['app_type'],
                    'amount' => $product['amount'],
                    'application_rate' => $product['application_rate'],
                    'invoice_item_id' => $invoiceItem->id,
                    'active' => 1,
                ]);

                if (isset($product['areas'])) {
                    foreach ($product['areas'] as $area) {
                        PocomosJobsProductsAreas::create([
                            'applied_product_id' => $jobProduct->id,
                            'area_id' => $area,
                        ]);
                    }
                }
            }
        }

        if ($request->save_to_permanent_notes) {
            // $note['user_id'] = $or_user->id ?? null;
            $note['summary'] = $request->technician_note;
            $note['interaction_type'] = 'Other';
            $note['active'] = true;
            $note['body'] = '';
            $createdNote = PocomosNote::create($note);

            // return $createdNote;

            // PocomosCustomerNote::create([
            //     'customer_id' => $custId,
            //     'note_id' => $createdNote->id,
            // ]);
        }

        if ($request->file('signature')) {
            
            $signature = $request->file('signature');
            //store file into document folder
            $sign_detail['filename'] = $signature->getClientOriginalName();
            $sign_detail['mime_type'] = $signature->getMimeType();
            $sign_detail['file_size'] = $signature->getSize();
            $sign_detail['active'] = 1;
            $sign_detail['md5_hash'] =  md5_file($signature->getRealPath());

            $url = date('h:i:s a', time())."signature" . "/" . $sign_detail['filename'];
            Storage::disk('s3')->put($url, file_get_contents($signature));
            $sign_detail['path'] = Storage::disk('s3')->url($url);

            $agreement_sign =  OrkestraFile::create($sign_detail);
            // $input_details['logo_file_id'] = $agreement_sign->id;

            PocomosJob::findOrFail($jobId)->update([
                'signature_id' => $agreement_sign->id
            ]);
        }

        if ($request->file('photos')) {
            foreach ($request->file('photos') as $photo) {
                //store file into document folder
                $input_details['path'] = $photo->store('public/files');

                //store your file into database
                $input_details['filename'] = $photo->getClientOriginalName();
                $input_details['mime_type'] = $photo->getMimeType();
                $input_details['file_size'] = $photo->getSize();
                $input_details['active'] = 1;
                $input_details['md5_hash'] =  md5_file($photo->getRealPath());
                $input_details['job_id'] =  $jobId;
                $OrkestraFile =  OrkestraFile::create($input_details);
            }
        }

        PocomosJobChecklist::whereJobId($jobId)->delete();

        if ($request->tech_checklists) {
            foreach ($request->tech_checklists as $checklist) {
                PocomosJobChecklist::create([
                    'job_id' => $jobId,
                    'checklist_id' => $checklist,
                ]);
            }
        }

        return $this->sendResponse(true, __('strings.update', ['name' => 'The service has been']));
    }


    public function updateChanges(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        if ($request->chemsheets) {
            // return $request->chemsheets;
            foreach ($request->chemsheets as $product) {
                $itemId = null;
                if (isset($product['price']) && $product['price']['price'] > 0 && !isset($product['price']['item_id'])) {
                    // return 118;
                    // $desc = $product['amount'].''.$product['dilution_unit'];

                    $item['tax_code_id'] = $product['price']['tax_code_id'];    // get form data
                    $item['sales_tax'] =  $product['price']['tax_rate'];    // get form data
                    $item['invoice_id'] = $request['invoice_id'];
                    $item['description'] = '';  //$desc
                    $item['price'] = $product['price']['price'];
                    $item['active'] = true;
                    $item['type'] = '';
                    $invoiceItem = PocomosInvoiceItems::create($item);

                    $itemId = $invoiceItem->id;
                } elseif (isset($product['price']) && isset($product['price']['item_id'])) {
                    // return 11;
                    $desc = $product['amount'].''.$product['dilution_unit'];

                    // $item['tax_code_id'] = $product['price']['tax_code_id'];
                    // $item['sales_tax'] =  $product['price']['tax_rate'];
                    $item['price'] = $product['price']['price'];
                    $item['description'] = $desc;
                    $item['active'] = true;
                    $invoiceItem = PocomosInvoiceItems::whereId($product['price']['item_id'])->update($item);

                    if ($item['price'] > 0) {
                        $itemId =  $product['price']['item_id'];
                    } else {
                        $itemId = null;
                    }
                }

                // return $product['id'];
                $jobProduct = PocomosJobProduct::findorfail($product['id']);
                $jobProduct->product_id = $product['product_id'];
                $jobProduct->dilution_rate = $product['dilution_rate'];
                $jobProduct->dilution_quantity = 'per';
                $jobProduct->dilution_unit = $product['dilution_unit'];
                $jobProduct->dilution_liquid_unit = $product['dilution_liquid_unit'];
                $jobProduct->service_id = $product['app_type'];
                $jobProduct->amount = $product['amount'];
                $jobProduct->application_rate = $product['application_rate'];
                $jobProduct->invoice_item_id = $itemId;
                $jobProduct->save();

                PocomosJobsProductsAreas::whereAppliedProductId($product['id'])->delete();

                if (isset($product['areas'])) {
                    foreach ($product['areas'] as $area) {
                        PocomosJobsProductsAreas::create([
                            'applied_product_id' => $product['id'],
                            'area_id' => $area,
                        ]);
                    }
                }
            }
        }

        return $this->sendResponse(true, __('strings.update', ['name' => 'The service has been']));
    }

    public function removeChemsheets(Request $request)
    {
        $v = validator($request->all(), [
            'job_product_ids' => 'required|array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        // foreach($request->job_product_ids as $jobProductId){
        PocomosJobsProductsAreas::whereIn('applied_product_id', $request->job_product_ids)->delete();
        PocomosJobProduct::whereIn('id', $request->job_product_ids)->delete();
        // }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Removed chemsheets']));
    }


    public function enrouteSms(Request $request, $jobId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'customer_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();
        if (!$profile) {
            return $this->sendResponse(false, 'Customer profile not found.');
        }

        $find_phone_ids = PocomosCustomersNotifyMobilePhone::where('profile_id', $profile->id)->pluck('phone_id')->toArray();

        $phoneDetail = PocomosPhoneNumber::whereIn('id', $find_phone_ids)->whereActive(1)->first();
        if (!$phoneDetail) {
            throw new \Exception(__('strings.message', ['message' => 'Customer has no mobile phone']));
        }

        //get same technician from job filters
        $technicianName = $request->technician_name;

        $office =  PocomosCompanyOffice::findOrFail($officeId);

        $message = "I'm on my way";
        if ($technicianName) {
            $message .= ' -' . $technicianName;
        }
        $message .= ', ' . $office->name;

        // return $message;

        $this->sendMessage($profile->office_details, $phoneDetail, $message);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Text message has been sent']));
    }

    //sm Paymentprocess
    public function chargeAndCompleteJobAction(Request $request, $jobid)
    {
        // return $jobid;

        $v = validator($request->all(), [
            'office_id' => 'required',
            'customer_id' => 'required',
            'payments' => 'required|array',
            'payments.*.method' => 'required|in:card,ach,cash,check,account_credit,processed_outside,points',
            // 'payments.*.referenceNumber' => 'required',
            // 'payments.*.description' => 'required',
            'payments.*.amount' => 'required',
            'payments.*.account_id' => 'required|exists:orkestra_accounts,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $payments = $request->payments;

        $generalValues = $request->only('customer_id', 'invoice_id', 'office_id', 'current_office_user_id');

        $customer = PocomosCustomer::with('sales_profile')->where('id', $request->customer_id)->first();
        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find the Customer.');
        }

        $job = PocomosJob::with(['invoice.contract.profile_details.customer', 'technician'])->where('id', $jobid)->first();
        if (!$job) {
            return $this->sendResponse(false, 'Unable to find the Job.');
        }

        $profile = $customer->sales_profile;

        $invoice = $job->invoice;

        // $profile = $this->getInvoiceProfile($invoice, $profile);

        // try {
            // FacadesDB::beginTransaction();

            foreach ($payments as $value) {
                $result = $this->processPayment($invoice->id, $generalValues, $value, $request->current_office_user_id);
                // return 99;
            }
            // FacadesDB::commit();
        // } catch (\Exception $e) {
            // FacadesDB::rollback();
            // dd(11);
            // throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        // }

        $jsonarray = ['customerInfo' => $customer, 'techInfo' => $job->technician];
        $data_string = serialize($jsonarray);

        /* $webhooks = PocomosWebhook::where('office_id', $request->office_id)->get();

        foreach ($webhooks as $webhook){
            if($webhook->send_on == 'Job Completion') {
                $url = $webhook->webhook_url;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($data_string))
                );
                $result = curl_exec($ch);
            }
        } */

        return $this->sendResponse(true, 'Payment has been processed successfully.');
    }

    /* public function getInvoiceProfile($invoice, $profile)
    {
        $invoiceProfile = $invoice->contract;

        if ($invoiceProfile->profile_id !== $profile->id) {
            // $sub_customers = PocomosSubCustomer::where('parent_id', $customer_id)->get();

            $parentRelationship = PocomosSubCustomer::where('parent_id', $profile->customer_id)->first();

            if ($parentRelationship) {
                $custId = $parentRelationship->parent_id;
                $profile = PocomosCustomerSalesProfile::whereCustomerId($custId)->first();
            }
            if ($invoiceProfile->profile_id !== $profile->id) {
                throw new \Exception(__('strings.message', ['message' => 'Unable to find the Invoice Entitiy.']));
            }
        }
        return $profile;
    } */

    public function previousChemical($custId)
    {
        $customer = PocomosCustomer::findOrFail($custId);

        $job = PocomosJob::join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->join('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
            ->whereNotNull('pocomos_jobs.date_completed')
            ->orderBy('pocomos_jobs.date_completed', 'desc')
            ->first();

        if (!$job) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Job.']));
        }

        return $this->sendResponse(true, 'get from last job', $job);
    }
}
