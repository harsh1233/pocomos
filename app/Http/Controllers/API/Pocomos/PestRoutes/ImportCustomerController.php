<?php

namespace App\Http\Controllers\API\Pocomos\PestRoutes;

use DB;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\PocomosPestRoutesConfig;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosPestContractServiceType;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Pocomos\PocomosPestRoutesImportContract;
use App\Models\Pocomos\PocomosPestRoutesImportCustomer;

class ImportCustomerController extends Controller
{
    use Functions;

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

    //    return PocomosPestRoutesImportContract::whereOfficeId($request->office_id)->first();

        $query = PocomosPestRoutesImportCustomer::whereOfficeId($request->office_id)->whereStatus(1);

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $query->count();
        $query->skip($perPage * ($page - 1))->take($perPage);

        $pestRoutesImportCustomer = $query->get();

        return $this->sendResponse(true, __('strings.list', ['name' => 'Pest Routes Configurations']), [
            'pest_routes_import_customers' => $pestRoutesImportCustomer,
            'count' => $count
        ]);

        //entity.address }} {{ entity.city }} {{ entity.state }} {{ entity.zipcode
        // subscription_ids
        // status_text
    }

    public function tryImport(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $pestRoutesConfig = PocomosPestRoutesConfig::whereOfficeId($officeId)
                                            ->whereActive(1)->whereEnabled(1)->first();

        if (!$pestRoutesConfig) {
            throw new \Exception(__('strings.message', ['message' => "Pest Routes import isn't enabled for this office"]));
        }

        PocomosPestRoutesImportContract::whereOfficeId($officeId)->delete();

        $query = PocomosPestRoutesImportCustomer::whereOfficeId($request->office_id)->delete();

        $pestRoutesBaseUrl = 'https://subdomain.pestroutes.com';

        $baseUri = str_replace('subdomain', $pestRoutesConfig->sub_domain, $pestRoutesBaseUrl);
        $client = new Client(array('base_uri' => $baseUri));

        // return $response;

        //fetch all customers id from api
        $customerResponse = $client->get('/api/customer/search', array(
            'query' => array(
                'authenticationToken' => $pestRoutesConfig->auth_token,
                'authenticationKey' => $pestRoutesConfig->auth_key,
                'active' => 1,
                'dateAddedStart' => date("Y-m-d"),
            )
        ));


        $customerResponse = json_decode($customerResponse->getBody()->getContents(), true);

        //fetch all serviceType id from api
        $serviceTypeResponse = $client->get('/api/serviceType/search', array(
            'query' => array(
                'authenticationToken' => $pestRoutesConfig->auth_token,
                'authenticationKey' => $pestRoutesConfig->auth_key,
            )
        ));

        $serviceTypeResponse = json_decode($serviceTypeResponse->getBody()->getContents(), true);

        //fetch all employees id from api
        $employeeResponse = $client->get('/api/employee/search', array(
            'query' => array(
                'authenticationToken' => $pestRoutesConfig->auth_token,
                'authenticationKey' => $pestRoutesConfig->auth_key,
            )
        ));
        $employeeResponse = json_decode($employeeResponse->getBody()->getContents(), true);

        if (isset($customerResponse['success']) && $customerResponse['success'] == true && isset($serviceTypeResponse['success']) && $serviceTypeResponse['success'] == true && isset($employeeResponse['success']) && $employeeResponse['success'] == true) {
            $pestRoutesApiResponse = array(
                'customerResponse' => $customerResponse,
                'serviceTypeResponse' => $serviceTypeResponse,
                'employeeResponse' => $employeeResponse,
            );
        // return $pestRouteResponse;
        } else {
            throw new \Exception(__('strings.message', ['message' => "some error while fetching data from api"]));
        }

        // return $pestRoutesApiResponse;

        //customer data from api storing all ids into table
        $customerIdsData = $pestRoutesApiResponse['customerResponse']['customerIDs'];
        // $customerIdsData = [[1,2,3,4,5,6,7]];

        $customerApiProcessingTime = $pestRoutesApiResponse['customerResponse']['processingTime'];
        $customerTotalCount = $pestRoutesApiResponse['customerResponse']['count'];

        //serviceTypeResponse data from api storing all ids into table
        $serviceTypeIdsData = $pestRoutesApiResponse['serviceTypeResponse']['serviceTypeIDs'];
        $serviceTypeTotalCount = $pestRoutesApiResponse['serviceTypeResponse']['count'];

        //employeeResponse data from api storing all ids into table
        $employeeIdsData = $pestRoutesApiResponse['employeeResponse']['employeeIDs'];
        $employeeTotalCount = $pestRoutesApiResponse['employeeResponse']['count'];


        $importContract = PocomosPestRoutesImportContract::create([
            'domain' => $pestRoutesConfig->sub_domain,
            'customer_ids' => serialize($customerIdsData),
            'processing_time' => $customerApiProcessingTime,
            'total_customer_count' => $customerTotalCount,
            'service_type_ids' => serialize($serviceTypeIdsData),
            'total_service_type_count' => $serviceTypeTotalCount,
            'employee_ids' => serialize($employeeIdsData),
            'total_employee_count' => $employeeTotalCount,
            'office_id' => $officeId,
            'status' => 'Pending',
        ]);

        // return $this->sendResponse(true, __('strings.sucess', ['name' => 'customer data imported from pest routes to pocomos']));
        $this->importPestRoutesCustomerData($officeId, $importContract->id);
    }

    public function importPestRoutesCustomerData($officeId, $id)
    {
        $pestRoutesConfig = PocomosPestRoutesConfig::whereOfficeId($officeId)->whereActive(1)->whereEnabled(1)->first();

        if (!$pestRoutesConfig) {
            throw new \Exception(__('strings.message', ['message' => "Pest Routes Export isn't enabled for this office"]));
        }

        $importContract = PocomosPestRoutesImportContract::whereId($id)->whereOfficeId($officeId)->firstorfail();

        $pestRouteCustomerIds = $importContract->customer_ids;
        $pestRoutesTotalCount = $importContract->total_customer_count;
        $pestRouteCustomerId = array();
        $apiIterations = ceil($pestRoutesTotalCount / 1000);
        //dividing array into parts
        $newspliteCustomerIdsArray = $this->array_split($pestRouteCustomerIds, $apiIterations);

        $newspliteCustomerIdsArray = unserialize($newspliteCustomerIdsArray[0]);

        foreach ($newspliteCustomerIdsArray as $iteration) {
            $pestRouteCustomerId = array();
            foreach ($iteration as $pestroutesdata) {
                $pestRouteCustomerId[] = $pestroutesdata;
            }
            $pestRouteCustomerIds = "[".implode(',', $pestRouteCustomerId)."]";
            //$pestRouteCustomerIds = '[72104,72127,72132,72140]';
            $pestRoutesCustomers = $this->getCustomerDataFromPestRoutes($pestRoutesConfig, $pestRouteCustomerIds);
            $pestRoutesCustomersData = $pestRoutesCustomers['customers'];
            foreach ($pestRoutesCustomersData as $data) {
                PocomosPestRoutesImportCustomer::create([
                    'customer_id'           => $data['customerID'],
                    'bill_to_account_id'    => $data['billToAccountID'],
                    'pest_office_id'        => $data['officeID'],
                    'fname'                 => $data['fname'],
                    'lname'                 => $data['lname'],
                    'company_name'          => $data['companyName'],
                    'status'                => $data['status'],
                    'status_text'           => $data['statusText'],
                    'email'                 => $data['email'],
                    'phone1'                => $data['phone1'],
                    'subscription_ids'      => $data['subscriptionIDs'],
                    'subscription_data'     => $data['subscriptions'],
                    'address'               => $data['address'],
                    'city'                  => $data['city'],
                    'state'                 => $data['state'],
                    'zipcode'               => $data['zip'],
                    'office_id'             => $officeId,
                    'added_by'              => $data['addedByID'],
                ]);
            }
        }

        $pestRoutesCustomer = PocomosPestRoutesImportCustomer::whereOfficeId($officeId)->whereStatus(1)->get();

        //$newCustomerArray = $pestRoutesCustomer[1];
        ini_set('max_execution_time', '20000');
        foreach ($pestRoutesCustomer as $newCustomerArray) {
            $this->createCustomerAction($officeId, $newCustomerArray);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'customer data imported from pest routes to pocomos']));
    }

    public function createCustomerAction($officeId, $newCustomerArray)
    {
        $office = PocomosCompanyOffice::with('coontact_address')->findorfail($officeId);

        $foundByTypes = PocomosMarketingType::whereOfficeId($officeId)->whereActive(1)->whereEnabled(1)->get();

        $defaulMaketingType = "Door to Door";
        $foundByTypeObj = null;
        foreach ($foundByTypes as $foundByType) {
            if ($foundByType->name == $defaulMaketingType) {
                $foundByTypeObj = $foundByType;
            }
        }

        if ($foundByTypeObj==null) {
            $foundByTypeNew = PocomosMarketingType::create([
                'name' => $defaulMaketingType,
                'office_id' => $officeId,
            ]);

            $foundByTypeObj =  $foundByTypeNew->id; // default service type from pest office.;
        }

        $sendType = $foundByTypeObj;

        $salespersonData = PocomosSalesPeople::join('pocomos_company_office_users as pcou', 'pocomos_salespeople.user_id', 'pcou.id')
                ->join('pocomos_company_offices as pco', 'pcou.office_id', 'pco.id')
                ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->whereIn('pco.id', [$officeId])
                ->orderBy('ou.first_name')
                ->orderBy('ou.last_name')
                ->get();

        $salespersonID = $salespersonData;

        $salespersonID = $newCustomerArray->added_by;

        $officeUserProfile = PocomosCompanyOfficeUserProfile::whereProfileExternalId($salespersonID)->first();

        if ($officeUserProfile) {
            $salesperson = PocomosSalesPeople::join('pocomos_company_office_users as pcou', 'pocomos_salespeople.user_id', 'pcou.id')
                        ->join('pocomos_company_office_user_profiles as pcoup', 'pcou.profile_id', 'pcoup.id')
                        ->where('pocomos_salespeople.active', 1)
                        ->where('pcoup.id', $officeUserProfile->id)
                        ->first();
        } else {
            $salesperson =  $salespersonData[0];
        }

        $subscriptionData = $newCustomerArray->subscription_data;
        $customerCreationDate = date("m/d/y");

        foreach ($subscriptionData as $subscription) {
            $dateAdded = strtotime($subscription->dateAdded);
            $begin = date('m/d/y g:i A', $dateAdded);
            $startDate = date('m/d/y', $dateAdded);

            $initialQuote = $subscription->initialQuote;
            $initialPrice = $subscription->initialServiceTotal;
            $recurringPrice = $subscription->recurringCharge;
            $initialDiscount = $subscription->initialDiscount;
            $lastServiceDate = $subscription->lastCompleted;
            // $regularInitialPrice = $subscription->regularInitialPrice; //commented in symphony
            // $regularInitialPrice = $subscription->regularInitialPrice;
            $contractValue = $subscription->contractValue;
            $pestRouteServiceType = $subscription->serviceType;
            //$pestRouteServiceType = "Monthly Service Type";
            $serviceFrequency = $subscription->frequency;
            $renewalFrequency = $subscription->renewalFrequency;
            $billingFrequency = $subscription->billingFrequency;
            //$addedByID = $subscription->addedByID;

            switch ($serviceFrequency) {
                case '0':
                    $serviceFrequency = 'Custom';
                    break;
                case '7':
                    $serviceFrequency = 'Weekly';
                    break;
                case '14':
                    $serviceFrequency = 'Bi-weekly';
                    break;
                case '21':
                    $serviceFrequency = 'Tri-weekly';
                    break;
                case '30':
                    $serviceFrequency = 'Monthly';
                    break;
                case '60':
                    $serviceFrequency = 'Bi-monthly';
                    break;
                case '90':
                    $serviceFrequency = 'Quarterly';
                    break;
                case '180':
                    $serviceFrequency = 'Annually';
                    break;
                case '360':
                    $serviceFrequency = 'Annually';
                    break;
                default:
                    $serviceFrequency = 'Custom';
                    break;
            }

            switch ($renewalFrequency) {
                case '7':
                    $renewalFrequency = date('Y-m-d', strtotime("+1 week", strtotime($startDate)));
                    break;
                case '14':
                    $renewalFrequency = date('Y-m-d', strtotime("+2 week", strtotime($startDate)));
                    break;
                case '21':
                    $renewalFrequency = date('Y-m-d', strtotime("+3 week", strtotime($startDate)));
                    break;
                case '30':
                    $renewalFrequency = date('Y-m-d', strtotime("+1 month", strtotime($startDate)));
                    break;
                case '60':
                    $renewalFrequency = date('Y-m-d', strtotime("+60 days", strtotime($startDate)));
                    break;
                case '90':
                    $renewalFrequency = date('Y-m-d', strtotime("+90 days", strtotime($startDate)));
                    break;
                case '180':
                    $renewalFrequency = date('Y-m-d', strtotime("+6 months", strtotime($startDate)));
                    break;
                case '360':
                    $renewalFrequency = date('Y-m-d', strtotime("+12 months", strtotime($startDate)));
                    break;
                default:
                    $renewalFrequency = date('Y-m-d', strtotime("+12 months", strtotime($startDate)));
                    break;
            }

            switch ($billingFrequency) {
                case '-1':
                    $billingFrequency = 'Per service';
                    break;
                case '1':
                    $billingFrequency = 'Monthly';
                    break;
                case '30':
                    $billingFrequency = 'Initial monthly';
                    break;
                default:
                    $billingFrequency = 'Per service';
                    break;
            }

            $beginTime = new \DateTime($begin);

            $serviceTypeObj = null;

            $serviceTypes = PocomosPestContractServiceType::whereActive(1)->whereOfficeId($officeId)->get();

            foreach ($serviceTypes as $serviceType) {
                if ($serviceType->name == $pestRouteServiceType) {
                    $serviceTypeObj = $serviceType;
                }
            }

            if ($serviceTypeObj==null) {
                $serviceTypeNew =  PocomosPestContractServiceType::create([
                    'name' => $pestRouteServiceType,
                    'office_id' => $officeId,
                    'description' => $pestRouteServiceType,
                ]);

                $serviceTypeObj =  $serviceTypeNew->id; // default service type from pest office.;
            }

            $pocomos_contract['date_start'] = new \DateTime($startDate);
            $pocomos_contract['date_end'] = date('Y-m-d', strtotime('+1 year'));
            $pocomos_contract['billing_frequency'] = '';
            $pocomos_contract['tax_code_id'] = $additional_information['tax_code_id'] ?? null;
            $pocomos_contract['found_by_type_id'] = $billing_information['marketing_type_id'] ?? null;
            $pocomos_contract['salesperson_id'] = $billing_information['sales_person_id'] ?? null;
            $pocomos_contract['status'] = 'Active';
            $pocomos_contract['active'] = true;
            $cus_contract = PocomosContract::create($pocomos_contract);

            $pest_contract['service_frequency'] = $serviceFrequency;
            // $pest_contract['agreement_id'] = $pest_agreement_id;
            $pest_contract['service_type_id'] = $serviceTypeObj;
            $pest_contract['initial_price'] = (float) $initialPrice;
            $pest_contract['recurring_price'] = (float) $recurringPrice;
            $pest_contract['initial_discount'] = (float) $initialDiscount;
            $pest_contract['regular_initial_price'] = $initialQuote;
            $pest_contract['first_year_contract_value'] = $contractValue;
            $pest_contract_res = PocomosPestContract::create($pest_contract);
        }

        $region = $office->coontact_address->region->id;

        $phone_number['alias'] = 'Primary';
        $phone_number['number'] = $newCustomerArray->phone1;
        $phone_number['type'] = 'Mobile';
        $phone_number['active'] = true;
        $phone = PocomosPhoneNumber::create($phone_number);

        $contactAddress =  PocomosAddress::create([
            'city' => $newCustomerArray->city,
            'street' =>  $newCustomerArray->state,
            'postal_code' => $newCustomerArray->zipcode,
            'region_id' => $region,
            'suite' => $newCustomerArray->address,
            'phone_id' => $phone->id,
            'active' => 1,
            'valid' => 1,
            'validated' => 1,
        ]);

        $input_details['account_type'] = 'Residential';
        $input_details['contact_address_id'] = $contactAddress->id;
        $input_details['first_name'] = $newCustomerArray->fname;
        $input_details['last_name'] = $newCustomerArray->lname;
        $input_details['email'] = $newCustomerArray->email;
        $input_details['external_account_id'] = $newCustomerArray->customer_id;
        $input_details['active'] = true;
        $input_details['email_verified'] = true;
        $input_details['status'] = config('constants.ACTIVE');
        $input_details['company_name'] = $newCustomerArray->company_name;
        $customer = PocomosCustomer::create($input_details);

        $creationResult = $this->convertCustomerToEntity(null, $customer, $office);

        return $this->sendResponse(true, __('strings.message', ['name' => 'Customer Saved']));
    }

    public function getCustomerDataFromPestRoutes($pestRoutesConfig, $pestRouteCustomerIds)
    {
        $pestRoutesBaseUrl = 'https://subdomain.pestroutes.com';

        $baseUri = str_replace('subdomain', $pestRoutesConfig->sub_domain, $pestRoutesBaseUrl);

        try {
            $client = new Client(array('base_uri' => $baseUri));
            $response = $client->get('/api/customer/get', array(
                'query' => array(
                    'authenticationToken' => $pestRoutesConfig->auth_token,
                    'authenticationKey' => $pestRoutesConfig->auth_key,
                    'customerIDs' => $pestRouteCustomerIds,
                    'includeSubscriptions' => true,
                )
            ));
            $response = json_decode($response->getBody()->getContents(), true);

            if (isset($response['success']) && $response['success'] == true) {
                return $response;
            } else {
                return ['success' => false];
            }
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'Client error') !== false || strpos($errorMessage, 'Server error') !== false) {
                $errorMessage = str_replace('Client error', 'API Error', $e->getMessage());
                $errorMessage = str_replace('Server error', 'API Error', $e->getMessage());
            } else {
                $errorMessage = 'Local error: ' . $errorMessage;
            }
        }
    }

    // split the given array into n number of pieces
    public function array_split($array, $pieces = 2)
    {
        if ($pieces < 2) {
            return array($array);
        }
        $newCount = ceil(count($array) / $pieces);
        $a = array_slice($array, 0, $newCount);
        $b = $this->array_split(array_slice($array, $newCount), $pieces - 1);
        return array_merge(array($a), $b);
    }
}
