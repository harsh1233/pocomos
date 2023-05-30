<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use PDF;
use Excel;
use DateTime;
use App\Jobs\SearchStateJob;
use App\Jobs\SendMassSmsJob;
use Illuminate\Http\Request;
use App\Jobs\ExportCustomers;
use App\Jobs\ContractStateJob;
use App\Jobs\CustomerStateJob;
use App\Jobs\SendMassEmailJob;
use App\Exports\ExportQrReport;
use App\Jobs\ResendBulkEmailJob;
use App\Models\Pocomos\PocomosJob;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosLead;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosPest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosEmail;
use App\Models\Pocomos\PocomosRoute;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SendEmailCustomerExport;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Orkestra\OrkestraUser;
use Illuminate\Support\Facades\Crypt;
use App\Http\Requests\CustomerRequest;
use App\Mail\RemoteCompletionCustomer;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosLeadNote;
use App\Models\Pocomos\PocomosSchedule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Pocomos\PocomosLeadQuote;
use App\Mail\RemoteCompletionRecruitment;
use App\Models\Pocomos\PocomosRouteSlots;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosCustomField;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosSubCustomer;
use App\Notifications\TaskAddNotification;
use App\Jobs\SendEmailToOfficeCustomersJob;
use App\Models\Pocomos\PocomosFormVariable;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosLeadQuoteTag;
use App\Models\Pocomos\PocomosLeadQuotPest;
use App\Models\Pocomos\PocomosLeadReminder;
use App\Models\Pocomos\PocomosPestContract;
use Illuminate\Console\Scheduling\Schedule;
use App\Models\Pocomos\PocomosCustomersFile;
use App\Models\Pocomos\PocomosCustomersNote;
use App\Models\Pocomos\PocomosCustomerState;
use App\Models\Pocomos\PocomosMissionConfig;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Pocomos\PocomosSmsFormLetter;
use App\Http\Requests\CustomerAdvanceRequest;
use App\Models\Pocomos\PocomosCustomersPhone;
use App\Http\Requests\CustomerAgreementRequest;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosPestContractsPest;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosPestContractsInvoice;
use App\Models\Pocomos\PocomosPestDiscountTypeItem;
use App\Models\Pocomos\PocomosReportsContractState;
use Illuminate\Support\Facades\Response as Download;
use App\Models\Pocomos\PocomosCustomersWorkorderNote;
use App\Models\Pocomos\PocomosLeadQuoteSpecialtyPest;
use App\Models\Pocomos\PocomosPestContractServiceType;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Pocomos\PocomosCustomersNotifyMobilePhone;
use App\Models\Pocomos\PocomosPestContractsSpecialtyPest;


class CustomerController extends Controller
{
    use Functions;

    /**
     * API for create of customer
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(CustomerRequest $request)
    {
        DB::beginTransaction();
        $res = array();
        try {
            $service_address = ($request->service_address ?? array());
            $billing_information = ($request->billing_information ?? array());
            $same_as_service_address = ($request->billing_information ? $request->billing_information['same_as_service_address'] ?? null : null);
            $subscribe_to_mailing_list = ($request->billing_information ? $request->billing_information['subscribe_to_mailing_list'] ?? false : false);
            $service_information = ($request->service_information ?? array());
            $pricing_information = ($request->service_information ? $request->service_information['pricing_information'] ?? array() : array());
            $scheduling_information = ($request->service_information ? $request->service_information['scheduling_information'] ?? array() : array());
            $additional_information = ($request->service_information ? $request->service_information['additional_information'] ?? array() : array());
            $options = ($request->service_information ? $request->service_information['options'] ?? array() : array());
            $tags = ($request->service_information ? $request->service_information['tags'] ?? array() : array());
            $targeted_pests = ($request->service_information ? $request->service_information['targeted_pests'] ?? array() : array());
            $specialty_pests = ($request->service_information ? $request->service_information['specialty_pests'] ?? array() : array());
            $agreement_input = ($request->agreement ?? array());
            $custom_fields = ($request->service_information ? $request->service_information['additional_information']['custom_fields'] ?? array() : array());
            $pest_discount_types = ($request->service_information ? $request->service_information['pest_discount_types'] ?? array() : array());

            $input_details['first_name'] = $service_address['first_name'] ?? '';
            $input_details['last_name'] = $service_address['last_name'] ?? '';
            $input_details['email'] = $service_address['email'] ?? '';
            $input_details['active'] = true;
            $input_details['email_verified'] = false;
            $input_details['company_name'] = $service_address['company_name'] ?? '';
            $input_details['secondary_emails'] = implode(',', $service_address['secondary_emails'] ?? array());
            $input_details['account_type'] = $service_address['account_type'] ?? null;

            $phone = null;
            if (isset($service_address['phone'])) {
                $phone_number['alias'] = 'Primary';
                $phone_number['number'] = $service_address['phone'];
                $phone_number['type'] = $service_address['phone_type'];
                $phone_number['active'] = true;
                $phone = PocomosPhoneNumber::create($phone_number);
            }

            $alt_phone = null;
            if (isset($service_address['alt_phone'])) {
                $phone_number['alias'] = 'Alternate';
                $phone_number['number'] = $service_address['alt_phone'];
                $phone_number['type'] = $service_address['alt_phone_type'];
                $phone_number['active'] = true;
                $alt_phone = PocomosPhoneNumber::create($phone_number);
            }

            $contact_address['street'] = $service_address['street'] ?? '';
            $contact_address['suite'] = $service_address['suite'] ?? '';
            $contact_address['city'] = $service_address['city'] ?? '';
            $contact_address['postal_code'] = $service_address['postal'] ?? '';
            $contact_address['validated'] = 2;
            $contact_address['valid'] = 1;
            $contact_address['phone_id'] = ($phone ? $phone->id ?? null : null);
            $contact_address['alt_phone_id'] = ($alt_phone ? $alt_phone->id ?? null : null);
            $contact_address['region_id'] = $service_address['region_id'] ?? null;
            $contact_address['active'] = true;
            $c_address = PocomosAddress::create($contact_address);

            $b_address = null;
            if (!$same_as_service_address && $same_as_service_address != null) {
                $billing_address['street'] = $billing_information['street'] ?? '';
                $billing_address['suite'] = $billing_information['suite'] ?? '';
                $billing_address['city'] = $billing_information['city'] ?? '';
                $billing_address['postal_code'] = $billing_information['postal'] ?? '';
                $billing_address['validated'] = 2;
                $billing_address['valid'] = 1;
                $billing_address['phone_id'] = ($phone ? $phone->id ?? null : null);
                $billing_address['alt_phone_id'] = ($alt_phone ? $alt_phone->id ?? null : null);
                $billing_address['region_id'] = $billing_information['region_id'] ?? null;
                $billing_address['active'] = true;
                $b_address = PocomosAddress::create($billing_address);
            } else {
                $billing_address['street'] = $service_address['street'] ?? '';
                $billing_address['suite'] = $service_address['suite'] ?? '';
                $billing_address['city'] = $service_address['city'] ?? '';
                $billing_address['postal_code'] = $service_address['postal'] ?? '';
                $billing_address['validated'] = 2;
                $billing_address['valid'] = 1;
                $billing_address['phone_id'] = ($phone ? $phone->id ?? null : null);
                $billing_address['alt_phone_id'] = ($alt_phone ? $alt_phone->id ?? null : null);
                $billing_address['region_id'] = $service_address['region_id'] ?? null;
                $billing_address['active'] = true;
                $b_address = PocomosAddress::create($billing_address);
            }

            $input_details['contact_address_id'] = ($c_address ? $c_address->id ?? null : null);
            $input_details['billing_address_id'] = ($b_address ? $b_address->id ?? null : null);
            $input_details['subscribed'] = $subscribe_to_mailing_list;
            $input_details['billing_name'] = $billing_information['billing_name'] ?? null;
            $input_details['status'] = config('constants.ACTIVE');
            $input_details['external_account_id'] = '';
            $input_details['default_job_duration'] = $scheduling_information['job_duration'];

            $autopay_account = null;
            if (isset($billing_information['is_enroll_auto_pay']) && $billing_information['is_enroll_auto_pay']) {
                $account_details['ip_address'] = '';
                $account_details['alias'] = $billing_information['alias'] ?? 'Auto-pay account';
                $account_details['name'] = ($service_address['first_name'] ?? '') . ' ' . ($service_address['last_name'] ?? '');
                $account_details['address'] = $billing_information['street'] ?? '' . ', ' . $billing_information['suite'] ?? '' . ', ' . $billing_information['city'] ?? '' . ', ' . $billing_information['postal'] ?? '';
                $account_details['city'] = $service_address['city'] ?? '';
                $account_details['region'] = $billing_information['region'] ?? '';
                $account_details['country'] = $billing_information['country'] ?? '';
                $account_details['postal_code'] = $billing_information['postal'] ?? '';
                $account_details['phoneNumber'] = $service_address['phone'] ?? '';
                $account_details['active'] = true;
                $account_details['account_number'] = $billing_information['account_number'] ?? '';
                if($billing_information['payment_method'] == 'ach'){
                    $account_details['type'] = 'BankAccount';
                    $account_details['ach_routing_number'] = $billing_information['routing_number'] ?? '';
                }else{
                    $account_details['type'] = 'CardAccount';
                    $account_details['card_exp_month'] = $billing_information['exp_month'] ?? '';
                    $account_details['card_exp_year'] = $billing_information['exp_year'] ?? '';
                    $account_details['card_cvv'] = $billing_information['cvv'] ?? '';
                }
                $account_details['email_address'] = $service_address['email'] ?? '';
                $account_details['external_person_id'] = '';
                $account_details['external_account_id'] = '';
                $account_details['account_type'] = '';
                $account_details['last_four'] = $billing_information['last_four'] ?? '';
                $autopay_account = OrkestraAccount::create($account_details);
            }

            $other_account_details['ip_address'] = '';
            $other_account_details['alias'] = 'External account';
            $other_account_details['name'] = ($service_address['first_name'] ?? '') . ' ' . ($service_address['last_name'] ?? '');
            $other_account_details['address'] = $billing_information['street'] ?? '' . ', ' . $billing_information['suite'] ?? '' . ', ' . $billing_information['city'] ?? '' . ', ' . $billing_information['postal'] ?? '';
            $other_account_details['city'] = $service_address['city'] ?? '';
            $other_account_details['region'] = $billing_information['region'] ?? '';
            $other_account_details['country'] = $billing_information['country'] ?? '';
            $other_account_details['postal_code'] = $billing_information['postal'] ?? '';
            $other_account_details['phoneNumber'] = $service_address['phone'] ?? '';
            $other_account_details['active'] = true;
            $other_account_details['account_number'] = $billing_information['account_number'] ?? '';
            $other_account_details['type'] = 'SimpleAccount';
            $other_account_details['ach_routing_number'] = $billing_information['ach_routing_number'] ?? '';
            $other_account_details['email_address'] = $service_address['email'] ?? '';
            $other_account_details['external_person_id'] = '';
            $other_account_details['external_account_id'] = '';
            $other_account_details['account_type'] = '';
            $external_account = OrkestraAccount::create($other_account_details);

            $other_account_details['account_number'] = '';
            $other_account_details['ach_routing_number'] = '';
            $other_account_details['alias'] = 'Cash or check';
            $other_account = OrkestraAccount::create($other_account_details);

            $other_account_details['alias'] = 'Account credit';
            $other_account_details['type'] = 'PointsAccount';
            $point_account = OrkestraAccount::create($other_account_details);

            $sales_profile['autopay'] = true;
            $customer = PocomosCustomer::create($input_details);

            $sales_profile['points_account_id'] = $point_account->id ?? null;
            if ($autopay_account) {
                $sales_profile['autopay_account_id'] = $autopay_account->id;
            }
            $sales_profile['external_account_id'] = $external_account->id ?? null;
            $sales_profile['customer_id'] = $customer->id;
            $sales_profile['office_id'] = $service_information['office_id'];
            $sales_profile['salesperson_id'] = $billing_information['sales_person_id'] ?? null;
            $sales_profile['active'] = true;
            $sales_profile['office_user_id'] = null;
            $sales_profile['date_signed_up'] = date('Y-m-d H:i:s');
            $sales_profile['imported'] = false;
            $sales_profile['balance'] = 0.00;

            $sales_profile_data = PocomosCustomerSalesProfile::create($sales_profile);

            if ($phone) {
                $phone_num_input[] = [
                    'profile_id' => $sales_profile_data->id,
                    'phone_id' => $phone->id
                ];
            }

            if ($alt_phone) {
                $phone_num_input[] = [
                    'profile_id' => $sales_profile_data->id,
                    'phone_id' => $alt_phone->id
                ];
            }

            if ($phone_num_input) {
                PocomosCustomersPhone::insert($phone_num_input);
            }

            $insert_data[0]['profile_id'] = $sales_profile_data->id;
            $insert_data[0]['account_id'] = $external_account->id;
            $insert_data[1]['profile_id'] = $sales_profile_data->id;
            $insert_data[1]['account_id'] = $point_account->id;
            $insert_data[2]['profile_id'] = $sales_profile_data->id;
            $insert_data[2]['account_id'] = $other_account->id;

            if ($autopay_account) {
                $insert_data[3]['profile_id'] = $sales_profile_data->id;
                $insert_data[3]['account_id'] = $autopay_account->id;
            }

            // [
            //     'profile_id' => $sales_profile_data->id, 'account_id' => $autopay_account->id,
            //     'profile_id' => $sales_profile_data->id, 'account_id' => $external_account->id,
            //     'profile_id' => $sales_profile_data->id, 'account_id' => $point_account->id,
            //     'profile_id' => $sales_profile_data->id, 'account_id' => $other_account->id
            // ]

            PocomosCustomersAccount::insert($insert_data);

            $pest_agr =  PocomosPestAgreement::whereId($service_information['contract_type_id'])->first();

            if ($pest_agr) {
                $pest_agreement_id = $pest_agr->id ?? null;
            } else {
                $pest_agreement['agreement_id'] = $pest_agr->agreement_id ?? null;
                $pest_agreement['service_frequencies'] = serialize($service_information['service_frequency']) ?? null;

                if (isset($service_information['exceptions'])) {
                    $pest_agreement['exceptions'] = serialize($service_information['exceptions']) ?? null;
                }

                $pest_agreement['active'] = true;
                $pest_agreement['initial_duration'] = $scheduling_information['initial_job_duration'];
                $pest_agreement['regular_duration'] = $scheduling_information['job_duration'];
                $pest_agreement['one_month_followup'] = false;
                $pest_agreement['max_jobs'] = $service_information['num_of_jobs'];
                $pest_agreement['default_agreement'] = false;
                $agreement = PocomosPestAgreement::create($pest_agreement);
                $pest_agreement_id = $agreement->id ?? null;
            }

            $agreement_signature = $agreement_input['signature'] ?? null;
            $agreement_sign = null;
            $signed = false;
            if ($agreement_signature) {
                $signature_id = $this->uploadFileOnS3('Customer', $agreement_signature);
                $agreement_sign = OrkestraFile::findOrFail($signature_id);
                //store file into document folder
                // $agreement_sign_detail['path'] = $agreement_signature->store('public/files');
                // $agreement_sign_detail['user_id'] = $or_user->id ?? null;
                //store your file into database
                // $agreement_sign_detail['filename'] = $agreement_signature->getClientOriginalName();
                // $agreement_sign_detail['mime_type'] = $agreement_signature->getMimeType();
                // $agreement_sign_detail['file_size'] = $agreement_signature->getSize();
                // $agreement_sign_detail['active'] = 1;
                // $agreement_sign_detail['md5_hash'] =  md5_file($agreement_signature->getRealPath());
                // $agreement_sign =  OrkestraFile::create($agreement_sign_detail);
                $signed = true;
            }

            $autopay_signature = $agreement_input['autopay_signature'] ?? null;
            $autopay_sign = null;

            if ($autopay_signature) {
                $autopay_sign_id = $this->uploadFileOnS3('Customer', $autopay_signature);
                $autopay_sign = OrkestraFile::findOrFail($autopay_sign_id);

                //store file into document folder
                // $autopay_sig_detail['path'] = $autopay_signature->store('public/files');
                // $autopay_sig_detail['user_id'] = $or_user->id ?? null;
                //store your file into database
                // $autopay_sig_detail['filename'] = $autopay_signature->getClientOriginalName();
                // $autopay_sig_detail['mime_type'] = $autopay_signature->getMimeType();
                // $autopay_sig_detail['file_size'] = $autopay_signature->getSize();
                // $autopay_sig_detail['active'] = 1;
                // $autopay_sig_detail['md5_hash'] =  md5_file($autopay_signature->getRealPath());
                // $autopay_sign =  OrkestraFile::create($autopay_sig_detail);
            }

            $pocomos_contract['profile_id'] = $sales_profile_data->id;
            $pocomos_contract['agreement_id'] = $pest_agr->agreement_id ?? '';
            $pocomos_contract['signature_id'] = $agreement_sign ? $agreement_sign->id : null;
            $pocomos_contract['billing_frequency'] = $service_information['billing_frequency'] ?? '';
            $pocomos_contract['status'] = 'Active';
            $pocomos_contract['date_start'] = $service_information['contract_start_date'] ?? '';
            if (isset($service_information['contract_end_date'])) {
                $contract_end_date = $service_information['contract_end_date'];
            } else {
                $contract_end_date = date('Y-m-d', strtotime('+1 year'));
            }
            $pocomos_contract['date_end'] = $contract_end_date;
            $pocomos_contract['active'] = true;
            $pocomos_contract['salesperson_id'] = $billing_information['sales_person_id'] ?? null;
            $pocomos_contract['auto_renew'] = $options['auto_renew'] ?? false;
            $pocomos_contract['tax_code_id'] = $additional_information['tax_code_id'] ?? null;
            $pocomos_contract['signed'] = $signed;
            $pocomos_contract['autopay_signature_id'] = $autopay_sign ? $autopay_sign->id : null;
            $pocomos_contract['sales_tax'] = $additional_information['sales_tax'] ?? 0.0;
            $pocomos_contract['sales_status_id'] = $billing_information['sales_status_id'] ?? null;
            $pocomos_contract['found_by_type_id'] = $billing_information['marketing_type_id'] ?? null;

            if (isset($service_information['billing_frequency']) &&  in_array($service_information['billing_frequency'], ['Installments'])) {
                $pocomos_contract['number_of_payments'] = $pricing_information['number_of_payments'] ?? 0;
                $pocomos_contract['renew_installment_initial_price'] = $pricing_information['renew_installment_initial_price'] ?? 0;
                $pocomos_contract['renew_installment_start_date'] = $pricing_information['renew_installment_start_date'] ?? 0;
                $pocomos_contract['renew_number_of_payment'] = $pricing_information['renew_number_of_payments'] ?? 0;
                $pocomos_contract['renew_installment_frequency'] = $pricing_information['renew_installment_frequency'] ?? 0;
                $pocomos_contract['renew_installment_price'] = $pricing_information['renew_installment_price'] ?? 0;
            }

            $cus_contract = PocomosContract::create($pocomos_contract);

            foreach ($pest_discount_types as $discountVal) {
                PocomosPestDiscountTypeItem::create([
                    'discount_id' => $discountVal['discount_type_id'],
                    'rate'        => 0.00,
                    'amount' => $discountVal['amount'],
                    'type' => 'static',
                    'description' => $discountVal['description'],
                    'contract_id' => $cus_contract->id,
                ]);
            }

            if ($agreement_sign) {
                $files_input = [
                    [
                        'customer_id' => $customer->id,
                        'file_id' => $agreement_sign->id
                    ]
                ];
                PocomosCustomersFile::insert($files_input);
            }

            $pest_contract['contract_id'] = $cus_contract->id;
            $pest_contract['agreement_id'] = $pest_agreement_id;
            $pest_contract['service_frequency'] = $service_information['service_frequency'] ?? '';
            if (isset($service_information['exceptions'])) {
                $pest_contract['exceptions'] = serialize($service_information['exceptions']) ?? null;
            } else {
                $pest_contract['exceptions'] = null;
            }
            $pest_contract['initial_price'] = $pricing_information['initial_price'] ?? 0;
            $pest_contract['recurring_price'] = $pricing_information['recurring_price'] ?? 0;
            $pest_contract['initial_discount'] = $pricing_information['initial_discount'] ?? 0;
            $pest_contract['regular_initial_price'] = $pricing_information['normal_initial'] ?? 0;

            $amount = 0;
            $original_value = 0;
            $modifiable_original_value = 0;
            $first_year_contract_value = 0;

            if (isset($service_information['billing_frequency']) && in_array($service_information['billing_frequency'], ['Monthly'])) {
                $original_value = $pricing_information['recurring_price'] ?? 0;
                $modifiable_original_value = $pricing_information['recurring_price'] ?? 0;
                $first_year_contract_value = $pricing_information['recurring_price'] ?? 0;
                $amount = $pricing_information['recurring_price'] ?? 0;
            } elseif (isset($service_information['billing_frequency']) &&  in_array($service_information['billing_frequency'], ['Initial monthly', 'Due at signup'])) {
                $original_value = $pricing_information['initial_price'] ?? 0;
                $modifiable_original_value = $pricing_information['initial_price'] ?? 0;
                $first_year_contract_value = $pricing_information['initial_price'] ?? 0;
                $amount = $pricing_information['initial_price'] ?? 0;
            } elseif (isset($service_information['billing_frequency']) &&  in_array($service_information['billing_frequency'], ['Two payments'])) {
                $original_value = $pricing_information['initial_price'] + $pricing_information['recurring_price'];
                $modifiable_original_value = $pricing_information['initial_price'] + $pricing_information['recurring_price'];
                $first_year_contract_value = $pricing_information['initial_price'] + $pricing_information['recurring_price'];
                $amount = $original_value;
            } elseif (isset($service_information['billing_frequency']) &&  in_array($service_information['billing_frequency'], ['Installments'])) {
                $original_value = $pricing_information['initial_price'];
                $modifiable_original_value = $pricing_information['initial_price'];
                $first_year_contract_value = $pricing_information['initial_price'];
                $pest_contract['installment_frequency'] = $pricing_information['installment_frequency'];
                $pest_contract['installment_start_date'] = $pricing_information['installment_start_date'];
                $pest_contract['installment_end_date'] = $pricing_information['installment_end_date'];
                $amount = $pricing_information['initial_price'] ?? 0;
            }

            $pest_contract['original_value'] = $original_value;
            $pest_contract['auto_renew_installments'] = $service_information['auto_renew_installments'] ?? false;
            $pest_contract['modifiable_original_value'] = $modifiable_original_value;
            $pest_contract['first_year_contract_value'] = $first_year_contract_value;

            $pest_contract['active'] = true;
            $pest_contract['service_type_id'] = $service_information['service_type_id'];
            $pest_contract['service_schedule'] = '';
            $pest_contract['week_of_the_month'] = $options['week'] ?? '';
            $pest_contract['day_of_the_week'] = $options['day'] ?? '';
            $pest_contract['date_renewal_end'] = date('Y-m-d', strtotime('+2 year'));
            $pest_contract['preferred_time'] = $options['preferred_time'] ?? '';
            $pest_contract['county_id'] = $service_address['county_id'] ?? null;

            $preferred_technician_id = null;
            if($scheduling_information['is_preferred_technician']){
                $preferred_technician_id = $scheduling_information['technician_id'];
            }
            
            $pest_contract['technician_id'] = $preferred_technician_id;
            // $pest_contract['renew_initial_job'] = '';
            $pest_contract['number_of_jobs'] = $service_information['num_of_jobs'];
            $pest_contract['map_code'] = $service_information['map_code'] ?? '';
            $pest_contract['addendum'] = $agreement_input['addendum'] ?? null;
            $pest_contract_res = PocomosPestContract::create($pest_contract);

            foreach ($custom_fields as $key => $value) {
                PocomosCustomField::create(['pest_control_contract_id' => $pest_contract_res->id, 'custom_field_configuration_id' => $key, 'value' => $value, 'active' => true]);
            }

            foreach ($tags as $value) {
                PocomosPestContractsTag::create(['contract_id' => $pest_contract_res->id, 'tag_id' => $value]);
            }

            // $note_detail['user_id'] = $or_user->id ?? null;
            $initial_job_note_data['summary'] = $service_information['initial_job_notes'] ?? '';
            $initial_job_note_data['interaction_type'] = 'Other';
            $initial_job_note_data['active'] = true;
            $initial_job_note_data['body'] = '';
            $initial_job_note = PocomosNote::create($initial_job_note_data);

            $permanent_job_note_data['summary'] = $service_information['permanent_notes'] ?? '';
            $permanent_job_note_data['interaction_type'] = 'Other';
            $permanent_job_note_data['active'] = true;
            $permanent_job_note_data['body'] = '';
            $permanent_job_note = PocomosNote::create($permanent_job_note_data);

            PocomosCustomersNote::create(['customer_id' => $customer->id, 'note_id' => $initial_job_note->id]);
            PocomosCustomersNote::create(['customer_id' => $customer->id, 'note_id' => $permanent_job_note->id]);

            foreach ($targeted_pests as $value) {
                PocomosPestContractsPest::create(['contract_id' => $pest_contract_res->id, 'pest_id' => $value]);
            }

            foreach ($specialty_pests as $value) {
                PocomosPestContractsPest::create(['contract_id' => $pest_contract_res->id, 'pest_id' => $value]);
            }

            $customer_state['customer_id'] = $customer->id;
            $customer_state['next_service_date'] = date('Y-m-d H:i:s');
            $customer_state['active'] = true;
            $customer_state['balance_overall'] = 0.0;
            $customer_state['balance_outstanding'] = 0.0;
            $customer_state['balance_credit'] = 0.0;
            $customer_state['days_past_due'] = 0;
            $customer_state['card_on_file'] = 0;
            PocomosCustomerState::create($customer_state);

            if (isset($service_address['lead_id'])) {
                $lead = PocomosLead::findOrFail($request->lead_id);
                $lead->customer_id = $customer->id;
                $lead->first_name = $service_address['first_name'] ?? null;
                $lead->last_name = $service_address['last_name'] ?? null;
                $lead->status = 'Customer';
                $lead->save();
            }

            $service_schedule = $service_information['service_schedule'] ?? array();

            if (!count($service_schedule)) {
                $contract_start_date =  $service_information['contract_start_date'] ?? date('Y-m-d');
                $contract_end_date   =  $service_information['contract_end_date'] ?? date('Y-m-d', strtotime('+1 year'));

                $service_schedule = $this->getDatesBaseServiceSchedules($contract_start_date, $contract_end_date, $service_information['service_frequency'], $service_information['exceptions'] ?? array(), $scheduling_information['specific_day_and_week'], $options);
            }

            $i = 0;
            foreach ($service_schedule as $schedule) {
                // create invoice
                $invoice_input['contract_id'] = $cus_contract->id;
                $invoice_input['date_due'] = date('Y-m-d', strtotime($schedule));
                $invoice_input['amount_due'] = $amount;
                $invoice_input['status'] = 'Paid';
                $invoice_input['balance'] = 0.00;
                $invoice_input['active'] = true;
                $invoice_input['sales_tax'] = $additional_information['sales_tax'] ?? 0.0;
                $invoice_input['tax_code_id'] = $additional_information['tax_code_id'] ?? null;
                $invoice_input['closed'] = false;
                $pocomos_invoice = PocomosInvoice::create($invoice_input);

                $invoice_items['description'] = 'Description';
                $invoice_items['price'] = $amount;
                $invoice_items['invoice_id'] = $pocomos_invoice->id;
                $invoice_items['active'] = true;
                $invoice_items['sales_tax'] = $additional_information['sales_tax'] ?? 0.0;
                $invoice_items['tax_code_id'] = $additional_information['tax_code_id'] ?? null;
                $invoice_items['type'] = '';
                PocomosInvoiceItems::create($invoice_items);

                PocomosPestContractsInvoice::create(['pest_contract_id' => $pest_contract_res->id, 'invoice_id' => $pocomos_invoice->id]);

                // if($i == 0){                    
                    $route_input['name'] = 'Route';
                    $route_input['office_id'] = $service_information['office_id'];
                    $route_input['created_by'] = 'CreateCustomer';
                    $route_input['date_scheduled'] = date('Y-m-d', strtotime($schedule));
                    $route_input['active'] = 1;
                    $route_input['locked'] = 0;

                    if (isset($scheduling_information['technician_id'])) {
                        $route_input['technician_id'] = $scheduling_information['technician_id'] ?? '';
                        
                        $route = PocomosRoute::where('date_scheduled', date('Y-m-d', strtotime($schedule)))->where('technician_id', $scheduling_information['technician_id'])->first();
                    }

                    if(!$route){
                        $route = PocomosRoute::create($route_input);
                    }

                    $job_duration = explode(' ', $scheduling_information['job_duration']);
                    $job_duration = $job_duration[0] ?? '';
                    
                    $route_slot['route_id'] = $route->id;
                    $route_slot['time_begin'] = $options['preferred_time'] ?? '';
                    $route_slot['duration'] = $job_duration;
                    $route_slot['type'] = 'Regular';
                    $route_slot['type_reason'] = '';
                    if ($i == 0) {
                        $route_slot['schedule_type'] = 'Hard';
                    } else {
                        $route_slot['schedule_type'] = 'Regular';
                    }
                    $route_slot['anytime'] = false;
                    $route_slot['active'] = 1;
                    $slot = PocomosRouteSlots::create($route_slot);

                    $scheduleData = PocomosSchedule::whereOfficeId($service_information['office_id'])->first();
                    $scheduleData = $scheduleData ?? array();

                    $lunch_slot['route_id'] = $route->id;
                    $lunch_slot['time_begin'] = $scheduleData['time_lunch_start'] ?? '';
                    $lunch_slot['duration'] = $scheduleData['lunch_duration'] ?? '';
                    $lunch_slot['type'] = 'Lunch';
                    $lunch_slot['type_reason'] = '';
                    $lunch_slot['schedule_type'] = 'Dynamic';
                    $lunch_slot['anytime'] = false;
                    $lunch_slot['active'] = 1;
                    PocomosRouteSlots::create($lunch_slot);
                // }
                $input = [];
                $input['contract_id'] = $pest_contract_res->id;
                $input['invoice_id'] = $pocomos_invoice->id;
                $input['date_scheduled'] = date('Y-m-d', strtotime($schedule));
                $input['type'] = $request->type;
                $input['status'] = 'Pending';
                $input['active'] = true;
                $input['slot_id'] = $slot->id;
                if ($i == 0) {
                    $input['type'] = 'Initial';
                } else {
                    $input['type'] = 'Regular';
                }
                $input['original_date_scheduled'] = date('Y-m-d', strtotime($schedule));
                $input['note'] = $request->permanent_notes ?? '';
                $input['color'] = '';
                $input['commission_type'] = 'None';
                $input['commission_value'] = 0;
                $input['commission_edited'] = 0;
                $input['technician_note'] = '';
                $input['weather'] = '';
                $input['treatmentNote'] = '';
                $input['technician_id'] = $scheduling_information['technician_id'] ?? '';
                PocomosJob::create($input);

                $i = $i + 1;
            }

            /**Send welcome email to customer */
            // $this->generateWelcomeEmail($pest_contract_res);

            DB::commit();

            $status = true;
            $message = __('strings.create', ['name' => 'Customer']);
            $res['customer_id'] = $customer->id;
            $res['contract_id'] = $cus_contract->id;
        } catch (\Exception $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }
        return $this->sendResponse($status, $message, $res);
    }

    /**
     * API for list of customer
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


    public function getSlotsAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'date' => 'required',
            'technician' => 'required|exists:pocomos_technicians,id',
            'duration' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $route = PocomosRoute::has('technician_detail')
            ->with(['technician_detail.user_detail.user_details', 'office_detail'])
            ->whereTechnicianId($request->technician)
            ->whereOfficeId($request->office_id)
            ->whereDateScheduled($request->date)
            ->first();

        if ($route) {
            $timeSlots = $this->getAvailableTimeSlots($route, $request->duration);
        } else {
            $technician = PocomosTechnician::select('*', 'pocomos_technicians.id')
                ->join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
                ->where('pocomos_technicians.id', $request->technician)
                ->where('pcou.office_id', $request->office_id)
                ->first();

            $techId = $technician->id ?? null;

            $timeSlots = $this->getStandardTimeSlots($request->office_id, $request->date, $techId);
        }

        return $this->sendResponse(true, 'Time Slots', $timeSlots ?? []);
    }


    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $childCustomers = PocomosSubCustomer::pluck('child_id')->toArray();
        $childCustomers = $this->convertArrayInStrings($childCustomers);

        $sql = "SELECT pc.id, pc.account_type , pc.first_name, pc.last_name, ppn.number as 'phone_number', pc.email,
             cca.postal_code as 'current_postal', pss.name as 'status', pc.date_created as 'date_signed_up',
              pcs.last_service_date, pcs.next_service_date, pc.status, pc.company_name
            FROM pocomos_customers AS pc
            JOIN pocomos_customer_sales_profiles AS pcsp ON pc.id = pcsp.customer_id
            LEFT JOIN pocomos_addresses AS cca ON pc.contact_address_id = cca.id
            LEFT JOIN pocomos_sales_status AS pss ON pc.status = pss.id
            LEFT JOIN pocomos_customer_state AS pcs ON pc.id = pcs.customer_id
            LEFT JOIN pocomos_phone_numbers AS ppn ON cca.phone_id = ppn.id
            WHERE
            -- pc.id NOT IN ($childCustomers) and
            pcsp.office_id =  $request->office_id
            and pc.active <> 'Inactive'
        ";

        if ($request->search) {
            $search = $request->search;
            $sql .= " AND (CONCAT(pc.first_name, \" \", pc.last_name) LIKE '%$search%' OR ppn.number LIKE '%$search%'
                    OR pc.email LIKE '%$search%' OR cca.postal_code LIKE '%$search%'
                    -- OR pss.name LIKE '%$search%'
                    -- OR pc.date_created LIKE '%$search%' OR pcs.last_service_date LIKE '%$search%'
                    -- OR pcs.next_service_date LIKE '%$search%'
                    OR pc.status LIKE '%$search%'
                    ) ";
        }

        $sql .= " ORDER BY pc.first_name ASC";

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));
        /**If result data are from DB::row query then `true` else `false` normal laravel get listing */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";
        $data = DB::select(DB::raw("$sql"));
        /**End */

        foreach ($data as $value) {
            $value->is_parent = $this->is_cutomer_parent($value->id);
            $value->is_child = $this->is_cutomer_child($value->id);
            $value->multiple_contracts = $this->is_cutomer_multiple_contracts($value->id);
            $value->commercial_account = $value->account_type == config('constants.COMMERCIAL') ? true : false;
        }
        // $data = $this->removeChildCustomers($data);
        $data = [
            'customers' => $data,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Customers']), $data);
    }

    public function getcreatedBy(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $users = OrkestraUser::select('*', 'orkestra_users.id')
            ->join('pocomos_company_office_users as pcou', 'orkestra_users.id', 'pcou.user_id')
            ->join('pocomos_technicians as ou', 'pcou.id', 'ou.user_id')
            ->where('pcou.office_id', $officeId)
            ->where('pcou.active', 1)
            ->where('ou.active', 1)
            ->get();

        return $this->sendResponse(true, 'users ', [
            'users'   => $users,
        ]);
    }

    /**
     * API for account note add
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addAccountNote(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'interaction_type' => 'required',
            'summary' => 'required',
            'specify_user' => 'required|boolean', //true or false
            'assigned_by' => 'nullable|exists:orkestra_users,id',
            'notify_user' => 'required|boolean',
            'assigned_to' => 'nullable|exists:orkestra_users,id',
            'display_on_work_order' => 'nullable|boolean',
            'display_on_unpaid_jobs' => 'nullable|boolean',
            'favorite' => 'nullable|boolean',
            'display_on_load' => 'nullable|boolean',
            'display_on_route_map' => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // find customer
        $find_customer = PocomosCustomer::where('id', $request->customer_id)->first();
        if (!$find_customer) {
            return $this->sendResponse(false, 'Customer not found.');
        }

        $input = [];
        $input['user_id'] = auth()->user()->id;
        $input['summary'] = $request->summary;
        $input['body'] = '';
        $input['active'] = 1;
        $input['favorite'] = $request->favorite ?? 0;
        $input['interaction_type'] = $request->interaction_type;
        $input['display_on_load'] = $request->display_on_load ?? 0;

        if (($request->specify_user == 1) && isset($request->assigned_by)) {
            $input['user_id'] = $request->assigned_by;
        }

        $addNote = PocomosNote::create($input);

        // map this note with customer
        $customer_note = [];
        $customer_note['customer_id'] = $request->customer_id;
        $customer_note['note_id'] = $addNote->id;
        $map_customer = PocomosCustomersNote::create($customer_note);

        if (($request->notify_user == 1) && isset($request->assigned_to)) {
            $user = OrkestraUser::where('id', $request->assigned_to)->first();
            $sender = OrkestraUser::where('id', auth()->user()->id)->first();
            if (($request->specify_user == 1) && isset($request->assigned_by)) {
                $sender = OrkestraUser::where('id', $request->assigned_by)->first();
            }
            $user->notify(new TaskAddNotification($request, $user, $sender));

            $data = $this->createToDo($request, 'Alert', 'Alert');
        }

        // If display_on_work_order this true
        if ($request->display_on_work_order == 1) {
            $customer_note = [];
            $customer_note['customer_id'] = $request->customer_id;
            $customer_note['note_id'] = $addNote->id;
            $add_work_order = PocomosCustomersWorkorderNote::create($customer_note);
        }

        // If display_on_unpaid_jobs this true
        if ($request->display_on_unpaid_jobs == 1) {
            $find_customer->unpaid_note = $addNote->id;
        }

        // If display_on_route_map this true
        if ($request->display_on_route_map == 1) {
            $find_customer->route_map_note = $addNote->id;
        }

        $find_customer->save();

        return $this->sendResponse(true, 'Account Note Created Successfully!', $addNote);
    }

    /**
     * API for account note Listing
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function listAccountNote(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        // find customer
        $find_customer = PocomosCustomer::where('id', $request->customer_id)->first();
        if (!$find_customer) {
            return $this->sendResponse(false, 'Customer not found.');
        }

        // Find note based on customer id
        $customer_notes = PocomosCustomersNote::join('pocomos_notes as pn', 'pocomos_customers_notes.note_id', 'pn.id')
            ->where('customer_id', $request->customer_id)
            ->with('note_detail', 'note_detail.user_details');

        if ($request->search) {
            $search = $request->search;
            $customer_notes->where(function ($query) use ($search) {
                $query->where('summary', 'like', '%' . $search . '%')
                    ->orWhere('interaction_type', 'like', '%' . $search . '%')
                    ->orWhere('date_created', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $customer_notes->count();
        $customer_notes->skip($perPage * ($page - 1))->take($perPage);

        $customer_notes = $customer_notes->orderBy('pn.date_created', 'desc')->get();

        $customer_notes->map(function ($customer_note) use ($find_customer) {
            $customer_id = $customer_note->note_id;
            if ($find_customer->route_map_note == $customer_id) {
                $customer_note['display_on_route_map'] = true;
            } else {
                $customer_note['display_on_route_map'] = false;
            }
            if ($find_customer->unpaid_note == $customer_id) {
                $customer_note['display_on_unpaid_jobs'] = true;
            } else {
                $customer_note['display_on_unpaid_jobs'] = false;
            }
            $work_order = PocomosCustomersWorkorderNote::where('customer_id', $find_customer->id)->where('note_id', $customer_note->id)->first();
            if ($work_order) {
                $customer_note['display_on_work_order'] = true;
            } else {
                $customer_note['display_on_work_order'] = false;
            }
        });

        return $this->sendResponse(true, 'List', [
            'customer_notes' => $customer_notes,
            'count' => $count,
        ]);
    }

    /**
     * API for account note edit
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function editAccountNote(Request $request)
    {
        $v = validator($request->all(), [
            'note_id' => 'required|exists:pocomos_notes,id',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'interaction_type' => 'required',
            'summary' => 'required',
            'specify_user' => 'required|boolean', //true or false
            'assigned_by' => 'nullable|exists:orkestra_users,id',
            'notify_user' => 'required|boolean',
            'assigned_to' => 'nullable|exists:orkestra_users,id',
            'display_on_work_order' => 'nullable|boolean',
            'display_on_unpaid_jobs' => 'nullable|boolean',
            'favorite' => 'nullable|boolean',
            'display_on_load' => 'nullable|boolean',
            'display_on_route_map' => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // find customer
        $PocomosNote = PocomosNote::where('id', $request->note_id)->first();
        if (!$PocomosNote) {
            return $this->sendResponse(false, 'Note not found.');
        }

        // find customer
        $find_customer = PocomosCustomer::where('id', $request->customer_id)->first();
        if (!$find_customer) {
            return $this->sendResponse(false, 'Customer not found.');
        }

        $input_details = $request->only('summary', 'favorite', 'interaction_type', 'display_on_load');

        if (($request->specify_user == 1) && isset($request->assigned_by)) {
            $input_details['user_id'] = $request->assigned_by;
        }

        $PocomosNote->update($input_details);

        if (($request->notify_user == 1) && isset($request->assigned_to)) {
            $user = OrkestraUser::where('id', $request->assigned_to)->first();
            $sender = OrkestraUser::where('id', auth()->user()->id)->first();
            if (($request->specify_user == 1) && isset($request->assigned_by)) {
                $sender = OrkestraUser::where('id', $request->assigned_by)->first();
            }
            $user->notify(new TaskAddNotification($request, $user, $sender));

            $data = $this->createToDo($request, 'Alert', 'Alert');
        }

        // If display_on_work_order this true
        if ($request->display_on_work_order == 1) {
            $PocomosLeadQuoteTag = PocomosCustomersWorkorderNote::where('customer_id', $find_customer->id)->where('note_id', $request->note_id)->delete();

            $customer_note = [];
            $customer_note['customer_id'] = $request->customer_id;
            $customer_note['note_id'] = $request->note_id;
            $add_work_order = PocomosCustomersWorkorderNote::create($customer_note);
        } elseif ($request->display_on_work_order != 1) {
            $PocomosLeadQuoteTag = PocomosCustomersWorkorderNote::where('customer_id', $find_customer->id)->where('note_id', $request->note_id)->delete();
        }

        // If display_on_unpaid_jobs this true
        if ($request->display_on_unpaid_jobs == 1) {
            $find_customer->unpaid_note = $request->note_id;
        } elseif (($request->display_on_unpaid_jobs != 1) && ($find_customer->unpaid_note == $request->note_id)) {
            $find_customer->unpaid_note = null;
        }

        // If display_on_route_map this true
        if ($request->display_on_route_map == 1) {
            $find_customer->route_map_note = $request->note_id;
        } elseif (($request->display_on_route_map != 1) && ($find_customer->route_map_note == $request->note_id)) {
            $find_customer->route_map_note = null;
        }

        $find_customer->save();

        return $this->sendResponse(true, 'Account Note edited Successfully!', $PocomosNote);
    }

    /**
     * API for account note delete
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteAccountNote(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'note_id' => 'required|exists:pocomos_notes,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // find note first
        $customer_notes = PocomosNote::where('id', $request->note_id)->first();
        if (!$customer_notes) {
            return $this->sendResponse(false, 'Note not found.');
        }

        $find_customer_id = PocomosCustomersNote::where('note_id', $request->note_id)->where('customer_id', $request->customer_id)->delete();

        $find_old_work_order = PocomosCustomersWorkorderNote::where('customer_id', $request->customer_id)->where('note_id', $request->note_id)->delete();

        $find_customer = PocomosCustomer::where('id', $request->customer_id)->first();
        if (!$find_customer) {
            return $this->sendResponse(false, 'Customer not found.');
        }

        if ($find_customer->route_map_note == $request->note_id) {
            $find_customer['route_map_note'] = null;
        }
        if ($find_customer->unpaid_note == $request->note_id) {
            $find_customer['unpaid_note'] = null;
        }

        $find_customer->save();

        $delete_note = $customer_notes->delete();

        return $this->sendResponse(true, 'Note deleted successfully.', $delete_note);
    }

    /**
     * API for technician note list
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function techNoteList(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // find customer from id
        $find_customer = PocomosCustomer::where('id', $request->customer_id)->first();

        if (!$find_customer) {
            return $this->sendResponse(false, 'Customer not found.');
        }

        $data = [];

        // find sales perofile id from customer
        $find_sales_profile_id = PocomosCustomerSalesProfile::where('customer_id', $find_customer->id)->first();
        if ($find_sales_profile_id) {
            // find contracts from profile id
            $find_contracts = PocomosContract::where('profile_id', $find_sales_profile_id->id);

            if ($request->search) {
                $search = $request->search;
                // $find_contracts->where(function ($query) use ($search) {
                //     $query->where('date_scheduled', 'like', '%' . $search . '%')
                //         ->orWhere('type', 'like', '%' . $search . '%')
                //         ->orWhere('status', 'like', '%' . $search . '%');
                // });
            }

            /**For pagination */
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $find_contracts->skip($perPage * ($page - 1))->take($perPage);

            $find_contracts = $find_contracts->get();


            foreach ($find_contracts as $contract_data) {
                $find_pest_contract = PocomosPestContract::where('contract_id', $contract_data->id)->first();
                if ($find_pest_contract) {
                    $find_tech_note = PocomosJob::with('technician.user_detail.user_details')
                        ->where('contract_id', $find_pest_contract->id)->first();

                    if ($find_tech_note) {
                        array_push($data, $find_tech_note);
                    }
                }
            }

            $count =  count($data);
        }

        return $this->sendResponse(true, 'List', [
            'cust_tech_notes' => $data,
            'count' => $count,
        ]);
    }

    public function techNotes(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = $this->findOneByIdAndOffice_customerRepo($custId, $request->office_id);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find Customer.']));
        }

        $notes = $this->findOrderedByCustomer($custId, /* order */ 'DESC');

        $contracts = $this->findAllByCustomerPCCRepo($custId);

        $jobs = array();
        if ($contracts) {
            foreach ($contracts as $contract) {
                $result = $this->findAllServicesForContract($contract->id)->toArray();
                if ($result) {
                    $jobs = array_merge($jobs, $result);
                }
            }
        }

        // return array(
        //     'entity' => $customer,
        //     'notes' => $notes,
        //     'tech_notes' => $jobs,
        // );

        return $this->sendResponse(true, 'List', [
            'entity' => $customer,
            'notes' => $notes,
            'tech_notes' => $jobs,

            // 'cust_tech_notes' => $data,
            // 'count' => $count,
        ]);
    }


    /**
     * API for profile details of customer
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function profile($id)
    {
        // Session::flush('current_contract_context');
        // // dd(Session::get('current_contract_context'));
        // $data[config('constants.ACTIVE_CONTEXT_KEY')]["customer"] = '704624';
        // $data[config('constants.ACTIVE_CONTEXT_KEY')]["contract"] = '708591';

        // Session::put($data);
        // Session::save();
        // return (Session::all());

        $res = PocomosCustomer::with([
            'contact_address.primaryPhone', 'contact_address.altPhone', 'billing_address.primaryPhone', 'parent.parent_detail', 'child.child_detail',
            'billing_address.altPhone', 'contact_address.region', 'billing_address.region', 'sales_profile.points_account',
            'sales_profile.autopay_account', 'sales_profile.external_account',
            'sales_profile.sales_people.office_user_details.user_details',
            'sales_profile.sales_people.office_user_details.profile_details',
            'sales_profile.sales_people.office_user_details.company_details',
            'sales_profile.contract_details.agreement_details', 'sales_profile.contract_details.tax_details',
            'sales_profile.contract_details.pest_contract_details',
            'sales_profile.contract_details.pest_contract_details.pest_agreement_details',
            'sales_profile.contract_details.pest_contract_details.service_type_details',
            'sales_profile.contract_details.pest_contract_details.contract_tags',
            'sales_profile.contract_details.pest_contract_details.contract_tags.tag_details',
            'sales_profile.contract_details.pest_contract_details.pest_pac_export_detail',
            'sales_profile.contract_details.pest_contract_details.mission_export_detail',
            'sales_profile.contract_details.marketing_type', 'sales_profile.contract_details.sales_status',
            'notes_details.note', 'sales_profile.contract_details.pest_contract_details.all_pests',
            'state_details', 'sales_profile.contract_details.pest_contract_details.custom_fields.custom_field',
            'sales_profile.contract_details.state_report', 'sales_profile.contract_details.search_report_state',
            'sales_profile.contract_details.pest_contract_details.county',
            'sales_profile.contract_details.pest_contract_details.technician_details.user_detail.user_details_name',
            'sales_profile.phone_numbers.phone',
            'sales_profile.contract_details.discount_types',
            'sales_profile.contract_details.salespeople.office_user_details.user_details_name',
            'sales_profile.contract_details.discount_types.discount',
            'sales_profile.account_details',
            'sales_profile.contract_details.pest_contract_details.jobs_details' => function ($q) {
                $q->where('status', config('constants.COMPLETE'));
                $q->orderBy('date_completed', 'DESC');
                $q->orderBy('id', 'DESC');
            }, 'sales_profile.contract_details.pest_contract_details.jobs_details.route_detail'
        ])->findOrFail($id);

        $c = 0;
        $i = 0;
        foreach ($res->sales_profile->contract_details as $val) {
            $session = Session::get(config('constants.ACTIVE_CONTEXT_KEY'));
            $session = (array)$session;
            // $session = Session::all();
            // dd('dd1',$session);
            $is_default_selected = false;
            if (isset($session['contract']) && $session['contract'] == $val->id) {
                $is_default_selected = true;
            }

            if (!$is_default_selected) {
                $c = $c + 1;
            }
            if (count($res->sales_profile->contract_details) == $c) {
                $res->sales_profile->contract_details[$i]['is_default_selected'] = true;
            } else {
                $res->sales_profile->contract_details[$i]['is_default_selected'] = $is_default_selected;
            }

            $res->sales_profile->contract_details[$i]['last_job'] = $this->findLastServiceForContract($val->pest_contract_details->id);

            $res->sales_profile->contract_details[$i]['first_job'] = $this->getFirstJobNew($val->pest_contract_details->id);

            $first_job_warning_note = false;

            if (isset($res->sales_profile->contract_details[$i]['first_job']['slot_id'])) {
                $first_job_warning_note = true;
            }
            $res->sales_profile->contract_details[$i]['first_job_warning_note'] = $first_job_warning_note;
            
            $all_pests = $res->sales_profile->contract_details[$i]['pest_contract_details']['all_pests'];
            $target_pests = array();
            $specialty_pests = array();

            foreach($all_pests as $value){
                if($value['pest']['type'] == 'specialty'){
                    $specialty_pests[] = $value;
                }else{
                    $target_pests[] = $value;
                }
            }
            $res->sales_profile->contract_details[$i]['pest_contract_details']['specialty_pests'] = $specialty_pests;
            $res->sales_profile->contract_details[$i]['pest_contract_details']['target_pests'] = $target_pests;
            unset($res->sales_profile->contract_details[$i]['pest_contract_details']['all_pests']);

            $i = $i + 1;
        }
        $profile = PocomosCustomerSalesProfile::whereCustomerId($id)->firstOrFail();
        // Session::setId('gnCXViFN94Jb95B4YPvWMWdihKcnI9OdhBiLaiq1');
        Session::start();
        // // return Session::getId();
        // return Session::all();
        $res->is_parent = $this->is_cutomer_parent($id);
        $res->is_child = $this->is_cutomer_child($id);
        $res->multiple_contracts = $this->is_cutomer_multiple_contracts($id);
        $res->commercial_account = $res->account_type == config('constants.COMMERCIAL') ? true : false;
        $res->cardonfile = $this->getCustomerCardOnFileStatus($profile);
        $res->achonfile = $this->getCustomerAchOnFileStatus($profile);
        $res->autopaytype = $this->getCustomerAutopayAccStatus($profile);

        $res->sessions = Session::all();

        return $this->sendResponse(true, __('strings.details', ['name' => 'Customer']), $res);
    }
    /**
     * API for phone number add
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addNumber(Request $request)
    {
        $v = validator($request->all(), [
            'customer_profile_id' => 'required',
            'alias' => 'required',
            'number' => 'required',
            'type' => 'required',
            'send_sms' => 'required|boolean'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $find_customer = PocomosCustomerSalesProfile::where('id', $request->customer_profile_id)->first();
        if (!$find_customer) {
            return $this->sendResponse(false, 'Customer not found.');
        }
        $phone_number = [];
        $phone_number['alias'] = $request->alias;
        $phone_number['number'] = $request->number;
        $phone_number['type'] = $request->type;
        $phone_number['active'] = true;
        $phone = PocomosPhoneNumber::create($phone_number);
        if ($request->send_sms == true) {
            $send_sms = [];
            $send_sms['profile_id'] = $request->customer_profile_id;
            $send_sms['phone_id'] = $phone->id;
            PocomosCustomersNotifyMobilePhone::create($send_sms);
        }
        // map phone number with customer table
        $phones = [];
        $phones['profile_id'] = $request->customer_profile_id;
        $phones['phone_id'] = $phone->id;
        PocomosCustomersPhone::create($phones);
        return $this->sendResponse(true, 'Number added successfully', $phone);
    }

    /**
     * API for List of phone numbers
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listNumber(Request $request)
    {
        $v = validator($request->all(), [
            'profile_id' => 'required|exists:pocomos_customer_sales_profiles,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_customer = PocomosCustomerSalesProfile::findOrFail($request->profile_id);
        if (!$find_customer) {
            return $this->sendResponse(false, 'Customer not found.');
        }

        $phone_ids = PocomosCustomersPhone::where('profile_id', $request->profile_id)->pluck('phone_id');
        $phoner_numbers = PocomosPhoneNumber::where('active', 1)->whereIn('id', $phone_ids);

        if ($request->search) {
            $search = $request->search;
            $phoner_numbers->where(function ($query) use ($search) {
                $query->where('alias', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('number', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $phoner_numbers->count();
        $phoner_numbers->skip($perPage * ($page - 1))->take($perPage);

        $phoner_numbers = $phoner_numbers->get();

        $id = $request->profile_id;
        $phoner_numbers->map(function ($number) use ($id) {
            $find_notify_number = PocomosCustomersNotifyMobilePhone::where('profile_id', $id)->where('phone_id', $number->id)->first();
            if ($find_notify_number) {
                $number['is_notify'] = 'true';
            } else {
                $number['is_notify'] = 'false';
            }
        });

        return $this->sendResponse(true, 'List', [
            'phoner_numbers' => $phoner_numbers,
            'count' => $count,
        ]);
    }

    /**
     * API for Delete phone numbers
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteNumber(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'phone_id' => 'required|exists:pocomos_phone_numbers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_assignd_by_to = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        $phone_ids = PocomosCustomersPhone::where('profile_id', $find_assignd_by_to->id)->where('phone_id', $request->phone_id)->delete();

        $phoner_numbers = PocomosPhoneNumber::where('id', $request->phone_id)->update([
            'active' => 0
        ]);

        $PocomosCustomersNotifyMobilePhone = PocomosCustomersNotifyMobilePhone::where('profile_id', $find_assignd_by_to->id)->where('phone_id', $request->phone_id)->delete();

        return $this->sendResponse(true, 'Number deleted successfully', $phoner_numbers);
    }
    /**
     * API for phone number delete
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function editNumber(Request $request)
    {
        $v = validator($request->all(), [
            'phone_id' => 'required',
            'alias' => 'required',
            'number' => 'required',
            'type' => 'required',
            'send_sms' => 'required|boolean',
            'customer_profile_id' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $find_phone = PocomosPhoneNumber::where('id', $request->phone_id)->first();
        $find_phone->alias = $request->alias;
        $find_phone->number = $request->number;
        $find_phone->type = $request->type;
        $find_phone->active = true;
        $find_phone->save();
        $find_notify = PocomosCustomersNotifyMobilePhone::where('phone_id', $request->phone_id)->first();
        if ($find_notify && $request->send_sms == false) {
            PocomosCustomersNotifyMobilePhone::where('phone_id', $request->phone_id)->delete();
        } else {
            if ($request->send_sms == true && !$find_notify) {
                $send_sms = [];
                $send_sms['profile_id'] = $request->customer_profile_id;
                $send_sms['phone_id'] = $request->phone_id;
                PocomosCustomersNotifyMobilePhone::create($send_sms);
            }
        }
        return $this->sendResponse(true, 'Number edited successfully', $find_phone);
    }

    /**
     * API for save as lead from customer create flow
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function saveAsLead(Request $request)
    {
        DB::beginTransaction();
        $res = array();
        try {
            $service_address = ($request->service_address ?? array());
            $billing_information = ($request->billing_information ?? array());
            $same_as_service_address = ($request->billing_information ? $request->billing_information['same_as_service_address'] ?? null : null);
            $subscribe_to_mailing_list = ($request->billing_information ? $request->billing_information['subscribe_to_mailing_list'] ?? false : false);
            $service_information = ($request->service_information ?? array());
            $pricing_information = ($request->service_information ? $request->service_information['pricing_information'] ?? array() : array());
            $scheduling_information = ($request->service_information ? $request->service_information['scheduling_information'] ?? array() : array());
            $additional_information = ($request->service_information ? $request->service_information['additional_information'] ?? array() : array());
            $options = ($request->service_information ? $request->service_information['options'] ?? array() : array());
            $tags = ($request->service_information ? $request->service_information['tags'] ?? array() : array());
            $targeted_pests = ($request->service_information ? $request->service_information['targeted_pests'] ?? array() : array());
            $specialty_pests = ($request->service_information ? $request->service_information['specialty_pests'] ?? array() : array());
            $agreement_input = ($request->agreement ?? array());

            $phone = array();
            if (isset($service_address['phone'])) {
                $phone = [];
                $phone['alias'] = 'Primary';
                $phone['number'] = $service_address['phone'];
                $phone['type'] = $service_address['phone_type'];
                $phone['active'] = 1;
                $phone = PocomosPhoneNumber::create($phone);
            }

            $altPhone = array();
            if (isset($service_address['alt_phone'])) {
                $altPhone = [];
                $altPhone['alias'] = 'Alternate';
                $altPhone['number'] = $service_address['alt_phone'];
                $altPhone['type'] = $service_address['alt_phone_type'];
                $altPhone['active'] = 1;
                $altPhone = PocomosPhoneNumber::create($altPhone);
            }
            $address['street'] = $service_address['street'] ?? '';
            $address['suite'] = $service_address['suite'] ?? '';
            $address['city'] = $service_address['city'] ?? '';
            $address['postal_code'] = $service_address['postal'] ?? '';
            $address['region_id'] = $service_address['region_id'] ?? null;
            $address['phone_id'] = $phone->id ?? null;
            $address['alt_phone_id'] = $altPhone->id ?? null;
            $address['active'] = true;
            $address['validated'] = true;
            $address['valid'] = true;
            $PocomosAddress =  PocomosAddress::create($address);
            $pest_agreement_id = null;

            if (isset($service_information['contract_type_id']) && $service_information['contract_type_id']) {
                $pest_agr =  PocomosPestAgreement::whereId($service_information['contract_type_id'])->first();

                if ($pest_agr) {
                    $pest_agreement_id = $pest_agr->id ?? null;
                } else {
                    $pest_agreement['agreement_id'] = $pest_agr->agreement_id ?? null;
                    $pest_agreement['service_frequencies'] = serialize($service_information['service_frequency']) ?? null;
                    $pest_agreement['active'] = true;
                    $pest_agreement['initial_duration'] = $scheduling_information['initial_job_duration'];
                    $pest_agreement['regular_duration'] = $scheduling_information['job_duration'];
                    $pest_agreement['one_month_followup'] = false;
                    $pest_agreement['max_jobs'] = $service_information['num_of_jobs'];
                    $pest_agreement['default_agreement'] = false;
                    $agreement = PocomosPestAgreement::create($pest_agreement);
                    $pest_agreement_id = $agreement->id ?? null;
                }
            }

            // Data array for pocomos_leads_quotes table
            $input_leads_quotes['service_type_id'] = $service_information['service_type_id'] ?? null;

            if (isset($service_information['service_frequency'])) {
                $input_leads_quotes['service_frequency'] = serialize($service_information['service_frequency']);
            }

            $input_leads_quotes['found_by_type_id'] = $billing_information['marketing_type_id'] ?? null;
            $input_leads_quotes['salesperson_id'] = $billing_information['sales_person_id'] ?? null;
            $input_leads_quotes['pest_agreement_id'] = $pest_agreement_id;
            $input_leads_quotes['regular_initial_price'] = $pricing_information['normal_initial'] ?? 0.0;
            $input_leads_quotes['initial_discount'] = $pricing_information['initial_discount'] ?? 0.0;
            $input_leads_quotes['initial_price'] = $pricing_information['initial_price'] ?? 0.0;
            $input_leads_quotes['recurring_price'] = $pricing_information['recurring_price'] ?? 0.0;
            $input_leads_quotes['week_of_the_month'] = $service_information['week_of_the_month'] ?? null;
            $input_leads_quotes['day_of_the_week'] = $service_information['day_of_the_week'] ?? null;
            if ((isset($service_information['day_of_the_week']) && isset($service_information['week_of_the_month'])) && ($service_information['week_of_the_month'] && $service_information['day_of_the_week'])) {
                $input_leads_quotes['specific_recurring_schedule'] = 1;
            } else {
                $input_leads_quotes['specific_recurring_schedule'] = 0;
            }
            $input_leads_quotes['map_code'] = $service_information['map_code'] ?? '';
            $input_leads_quotes['autopay'] = false;
            $input_leads_quotes['auto_renew'] = true;
            $input_leads_quotes['active'] = true;
            $input_leads_quotes['tax_code'] = '';
            $input_leads_quotes['previous_balance'] = 0.0;
            $input_leads_quotes['preferred_time'] = $service_information['preferred_time'] ?? null;
            $input_leads_quotes['technician_id'] = $scheduling_information['technician_id'] ?? null;
            $input_leads_quotes['make_tech_preferred'] = $scheduling_information['make_tech_preferred'] ?? 0;
            $input_leads_quotes['county_id'] = $service_address['county_id'] ?? null; // county_id column is connected with pocomos_counties column.
            $PocomosLeadQuote = PocomosLeadQuote::create($input_leads_quotes);
            // Data entry of pocomos_lead_quotes_tags table
            // It is connected with pocomos_tags table
            foreach ($tags as $tag) {
                $input_details['tag_id'] = $tag;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuoteTag::create($input_details);
            }

            foreach ($targeted_pests as $pest) {
                $input_details['pest_id'] = $pest;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuotPest::create($input_details);
            }

            foreach ($specialty_pests as $special_pest) {
                $input_details['pest_id'] = $special_pest;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuoteSpecialtyPest::create($input_details);
            }

            // Data entry of pocomos_lead_reminders table
            // if ($service_information['reminder_date']) {
            //     $pocomos_lead_reminders = [];
            //     $pocomos_lead_reminders['note'] = $request->note;
            //     $pocomos_lead_reminders['reminder_date'] = $service_information['reminder_date'];
            //     $PocomosLeadReminder = PocomosLeadReminder::create($pocomos_lead_reminders);
            // }

            // Data array for pocomos_notes table
            if (isset($service_information['initial_job_notes']) && $service_information['initial_job_notes']) {
                $notes_1['user_id'] = null;
                $notes_1['summary'] = $service_information['initial_job_notes'];
                $notes_1['body'] = "";
                $notes_1['interaction_type'] = 'Other';
                $notes_1['active'] = 1;
                $PocomosNoteinitial = PocomosNote::create($notes_1);
            }
            if (isset($service_information['permanent_notes']) && $service_information['permanent_notes']) {
                $notes_2['user_id'] = null;
                $notes_2['summary'] = $service_information['permanent_notes'];
                $notes_2['body'] = "";
                $notes_2['interaction_type'] = 'Other';
                $notes_2['active'] = 1;
                $PocomosNotepermanent = PocomosNote::create($notes_2);
            }

            // Data array for pocomos_leads table
            $input = [];
            $input['contact_address_id'] = $PocomosAddress->id;
            $input['billing_address_id'] = $PocomosAddress->id;
            $input['quote_id'] = $PocomosLeadQuote->id;
            $input['first_name'] = $service_address['first_name'] ?? '';
            $input['last_name'] = $service_address['last_name'] ?? '';
            $input['email'] = $service_address['email'] ?? '';
            $input['status'] = 'Lead';
            $input['secondary_emails'] = implode(',', $service_address['secondary_emails'] ?? array());

            $input['company_name'] = $service_address['company_name'] ?? '';
            $input['external_account_id'] = '';
            $input['subscribed'] = false;
            $input['active'] = true;

            // if (isset($PocomosLeadReminder)) {
            //     $input['lead_reminder_id'] = $PocomosLeadReminder->id;
            // }
            if (isset($service_information['initial_job_notes']) && $service_information['initial_job_notes']) {
                $input['initial_job_note_id'] = $PocomosNoteinitial->id;
            }

            $PocomosLead =  PocomosLead::create($input);

            if (isset($service_information['permanent_notes']) && $service_information['permanent_notes']) {
                $leadNote['lead_id'] = $PocomosLead->id;
                $leadNote['note_id'] = $PocomosNotepermanent->id;
                $PocomosLeadNote = PocomosLeadNote::create($leadNote);
            }

            DB::commit();
            $status = true;
            $message = __('strings.create', ['name' => 'Lead']);
            $res['lead_id'] = $PocomosLead->id;
        } catch (\Exception $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }
        return $this->sendResponse($status, $message, $res);
    }

    /**
     * API for payment schedule customer create flow
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function paymentSchedule(CustomerAgreementRequest $request)
    {
        $service_information = ($request->service_information ?? array());
        $service_address = ($request->service_address ?? array());
        $contract_type_id = $service_information['contract_type_id'] ?? null;
        $agreement_input = ($request->agreement ?? array());
        $additional_information = ($request->service_information ? $request->service_information['additional_information'] ?? array() : array());

        $pest_agreement = PocomosPestAgreement::whereId($contract_type_id)->first();
        if ($pest_agreement) {
            $agreement = PocomosAgreement::findOrFail($pest_agreement->agreement_id);
            $exceptions = unserialize($pest_agreement->exceptions);
        } else {
            $agreement = array();
            $exceptions = array();
        }

        if (isset($service_information['service_type_id']) && $service_information['service_type_id']) {
            $service_type_res = PocomosPestContractServiceType::findOrFail($service_information['service_type_id']);
        } else {
            $service_type_res = array();
        }

        $tax_code = array();
        if ($additional_information) {
            $tax_code = PocomosTaxCode::findOrFail($additional_information['tax_code_id']);
        }

        $scheduling_information = ($request->service_information ? $request->service_information['scheduling_information'] ?? array() : array());
        $pricing_information = ($request->service_information ? $request->service_information['pricing_information'] ?? array() : array());
        $billing_information = ($request->billing_information ?? array());
        $technician_id = $scheduling_information['technician_id'];
        $targeted_pests = ($request->service_information ? $request->service_information['targeted_pests'] ?? array() : array());
        $specialty_pests = ($request->service_information ? $request->service_information['specialty_pests'] ?? array() : array());

        $pests_name = PocomosPest::whereIn('id', array_merge($targeted_pests, $specialty_pests))->pluck('name')->toArray();

        $res = array();
        $data = array();

        try {
            $signature_path = '';
            $agreement_body = $agreement->agreement_body;
            if (isset($agreement_input['signature']) && $agreement_input['signature']) {
                $signature = $agreement_input['signature'];
                // $signature_path = $signature->store('public/files');
                $signature_id = $this->uploadFileOnS3('Customer', $signature);
                $file = OrkestraFile::findOrFail($signature_id);
                $signature_path = $file['path'];
            }

            $variables = PocomosFormVariable::where('enabled', true)->where('active', true)->get();

            $res = DB::select(DB::raw("SELECT ofd.path as 'technician_photo', oud.first_name as 'technician_name', pco.fax as 'office_fax', pco.customer_portal_link, cld.path as 'company_logo', CONCAT(pad.suite, ', ' , pad.street, ', ' , pad.city, ', ' , pad.postal_code) as 'office_address', CONCAT(tad.suite, ', ' , tad.street, ', ' , tad.city, ', ' , tad.postal_code) as 'technician_address'
            FROM pocomos_technicians AS pt
            JOIN pocomos_company_office_users AS cou ON pt.user_id = cou.id
            JOIN pocomos_company_office_user_profiles AS oup ON cou.profile_id = oup.id
            JOIN orkestra_files AS ofd ON oup.photo_id = ofd.id
            JOIN orkestra_users AS oud ON oup.user_id = oud.id
            JOIN pocomos_company_offices AS pco ON cou.office_id = pco.id
            JOIN orkestra_files AS cld ON pco.logo_file_id = cld.id
            JOIN pocomos_addresses AS pad ON pco.contact_address_id = pad.id
            JOIN pocomos_addresses AS tad ON pt.routing_address_id = tad.id
            WHERE pt.id = '$technician_id' AND pt.active = 1"));
            $res = $res[0] ?? array();

            $technician = $res->technician_name ?? '';
            $service_type = $service_type_res->name ?? '';
            $office_address = $res->office_address ?? '';
            $office_phone = $res->office_fax ?? '';
            if (isset($service_address['suite']) && $service_address['street'] && $service_address['city'] && $service_address['state'] && $service_address['postal']) {
                $service_addr = $service_address['suite'] . ', ' . $service_address['street'] . ', ' . $service_address['city'] . ', ' . $service_address['state'] . ', ' . $service_address['postal'];
            } else {
                $service_addr = '';
            }
            if (isset($billing_information['suite']) && $billing_information['street'] && $billing_information['city'] && $billing_information['state'] && $billing_information['postal']) {
                $billing_address = $billing_information['suite'] . ', ' . $billing_information['street'] . ', ' . $billing_information['city'] . ', ' . $billing_information['state'] . ', ' . $billing_information['postal'];
            } else {
                $billing_address = '';
            }

            $company_logo = $res->company_logo ?? '';
            $selected_pests = implode(', ', $pests_name);
            $agreement_length = $agreement['length'] ?? 'N/A';
            $technician_photo = $res->technician_photo ?? '';
            $technician_bio = $res->technician_address ?? '';
            $initial_price_tax = $tax_code->tax_rate ?? '';
            $contract_value_tax = $tax_code->tax_rate ?? '';
            $initial_price_with_tax = $pricing_information['initial_price'] ?? 0 - ($pricing_information['initial_price'] ?? 0 * $tax_code->tax_rate / 100);
            $customer_portal_link = $res->customer_portal_link ?? '';

            foreach ($variables as $var) {
                $isSerializedValid = @unserialize($var['type']);
                if ($isSerializedValid !== false) {
                    $types_res = unserialize($var['type']);
                    if (in_array('Pest Agreement', $types_res)) {
                        $variable_name = $var['variable_name'] ?? null;

                        if (strpos($agreement_body, $variable_name) !== false) {
                            if ($variable_name === 'customer_name') {
                                if (isset($service_address['first_name']) & isset($service_address['last_name'])) {
                                    $value = $service_address['first_name'] . ' ' . $service_address['last_name'];
                                } else {
                                    $value = '';
                                }
                            } elseif ($variable_name === 'service_address') {
                                $value = $service_addr;
                            } elseif ($variable_name === 'customer_service_address') {
                                $value = $service_addr;
                            } elseif ($variable_name === 'service_city') {
                                $value = $service_address['city'] ?? '';
                            } elseif ($variable_name === 'service_state') {
                                $value = $service_address['state'] ?? '';
                            } elseif ($variable_name === 'service_zip') {
                                $value = $service_address['postal'] ?? '';
                            } elseif ($variable_name === 'customer_phone') {
                                $value = $service_address['phone'] ?? '';
                            } elseif ($variable_name === 'customer_email') {
                                $value = $service_address['email'] ?? '';
                            } elseif ($variable_name === 'contract_start_date') {
                                $value = $service_information['contract_start_date'] ?? '';
                            } elseif ($variable_name === 'customer_signature') {
                                if ($signature_path) {
                                    $value = '<img height="100px" width="200px" src="' . $signature_path . '">';
                                } else {
                                    $value = '';
                                }
                            } elseif ($variable_name === 'salesperson_signature') {
                                // $value = '<img height="100px" width="200px" src="'.storage_path('app'). '/' . $signature_path.'">';
                            } elseif ($variable_name === 'balance') {
                                $value = 0.00;
                            } elseif ($variable_name === 'credit') {
                                $value = 0.00;
                            } elseif ($variable_name === 'invoice_numbers') {
                                $value = '';
                            } elseif ($variable_name === 'technician') {
                                $value = $technician;
                            } elseif ($variable_name === 'service_date') {
                                $value = $scheduling_information['initial_date'] ?? '';
                            } elseif ($variable_name === 'service_time') {
                                $value = '';
                            } elseif ($variable_name === 'service_frequency' && @unserialize($service_information['service_frequency'])) {
                                $value = implode(', ', unserialize($service_information['service_frequency']));
                            } elseif ($variable_name === 'service_type') {
                                $value = $service_type ?? '';
                            } elseif ($variable_name === 'office_address') {
                                $value = $office_address ?? '';
                            } elseif ($variable_name === 'office_phone') {
                                $value = $office_phone;
                            } elseif ($variable_name === 'service_address') {
                                $value = $service_addr;
                            } elseif ($variable_name === 'company_logo') {
                                $value = $company_logo ?? '';
                            } elseif ($variable_name === 'customer_last_name') {
                                $value = $service_address['last_name'];
                            } elseif ($variable_name === 'customer_service_address') {
                                $value = $service_address;
                            } elseif ($variable_name === 'customer_billing_address') {
                                $value = $billing_address;
                            } elseif ($variable_name === 'agreement_price_info') {
                                $value = $pricing_information['initial_price'] ?? 0.00;
                            } elseif ($variable_name === 'auto_pay_checkbox') {
                                $value = $billing_information['is_enroll_auto_pay'] ? 'Autopay' : 'No Autopay';
                            } elseif ($variable_name === 'selected_pests') {
                                $value = $selected_pests;
                            } elseif ($variable_name === 'agreement_length') {
                                $value = $agreement_length;
                            } elseif ($variable_name === 'total_contract_value') {
                                $value = $pricing_information['initial_price'] ?? 0.00;
                            } elseif ($variable_name === 'customer_company_name') {
                                $value = $service_address['company_name'] ?? '';
                            } elseif ($variable_name === 'next_service') {
                                $value = '';
                            } elseif ($variable_name === 'contract_addendum') {
                                $value = $agreement_input['addendum'] ?? '';
                            } elseif ($variable_name === 'customer_portal_link') {
                                $value = $customer_portal_link;
                            } elseif ($variable_name === 'contract_recurring_price') {
                                $value = $pricing_information['recurring_price'];
                            } elseif ($variable_name === 'technician_photo') {
                                $value = $technician_photo;
                            } elseif ($variable_name === 'technician_bio') {
                                $value = $technician_bio;
                            } elseif ($variable_name === 'contract_initial_price') {
                                $value = $pricing_information['initial_price'] ?? 0.00;
                            } elseif ($variable_name === 'contract_total_contract_value_tax') {
                                $value = $contract_value_tax;
                            } elseif ($variable_name === 'contract_initial_price_with_tax') {
                                $value = $initial_price_with_tax;
                            } elseif ($variable_name === 'contract_initial_discount') {
                                $value = $pricing_information['initial_discount'];
                            } elseif ($variable_name === 'contract_initial_price_tax') {
                                $value = $initial_price_tax;
                            } elseif ($variable_name === 'customer_last_service_date') {
                                $value = '';
                            } elseif ($variable_name === 'contract_recurring_discount') {
                                $value = $pricing_information['discount_per_job'] ?? 0;
                            } else {
                                $value = '';
                            }
                            $agreement_body = str_replace('{{ ' . $variable_name . ' }}', $value, $agreement_body);
                        }
                    }
                }
            }
            // $status = true;
            // $message = __('strings.details', ['name' => 'Agreement']);

            $schedule_data = $this->getServiceBillningScheduleV2($request->all());

            $data = array(
                // 'pricing_overview' => $serviceCalendarHelper->getPricingOverview($contract),
                'service_schedule' => $schedule_data['service_schedule'] ?? '',
                'billing_schedule' => $schedule_data['billing_schedule'] ?? '',
                'agreement_body' => $agreement_body,
                'exception_list' => $exceptions,
                'input_data' => $request->all()
            );

            // $res = $agreement_body;
        } catch (\Exception $e) {
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        return $this->sendResponse(true, __('strings.details', ['name' => 'Agreement']), $data);
    }

    /**
     * API for send Email Remote Completion
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function remoteCompletionEmail(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer_id = $request->customer_id;
        $contract_id = $request->contract_id;

        $this->sendRemoteCompletionEmail($customer_id, $contract_id);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Remote Completion Email Send']));
    }

    /**
     * API for export customers
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportCustomers(Request $request)
    {
        $v = validator($request->all(), [
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'exists:pocomos_customers,id'
        ], [
            'customer_ids.*.exists' => __('validation.exists', ['attribute' => 'customer id'])
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customerIds = $request->customer_ids ?? array();

        $exported_columns = $request->exported_columns ?? array();

        ExportCustomers::dispatch($exported_columns, $customerIds);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Customer export report generation started']));
    }

    /**
     * API for send email exported details file
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendEmailExportedDetails(Request $request)
    {
        $v = validator($request->all(), [
            'recipient' => 'required|email',
            'subject' => 'required',
            'body' => 'required',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'exists:pocomos_customers,id'
        ], [
            'customer_ids.*.exists' => __('validation.exists', ['attribute' => 'customer id'])
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customerIds = $request->customer_ids ?? array();

        $columns = ["status", "name", "email", "postalCode", "phone", "address", "lastServiceDate", "nextServiceDate", "office", "office_fax", "email", "company_name", "billing_name", "secondary_emails", "street", "city", "billing_street", "billing_postal", "billing_city", "sales_status", "contract_start_date", "salesperson", "map_code", "service_type", "autopay", "service_frequency", "date_created", "initial_price", "recurring_price", "regular_initial_price", "last_service_date", "balance", "first_name", "last_name", "account_type", "next_service_date"];

        // Job dispacth for sending customer export details
        SendEmailCustomerExport::dispatch($columns, $request->all(), $customerIds);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Customer details Email send']));
    }

    /**
     * API for update account status
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateAccountStatus(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id'         => 'required|exists:pocomos_customers,id',
            'status'              => 'required|in:Active,On-Hold,Inactive',
            'status_reason'       => 'nullable|exists:pocomos_status_reasons,id',
            'modify_sales_status' => 'nullable|boolean',
            'sales_status'        => 'required_if:modify_sales_status,==,1|nullable|exists:pocomos_sales_status,id',
            // 'contracts'           => 'required_if:modify_sales_status,==,1|nullable|exists:pocomos_contracts,id|array',
            'contracts'           => 'nullable|exists:pocomos_contracts,id|array',
            'office_id'           => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $update_details = [
            'status'           => $request->status,
            'status_reason_id' => $request->status_reason ?? null,
            'sales_status_id'  => $request->sales_status ?? null,
        ];
        if ($request->modify_sales_status) {
            $update_details['sales_status_modified'] = date('Y-m-d H:i:s');
        }
        if ($request->contracts) {
            $pocomos_contract = PocomosContract::whereIn('id', $request->contracts)->update($update_details);
        }

        if ($request->status == 'Inactive') {
            $this->deactivateCustomer($request->customer_id, $update_details['status_reason_id'], true);
        } elseif ($request->status == 'Active') {
            $this->activateCustomer($request->customer_id, $update_details);
        } else {
            $this->placeCustomerOnHold($request->customer_id, $request->office_id, true);
        }
        return $this->sendResponse(true, __('strings.update', ['name' => 'Account details']));
    }

    /**
     * API for view agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function viewAgreement(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id',
            'is_download' => 'nullable|boolean'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer_id = $request->customer_id;
        $contract_id = $request->contract_id;
        $template = $this->getAgreementBody($contract_id, $customer_id);

        if ($request->is_download) {
            $url =  "contract/" . preg_replace('/[^A-Za-z0-9\-]/', '', $contract_id) . '.pdf';
            $pdf = PDF::loadView('pdf.dynamic_render', compact('template'));
            Storage::disk('s3')->put($url, $pdf->output(), 'public');
            $path = Storage::disk('s3')->url($url);

            // header('Access-Control-Allow-Origin:  *');
            // header("Cache-Control: public");
            // header("Content-Description: File Transfer");
            // header("Content-Disposition: attachment; filename=" . basename($path));
            // header("Content-Type: " . $contract_id.'.pdf');
            // return readfile($path);

            $res['url'] = $path;
        }
        $status = true;
        $message = __('strings.details', ['name' => 'Agreement']);
        $res['agreement_body'] = $template;

        return $this->sendResponse($status, $message, $res);
    }


    /**
     * API for capture signature
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function captureSignature(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required',
            'signature' => 'required|mimes:png,jpg,jpeg',
            'contract_id' => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer_id = $request->customer_id;
        $contract_id = $request->contract_id;
        $customer = PocomosCustomer::findOrFail($customer_id);

        $agreement_sign_id = null;
        $signed = false;
        if ($request->signature) {
            $agreement_sign_id = $this->uploadFileOnS3('Customer', $request->signature);
            $signed = true;
        }

        $pocomos_contract['signature_id'] = $agreement_sign_id;
        $pocomos_contract['signed'] = $signed;
        PocomosContract::where('id', $contract_id)->update($pocomos_contract);

        $files_input = [
            [
                'customer_id' => $customer->id,
                'file_id' => $agreement_sign_id
            ]
        ];
        PocomosCustomersFile::insert($files_input);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Signature added']));
    }

    /**
     * API for regenerate agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function regenerate_agreement(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $contract = PocomosContract::findOrFail($request->contract_id);
        $contract->status = 'Unsigned';
        $contract->save();

        // $path = storage_path('app/public/pdf') . '/agreement_' . $request->customer_id . '.pdf';
        // if ($path && file_exists($path)) {
        //     unlink($path);
        // }

        $template = $this->getAgreementBody($request->contract_id, $request->customer_id);

        $url =  "contract/" . preg_replace('/[^A-Za-z0-9\-]/', '', $request->contract_id) . '.pdf';
        $pdf = PDF::loadView('pdf.dynamic_render', compact('template'));
        Storage::disk('s3')->put($url, $pdf->output(), 'public');
        $path = Storage::disk('s3')->url($url);

        // header('Access-Control-Allow-Origin:  *');
        // header("Cache-Control: public");
        // header("Content-Description: File Transfer");
        // header("Content-Disposition: attachment; filename=" . basename($path));
        // header("Content-Type: " . $request->contract_id.'.pdf');
        // return readfile($path);

        $res['url'] = $path;
        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Customer Agreement Regenerated']), $res);
    }

    /**
     * API for update geo code details
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateGeocodeDetails(Request $request)
    {
        $v = validator($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
            'override_geocode' => 'required',
            'contact_address_id' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $contact_address = PocomosAddress::findOrFail($request->contact_address_id);
        $contact_address->latitude = $request->latitude ?? null;
        $contact_address->longitude = $request->longitude ?? null;
        $contact_address->override_geocode = $request->override_geocode ? true : false;
        $contact_address->save();

        return $this->sendResponse(true, __('strings.update', ['name' => 'Customer GeoCode']));
    }

    /**
     * API for cancel contract details
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function confirmCancelContract(Request $request)
    {
        $v = validator($request->all(), [
            'status_reason' => 'nullable|exists:pocomos_status_reasons,id',
            'change_sales_status' => 'required|boolean',
            'sales_status' => 'required|exists:pocomos_sales_status,id',
            'deactivate_account' => 'required|boolean',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        if ($request->change_sales_status) {
            $this->cancelContract($request->contract_id, $request->status_reason, $request->sales_status);
        } else {
            $this->cancelContract($request->contract_id, $request->status_reason);
        }

        if ($request->deactivate_account) {
            $this->deactivateCustomer($request->customer_id, $request->status_reason, /* deactivateChildren */ true);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Customer Contract Cancelled']));
    }

    /**
     * API for update service frequency
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateServiceFrequency(Request $request)
    {
        $v = validator($request->all(), [
            'service_frequency' => 'required',
            'recurring_price' => 'required',
            'initial_date' => 'required',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $contract = PocomosPestContract::where('contract_id', $request->contract_id)->first();

        $salesContract = $contract->contract_details;
        $result = $this->updateBillingFrequency($contract, $salesContract, $request->service_frequency, $request->initial_date);

        $result = $this->updateRecurringPrice($contract, $request->recurring_price);

        return $this->sendResponse(true, __('strings.update', ['name' => 'The new Schedule has been']));
    }

    /**
     * API for update contract pricing
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateContractPricing(Request $request)
    {
        $v = validator($request->all(), [
            'initial_price' => 'required',
            'recurring_price' => 'required',
            'original_value' => 'required',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pest_contract = PocomosPestContract::where('contract_id', $request->contract_id)->first();
        $pest_contract->initial_price = $request->initial_price;
        $pest_contract->recurring_price = $request->recurring_price;
        $pest_contract->save();

        $contract = PocomosContract::findOrFail($request->contract_id);
        $billing_frequency = $contract->billing_frequency;

        if (in_array($billing_frequency, array(config('constants.MONTHLY'), config('constants.INITIAL_MONTHLY')))) {
            $miscInvoices = $this->findFutureMiscInvoices($request->contract_id);
            foreach ($miscInvoices as $invoice) {
                $this->updateInvoiceRecurringPrice($request->contract_id, $invoice, $request->recurring_price);
            }
        } else {
            $invoices = $this->findFutureInvoices($request->contract_id);
            foreach ($invoices as $invoice) {
                $this->updateInvoiceRecurringPrice($request->contract_id, $invoice, $request->recurring_price);
            }
        }
        $contract->save();

        return $this->sendResponse(true, __('strings.update', ['name' => 'The contract pricing details has been']));
    }

    /**
     * Updates all jobs on a route commission settings
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateContractCommission(Request $request)
    {
        $v = validator($request->all(), [
            'commission_type' => 'required',
            'commission_value' => 'required',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $contract = PocomosPestContract::where('contract_id', $request->contract_id)->first();

        $jobs = PocomosJob::where('contract_id', $contract->id)->get();

        foreach ($jobs as $job) {
            $job->commission_type = $request->commission_type;
            $job->commission_value = $request->commission_value;
            $job->commission_edited = true;
            $job->save();
        }

        return $this->sendResponse(true, __('strings.update', ['name' => 'Commission settings']));
    }

    /**
     * updates service frequency
     *
     * @param Request $request
     */
    public function updateContractServiceType(Request $request)
    {
        $v = validator($request->all(), [
            'service_type' => 'required',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $contract = PocomosPestContract::where('contract_id', $request->contract_id)->first();

        $result = $this->updateServiceType($contract, $request->service_type);

        return $this->sendResponse(true, __('strings.update', ['name' => 'The contract\'s service type']));
    }

    public function applyDiscounts(Request $request, $customerId)
    {
        $v = validator($request->all(), [
            'customer_id' => 'exists:pocomos_customers,id',
            'discounts.*.contract' => 'required',
            'discounts.*.type' => 'required|in:dollar,percent',
            'discounts.*.amount' => 'required|numeric|gt:0',
            'discounts.*.discount_id' => 'nullable',
            'discounts.*.discount_name' => 'required_with:discounts.*.discount_id',
            'discounts.*.description' => 'required',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($customerId);

        $discounts = $request->discounts;

        $discountAmount = 0;
        $result = array();

        foreach ($discounts as $discount) {
            $contractId = $discount['contract'];
            $discountAmount = $discount['amount'];

            $contract = PocomosContract::findorfail($contractId);

            $pestContract = PocomosPestContract::with([
                'jobs_details.invoice.invoice_items',
                'jobs_details.invoice.tax_code',
                'misc_invoices.invoice.invoice_items'
            ])->whereContractId($contractId)->first();

            if ($contract->billing_frequency === 'Per service') {
                $jobs = $pestContract->jobs_details;
                $result = $this->handleDiscountTypeForJobInvoice($pestContract, $discount, $discountAmount, $jobs);
            } else {
                $jobs = $pestContract->misc_invoices;
                $result = $this->handleDiscountTypeForMiscInvoice($contract, $discount, $discountAmount, $jobs);
            }
            sleep(3);
        }

        return $this->sendResponse(true, 'The discount has been applied successfully.');
        /*
            get contracts from customer's contracts
        */
    }

    public function handleDiscountTypeForJobInvoice($pestContract, $discountType, $discountAmount, $jobs)
    {
        // return 99;
        foreach ($jobs as $job) {
            // return $job;
            if (isset($job->invoice->status) && $job->invoice->status == 'Not sent') {
                if ($discountType['type'] === 'percent') {
                    // return 9999;
                    $price = $discountType['amount'];
                    $amountDue = $job->invoice->invoice_items[0]->price;
                    $discount = -abs(round($amountDue, 2) * round($price / 100, 2));
                } else {
                    $discount = -abs($discountType['amount']);
                }
                // dd(11);
                if ($discountType['discount_name']) {
                    $discountTypeName = $discountType['discount_name'];
                } else {
                    $discountTypeName = "Default";
                }

                $type = $discountType['type'];

                if ($type == "dollar") {
                    $description = sprintf('%s : %s', $discountTypeName, $discountType['description']);
                } else {
                    $description = sprintf('%s : %s : %s', $discountType['amount'] . '%', $discountTypeName, $discountType['description']);
                }

                // return $job->invoice;

                $this->addDiscountItem($description, $discount, true, $job->invoice);
            }
        }

        if (!isset($description)) {
            if ($discountType['discount_name']) {
                $discountTypeName = $discountType['discount_name'];
            } else {
                $discountTypeName = "Default";
            }

            if ($discountType['type'] == "dollar") {
                $description = sprintf('%s : %s', $discountTypeName, $discountType['description']);
            } else {
                $description = sprintf('%s : %s : %s', $discountType['amount'] . '%', $discountTypeName, $discountType['description']);
            }
        }
        // dd(11);
        // return $description;

        if ($discountAmount) {
            $pestDiscountItems = PocomosPestDiscountTypeItem::create([
                'discount_id' => $discountType['discount_id'],
                'rate'        => 0.00,
                'amount' => $discountAmount,
                'type' => $discountType['type'],
                'description' => $description ?? '',
                'contract_id' => $pestContract->contract_id,
            ]);
        }
        // return $contract;
    }

    public function handleDiscountTypeForJobInvoiceForceContractRenew($pestContract)
    {
        $jobs = $pestContract->jobs_details;
        $discountTypes = $pestContract->contract_details->discount_types;

        foreach ($jobs as $job) {
            foreach ($discountTypes as $discountType) {
                if ($discountType->discount->auto_renew == true) {
                    if ($discountType->discount->type === 'percent') {
                        // return 9999;
                        $price = $discountType->discount->amount;
                        $amountDue = $job->invoice->invoice_items[0]->price;
                        $discount = -abs(round($amountDue, 2) * round($price / 100, 2));
                    } else {
                        $discount = -abs($discountType->discount->amount);
                    }

                    $type = $discountType->discount->type;

                    if ($type == "static") {
                        $description = sprintf('%s : %s', $discountType->discount->name, $discountType->discount->description);
                    } else {
                        $description = sprintf('%s : %s : %s', $discountType->discount->amount . '%', $discountType->discount->name, $discountType->discount->description);
                    }

                    // return $job->invoice;

                    $this->addDiscountItem($description, $discount, true, $job->invoice);
                }
            }
        }
        // return $contract;
    }

    public function handleDiscountTypeForMiscInvoice($contract, $discountType, $discountAmount, $miscInvoices)
    {
        $invoices = $miscInvoices;
        $billingFrequency = $contract->billing_frequency;
        $numberOfPayments = $contract->number_of_payments;

        foreach ($invoices as $invoice) {
            // return $invoice->invoice;
            if ($invoice->invoice->status == 'Not sent') {
                if ($discountType['type'] === 'percent') {
                    if ($billingFrequency == "Installments") {
                        $price = $discountType['amount'];
                        $amountDue = $invoice->invoice->invoice_items[0]->price;
                        $totalDiscount = abs(round($amountDue * $numberOfPayments, 2) * round($price / 100, 2));
                        $discount = -abs($totalDiscount / $numberOfPayments);
                    } else {
                        $price = $discountType['amount'];
                        $amountDue = $invoice->invoice->invoice_items[0]->price;
                        $discount = -abs(round($amountDue, 2) * round($price / 100, 2));
                    }
                } else {
                    if ($billingFrequency == "Installments") {
                        $discount = -abs($discountType['amount'] / $numberOfPayments);
                    } else {
                        $discount = -abs($discountType['amount']);
                    }
                }
                $type = $discountType['type'];
                if ($discountType['discount_name']) {
                    $discountTypeName = $discountType['discount_name'];
                } else {
                    $discountTypeName = "Default";
                }

                if ($type == "static") {
                    $description = sprintf('%s : %s', $discountTypeName, $discountType['description']);
                } else {
                    $description = sprintf('%s : %s : %s', $discountType['amount'] . '%', $discountTypeName, $discountType['description']);
                }
                // $this->addDiscountItem($description, $discount, true);
                $this->addDiscountItem($description, $discount, true, $invoice->invoice);
            }
        }

        // return $contract->id;
        // dd(121121);

        if (!isset($description)) {
            if ($discountType['discount_name']) {
                $discountTypeName = $discountType['discount_name'];
            } else {
                $discountTypeName = "Default";
            }

            if ($discountType['type'] == "dollar") {
                $description = sprintf('%s : %s', $discountTypeName, $discountType['description']);
            } else {
                $description = sprintf('%s : %s : %s', $discountType['amount'] . '%', $discountTypeName, $discountType['description']);
            }
        }

        if ($discountAmount) {
            $pestDiscountItems = PocomosPestDiscountTypeItem::create([
                'discount_id' => $discountType['discount_id'],
                'rate'        => 0.00,
                'amount' => $discountAmount,
                'type' => $discountType['type'],
                'description' => $description,
                'contract_id' => $contract->id,
            ]);
        }
        // return $contract;
    }

    public function handleDiscountTypeForMiscInvoiceForceContractRenew($pestContract)
    {
        $invoices = $pestContract->misc_invoices;
        $discountTypes = $pestContract->contract_details->discount_types;
        $billingFrequency = $pestContract->contract_details->billing_frequency;
        $numberOfPayments = $pestContract->contract_details->renew_number_of_payment;

        $count = 1;

        foreach ($invoices as $invoice) {
            // return $invoice->invoice;
            foreach ($discountTypes as $discountType) {
                if ($discountType->discount->auto_renew == true) {
                    if ($discountType->discount->type === 'percent') {
                        if ($billingFrequency == "Installments") {
                            $price = $discountType->discount->amount;
                            $amountDue = $invoice->invoice->invoice_items[0]->price;
                            $totalDiscount = abs(round($amountDue * $numberOfPayments, 2) * round($price / 100, 2));
                            $discount = -abs($totalDiscount / $numberOfPayments);
                        } else {
                            $price = $discountType->discount->amount;
                            $amountDue = $invoice->invoice->invoice_items[0]->price;
                            $discount = -abs(round($amountDue, 2) * round($price / 100, 2));
                        }
                    } else {
                        if ($billingFrequency == "Installments") {
                            $discount = -abs($discountType->discount->amount / $numberOfPayments);
                        } else {
                            $discount = -abs($discountType->discount->amount);
                        }
                    }

                    $type = $discountType->discount->type;

                    if ($type == "static") {
                        $description = sprintf('%s : %s', $discountType->discount->name, $discountType->discount->description);
                    } else {
                        $description = sprintf('%s : %s : %s', $discountType->discount->amount . '%', $discountType->discount->name, $discountType->discount->description);
                    }

                    $this->addDiscountItem($description, $discount, true, $invoice->invoice);
                }
            }
        }
    }

    /* public function addDiscountItem($description, $price, $discount = false, $invoice)
    {
        $invoice_item['tax_code_id'] = $invoice->tax_code_id;
        $invoice_item['sales_tax'] = $invoice->tax_code->tax_rate;
        $invoice_item['description'] = $description;
        $invoice_item['price'] =  $price;

        if ($discount) {
            $invoice_item['type'] = 'Discount';
        }

        $invoice_item['invoice_id'] = $invoice->id;
        $invoice_item['active'] = 1;
        $invoice_item = PocomosInvoiceItems::create($invoice_item);

        $this->addInvoiceItemSwitch($invoice_item, $invoice);
    } */

    /* public function addInvoiceItem1($invoiceItem, $invoice)
    {
        $invoice = $invoice;

        $itemType = $invoiceItem->type;

        switch ($itemType) {
                //            Not important for removal
            case 'Adjustment':
                $invoice->balance   += round($invoiceItem->price + ($invoiceItem->price * $invoiceItem->sales_tax), 2);
                break;
                //                cause for the issue with balance. Credit should be substracted from the balance. That's it.1
            case 'Credit':
                $invoice->balance   += round($invoiceItem->price + ($invoiceItem->price * $invoiceItem->sales_tax), 2);
                break;
            case 'Discount':
                $invoice->amount_due += $invoiceItem->price;
                $invoice->balance   += round($invoiceItem->price + ($invoiceItem->price * $invoiceItem->sales_tax), 2);
                break;
            default:
                $invoice->amount_due += $invoiceItem->price;
                $invoice->balance   += round($invoiceItem->price + ($invoiceItem->price * $invoiceItem->sales_tax), 2);
                break;
        }

        $invoice->save();
    } */


    public function givenDiscounts(Request $request)
    {
        $v = validator($request->all(), [
            'contracts' => 'required|exists:pocomos_contracts,id',
            // 'date' => 'required',
            // 'technician' => 'required',
            // 'duration' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $discounts = PocomosPestDiscountTypeItem::whereIn('contract_id', $request->contracts)->get();

        return $this->sendResponse(true, "given discounts", $discounts);
    }


    public function updateDiscount(Request $request, $pdtId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'contract' => 'required|exists:pocomos_contracts,id',
            'description' => 'required',
            'amount' => 'required|numeric|gt:0',
            // 'technician' => 'required',
            // 'duration' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // return auth()->user();

        $discountType = PocomosPestDiscountTypeItem::findorfail($pdtId);
        // return $discountType->contract;
        $discountType->description = $request->description;
        $discountType->amount = $request->amount;
        $discountType->save();

        //getDiscountTypeInvoiceItemData
        $invoiceItems = PocomosInvoiceItems::select('*', 'pocomos_invoice_items.id')
            ->join('pocomos_invoices as pi', 'pocomos_invoice_items.invoice_id', 'pi.id')
            ->join('pocomos_contracts as pc', 'pi.contract_id', 'pc.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
            ->where('pag.office_id', $request->office_id)
            ->where('pc.id', $request->contract)
            ->where('pocomos_invoice_items.description', $discountType->description)
            ->where('pocomos_invoice_items.type', 'Discount')
            ->get();

        foreach ($invoiceItems as $item) {
            $invoice = $item->invoice;

            $canBeRecalculated = $this->canBeRecalculated($invoice);
            // dd(11);

            if ($canBeRecalculated) {
                $item->invoice_id = null;
                $item->active = false;

                $this->updateInvoiceTaxNew($invoice, false);
            }
        }
        // dd(11);
        $this->applySingleDiscount($discountType, $request->description);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Discount has been']), $discountType);
    }


    public function canBeRecalculated($invoice)
    {
        $items = $invoice->invoice_items;

        foreach ($items as $item) {
            // Not important for removal
            if ($item->type == 'Adjustment') {
                return false;
            }
        }

        $payments = $invoice->paymentsDetails;

        foreach ($payments as $payment) {
            if ($payment->payment->status == 'Paid') {
                return false;
            }
        }

        return true;
    }


    public function toggleDiscountStatus(Request $request, $pdtId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'contract' => 'required|exists:pocomos_contracts,id',
            'status' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $discountType = PocomosPestDiscountTypeItem::findorfail($pdtId);
        $discountType->active = $request->status;
        $discountType->save();

        $invoiceItems = PocomosInvoiceItems::select('*', 'pocomos_invoice_items.id')
            ->join('pocomos_invoices as pi', 'pocomos_invoice_items.invoice_id', 'pi.id')
            ->join('pocomos_contracts as pc', 'pi.contract_id', 'pc.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
            ->where('pag.office_id', $request->office_id)
            ->where('pc.id', $request->contract)
            ->where('pocomos_invoice_items.description', $discountType->description)
            ->where('pocomos_invoice_items.type', 'Discount')
            ->get();

        if (!$invoiceItems) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Invoice Item.']));
        }

        foreach ($invoiceItems as $item) {
            $invoice = $item->invoice;

            $canBeRecalculated = $this->canBeRecalculated($invoice);
            // dd(11);

            if ($canBeRecalculated) {
                // dd($item);
                $item->active = $request->status;
                $item->save();
                $this->updateInvoiceTaxNew($invoice, false);
            }
        }

        return $this->sendResponse(true, "Discount has been enabled/disabled successfully.", $discountType);
    }


    public function deleteDiscount(Request $request, $pdtId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'contract' => 'required|exists:pocomos_contracts,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $discountType = PocomosPestDiscountTypeItem::findorfail($pdtId);
        $discountType->contract_id = null;
        $discountType->discount_id = null;
        $discountType->active = 0;
        $discountType->save();

        $invoiceItems = PocomosInvoiceItems::select('*', 'pocomos_invoice_items.id')
            ->join('pocomos_invoices as pi', 'pocomos_invoice_items.invoice_id', 'pi.id')
            ->join('pocomos_contracts as pc', 'pi.contract_id', 'pc.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
            ->where('pag.office_id', $request->office_id)
            ->where('pc.id', $request->contract)
            ->where('pocomos_invoice_items.description', $discountType->description)
            ->where('pocomos_invoice_items.type', 'Discount')
            ->get();

        if (!$invoiceItems) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Invoice Item.']));
        }

        foreach ($invoiceItems as $item) {
            $invoice = $item->invoice;

            $canBeRecalculated = $this->canBeRecalculated($invoice);
            // dd(11);

            if ($canBeRecalculated) {
                // dd($item);
                $item->invoice_id = null;
                $item->active = false;
                $item->save();

                $this->updateInvoiceTaxNew($invoice, false);
            }
        }

        return $this->sendResponse(true, "Discount has been removed successfully.");
    }


    /**
     * Create add/ remove credit
     *
     * @param Request $request
     */
    public function createCreditAction(Request $request)
    {
        $v = validator($request->all(), [
            'add_credit' => 'required|boolean',
            'amount' => 'required',
            'description' => 'required',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // $office = $this->getCurrentOffice();
        // $em = $this->getDoctrine()->getManager();

        // $entity = $em->getRepository(Customer::class)->findOneByIdAndOffice($id, $office);
        // if (!$entity) {
        //     throw $this->createNotFoundException('Unable to find the Customer.');
        // }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!($account = $profile->points_account)) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Points Account.']));
        }

        $amount = $request->amount;
        $description = $request->description;
        $addCredit = $request->add_credit;

        try {
            DB::beginTransaction();
            if ($addCredit) {
                $result = $this->addCredit($profile, $amount, null, $description);
            } else {
                $result = $this->removeCredit($profile, $amount, null, $description);
            }
            DB::commit();
            $status = true;
            $message = __('strings.message', ['message' => sprintf('Credit has been %s successfully.', $amount < 0 ? 'reduced' : 'issued')]);
        } catch (\RuntimeException $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }

        return $this->sendResponse($status, $message);
    }

    /**
     * Download a QR report in csv format
     *
     * @param $id
     * @return array
     */
    public function downloadQrReportCsv()
    {
        $customer_id = $_GET['customer_id'];
        $office_id = $_GET['office_id'];
        $customer = $this->findCustomerByIdAndOffice($customer_id, $office_id);
        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Customer.']));
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $customer_id)->first();

        $results = DB::select(DB::raw("SELECT CONCAT(u.first_name, '  ' , u.last_name) as username, pcs.note, pcs.date_created as 'last_scan', pqcg.name as 'group_name'
        FROM pocomos_customer_sales_profiles as pcsp
        JOIN pocomos_qr_code_groups as pqcg on (pqcg.profile_id = pcsp.id)
        JOIN pocomos_qr_code_scan_sessions as pqc on (pqcg.id = pqc.group_id)
        JOIN pocomos_qr_code_scan_session_scans as pcs on (pcs.session_id = pqc.id)
        JOIN pocomos_company_office_users ou on pqc.office_user_id = ou.id
        JOIN orkestra_users u ON u.id = ou.user_id
        where pcsp.customer_id=$customer_id"));

        $csvName = "QR_Code_Report" . date("Ymd") . "_" . str_replace(' ', '_', $profile->customer_details->first_name) . ".csv";

        return Excel::download(new ExportQrReport($results), $csvName);
    }

    /**
     * Renders the form letter modal
     *
     * @param Request $request
     */
    public function sendCustomerFormLetter(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'letter' => 'required|exists:pocomos_form_letters,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'job' => 'nullable',
            'subject' => 'nullable',
            'message' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        PocomosCustomer::findOrFail($request->customer_id);

        $letter = $request->letter;
        $job = $request->job;

        if ($job) {
            $result = $this->sendFormLetterFromJobIds(array($job), $letter, $request->office_id);
        } else {
            $result = $this->sendFormLetterFromCustomers(array($request->customer_id), $letter, $request->office_id);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Letter sent']));
    }


    /**
     * Edit a Customer's service address
     *
     * @param  mixed $request
     * @return void
     */
    public function updateServiceAddress(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id'                 => 'required|exists:pocomos_customers,id',
            'company_name'                => 'nullable',
            'first_name'                  => 'nullable',
            'last_name'                   => 'nullable',
            'contact_address'             => 'required|array',
            'contact_address.street'      => 'required',
            'contact_address.suite'       => 'nullable',
            'contact_address.city'        => 'required',
            'contact_address.region'      => 'required|exists:orkestra_countries_regions,id',
            'contact_address.postal_code' => 'required',
            'contact_address.phone'       => 'required',
            'deliver_email'               => 'required|boolean',
            'email_address'               => 'nullable|email',
            'secondary_email_addresses.*' => 'nullable|email',
            'account_type'                => 'required|in:Residential,Commercial'

        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        $customer->first_name = isset($request->first_name) ? $request->first_name : null;
        $customer->last_name  = isset($request->last_name) ? $request->last_name : null;
        if ($request->email_address && $customer->email !== $request->email_address) {
            $customer->email = $request->email_address ?? null;

            $profile = PocomosCustomerSalesProfile::whereCustomerId($request->customer_id)->first();

            $desc = '';
            if (auth()->user()) {
                $desc .= "<a href='/pocomos-admin/app/employees/users/" . auth()->user()->id . "/show'>" . auth()->user()->full_name . "</a> changed";
            } else {
                $desc .= 'The system changed ';
            }

            if (isset($customer)) {
                $desc .= " the email that belongs to <a href='/pocomos-admin/app/Customers/" . $customer->id . "/service-information'>" . $customer->first_name . " " . $customer->last_name . "</a>.";
            } else {
                $desc .= " a customer\'s email.";
            }

            if (isset($customer->email)) {
                $desc .= " from '" . $customer->email . "' ";
            }

            if (isset($request->email_address)) {
                $desc .= " to '" . $request->email_address . "' ";
            }

            $sql = 'INSERT INTO pocomos_activity_logs
                    (type, office_user_id, customer_sales_profile_id, description, context, date_created)
                    VALUES("Customer Email Changed", ' . auth()->user()->pocomos_company_office_user->id . ',
                        ' . $profile->id . ', "' . $desc . '", "", "' . date('Y-m-d H:i:s') . '")';

            $result = DB::select(DB::raw($sql));
        }
        $customer->company_name     = isset($request->company_name) ? $request->company_name : null;
        $customer->secondary_emails = implode(',', $request->secondary_email_addresses ?? array());
        $customer->account_type     = isset($request->account_type) ? $request->account_type : null;
        $customer->deliver_email    = isset($request->deliver_email) ? $request->deliver_email : null;
        $customer->save();

        $contact_address = PocomosAddress::findOrFail($customer->contact_address_id);
        $contact_phone_id = $contact_address->phone_id ?? null;

        $contact_address->region_id   = $request->contact_address['region'] ?? null;
        $contact_address->street      = $request->contact_address['street'] ?? '';
        $contact_address->suite       = $request->contact_address['suite'] ?? '';
        $contact_address->city        = $request->contact_address['city'] ?? '';
        $contact_address->postal_code = $request->contact_address['postal_code'] ?? '';
        $contact_address->save();

        if ($contact_phone_id) {
            $phone_number = PocomosPhoneNumber::findOrFail($contact_address->phone_id);

            $phone_number->number = $request->contact_address['phone'];
            $phone_number->save();
        }else{
            $phone_number['alias'] = 'Primary';
            $phone_number['number'] = $request->contact_address['phone'];
            $phone_number['type'] = 'Mobile';
            $phone_number['active'] = true;
            $phone_number = PocomosPhoneNumber::create($phone_number);

            $contact_address = PocomosAddress::findOrFail($customer->contact_address_id);
            $contact_address->phone_id  = $phone_number->id;
            $contact_address->save();
        }

        return $this->sendResponse(true, __('strings.update', ['name' => 'Customer information']));
    }

    /**
     * Edit a Customer's billing address
     *
     * @param  mixed $request
     * @return void
     */
    public function updateBillingAddress(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id'                   => 'required|exists:pocomos_customers,id',
            'same_as_service_address'       => 'required|boolean',
            'billing_name'                  => 'required_if:same_as_service_address,==,0|nullable',
            'billing_address'               => 'nullable|array',
            'billing_address.street'        => 'required_if:same_as_service_address,==,0|nullable',
            'billing_address.suite'         => 'required_if:same_as_service_address,==,0|nullable',
            'billing_address.city'          => 'required_if:same_as_service_address,==,0|nullable',
            'billing_address.region'        => 'required_if:same_as_service_address,==,0|nullable|exists:orkestra_countries_regions,id',
            'billing_address.postal_code'   => 'required_if:same_as_service_address,==,0|nullable',
            'billing_address.phone'         => 'required_if:same_as_service_address,==,0|nullable',
            'billing_address.alt_phone'     => 'required_if:same_as_service_address,==,0|nullable',
            'autopay'                       => 'required|boolean',
            'autopay_account'               => 'required_if:autopay,==,1|nullable|exists:orkestra_accounts,id',

        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        $customer->billing_name = $request->billing_name;
        $customer->save();

        // same as service address
        if (!$request->same_as_service_address) {
            $billing_address = PocomosAddress::findOrFail($customer->billing_address_id);

            // Create or update phone number
            if ($billing_address->phone_id) {
                $phone_number = PocomosPhoneNumber::find($billing_address->phone_id);
                if ($phone_number) {
                    $phone_number->number = $request->billing_address['phone'];
                } else {
                    $phone_number = new PocomosPhoneNumber;
                    $phone_number->number = $request->billing_address['phone'];
                    $phone_number->alias  = 'Primary';
                    $phone_number->type   = 'Mobile';
                    $phone_number->active = 1;
                }
                $phone_number->save();
                $billing_address->phone_id = $phone_number->id;
            }

            // Create or update alt phone number
            if ($billing_address->alt_phone_id) {
                $alt_phone_number = PocomosPhoneNumber::find($billing_address->alt_phone_id);
                if ($alt_phone_number) {
                    $alt_phone_number->number = $request->billing_address['alt_phone'];
                } else {
                    $alt_phone_number = new PocomosPhoneNumber;
                    $alt_phone_number->number = $request->billing_address['alt_phone'];
                    $alt_phone_number->alias  = 'Alternate';
                    $alt_phone_number->type   = 'Mobile';
                    $alt_phone_number->active = 1;
                }
                $alt_phone_number->save();
                $billing_address->alt_phone_id = $alt_phone_number->id;
            }

            // update billing address
            $billing_address->region_id    = $request->billing_address['region'];
            $billing_address->street       = $request->billing_address['street'];
            $billing_address->suite        = $request->billing_address['suite'];
            $billing_address->city         = $request->billing_address['city'];
            $billing_address->postal_code  = $request->billing_address['postal_code'];

            $billing_address->save();
        }

        $sale_profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();
        if (isset($request->autopay) && $request->autopay == 1) {
            $sale_profile->autopay = 1;
            $sale_profile->autopay_account_id = $request->autopay_account;
        }
        if ($request->autopay == 0) {
            $sale_profile->autopay = 0;
            $sale_profile->autopay_account_id = null;
        }
        $sale_profile->save();
        return $this->sendResponse(true, __('strings.update', ['name' => 'Billing information']));
    }

    /**
     * Edit a Customer's service address
     *
     * @param  mixed $request
     * @return void
     */
    public function updateServiceInformation(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id'                                       =>      'required|exists:pocomos_customers,id',
            'contract_id'                                       =>      'required|exists:pocomos_contracts,id',
            'office_id'                                         =>      'required|exists:pocomos_company_offices,id',

            'pests'                                             =>      'nullable|array',
            'pests.*'                                           =>      'nullable|exists:pocomos_pests,id',
            'specialty_pests'                                   =>      'nullable|array',
            'specialty_pests.*'                                 =>      'nullable|exists:pocomos_pests,id',
            'tags'                                              =>      'nullable|array',
            'tags.*'                                            =>      'nullable|exists:pocomos_tags,id',
            'county'                                            =>      'nullable|exists:pocomos_counties,id',

            'reschedule_initial'                                =>      'nullable|boolean',
            'reschedule_initial_job'                            =>      'nullable|array',
            'reschedule_initial_job.date_scheduled'             =>      'nullable',
            'reschedule_initial_job.assign_to_route'            =>      'nullable|boolean',
            'reschedule_initial_job.route'                      =>      'nullable',
            'reschedule_initial_job.schedule_specific_time'     =>      'nullable|boolean',
            'reschedule_initial_job.time_scheduled'             =>      'nullable',
            'reschedule_initial_job.apply_to_future'            =>      'nullable|boolean',
            'reschedule_initial_job.same_number_of_days'        =>      'nullable|boolean',

            'reschedule_initial_job.future_options'             =>      'nullable',
            'reschedule_initial_job.offset_future_jobs'         =>      'boolean',
            'reschedule_initial_job.reschedule_future_jobs'     =>      'boolean',
            'reschedule_initial_job.future_jobs'                =>      'array',
            'reschedule_initial_job.future_jobs.future_week'    =>      'nullable',
            'reschedule_initial_job.future_jobs.future_day'     =>      'nullable',
            'reschedule_initial_job.future_jobs.future_time'    =>      'nullable',

            'default_job_duration'                              =>      'nullable',

            'contract'                                          =>      'nullable|array',
            'contract.date_start'                               =>      'nullable',
            'contract.tax_code'                                 =>      'required',
            'contract.purchase_order_number'                    =>      'nullable',
            'contract.sales_status'                             =>      'nullable',
            'contract.sales_person'                             =>      'nullable',
            'contract.auto_renew'                               =>      'nullable',
            'contract.renewal_date'                             =>      'nullable',
            'contract.found_by_type'                            =>      'nullable',

            'map_code'                                          =>      'nullable',
            'technician'                                        =>      'nullable',
            'week_of_the_month'                                 =>      'nullable',
            'day_of_the_week'                                   =>      'nullable',
            'preferred_time'                                    =>      'nullable',
            'reschedule_pending_jobs'                           =>      'nullable|boolean',
            'custom_color'                                      =>      'boolean',
            'contract_color'                                    =>      'nullable',
            'found_by_type'                                     =>      'nullable',
            'number_of_jobs'                                    =>      'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);
        $contract = PocomosContract::with('salespeople.office_user_details.user_details_name')
            ->findOrFail($request->contract_id);

        $currentSalesperson = '';
        if ($contract->salespeople) {
            $currentSalesperson = $contract->salespeople->office_user_details->user_details_name->full_name ?? '';
            // $currentSalesperson = ['salesperson' => $currentSalesperson];
            // $currentSalesperson = "'" . json_encode($currentSalesperson) . "'";
        }

        $pest_contract    = PocomosPestContract::where('contract_id', $request->contract_id)->firstorfail();
        $pestOfficeConfig = PocomosPestOfficeSetting::where('office_id', $request->office_id)->first();
        $sale_profile     = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        $oldDuration      = $customer->default_job_duration;
        $oldCustomColor   = $pest_contract->contract_color;
        $oldTaxCode       = $pest_contract->contract_details->tax_details->code;
        $contract_details = $request->contract;
        $custom_color     = $request->contract_color ?? $oldCustomColor;

        if ($oldCustomColor !== $pest_contract->contract_color) {
            $this->unColorJobsByContract($pest_contract);
        }
        if ($oldDuration !== $request->default_job_duration && $request->default_job_duration !== null) {
            $result = $this->updateDefaultDuration($pest_contract, $request->default_job_duration);
            if ($result['unableToChange'] > 0) {
                throw new \Exception(__('strings.message', ['message' => 'Some job durations were not updated due to schedule overlap.']));
            }
        }
        try {
            if ($request->reschedule_initial) {
                $job = PocomosJob::where('contract_id', $pest_contract->id)->where('technician_id', $request->technician)->first();
                if ($request->reschedule_initial_job && $job) {
                    $this->rescheduleJobWithOptions($request->reschedule_initial_job, $job);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        if ($request->reschedule_pending_jobs && (($pest_contract->day_of_the_week && $pest_contract->week_of_the_month) || $pest_contract->preferred_time)) {

            $jobs = $this->getJobsByContractAndStatus($pest_contract, array(config('constants.PENDING')));
            // $rescheduledJobs = $this->reschedulePendingJobs($jobs);
            // Mark these back as pending
            foreach ($jobs as $job) {
                $job = PocomosJob::findOrFail($job->id);
                $job->status = config('constants.PENDING');
                $job->save();
            }
        }
        if ($request->pests) {
            PocomosPestContractsPest::where('contract_id', $pest_contract->id)->delete();

            foreach ($request->pests as $value) {
                PocomosPestContractsPest::create(['contract_id' => $pest_contract->id, 'pest_id' => $value]);
            }
        }

        if ($request->specialty_pests) {
            PocomosPestContractsSpecialtyPest::where('contract_id', $pest_contract->id)->delete();

            foreach ($request->specialty_pests as $value) {
                PocomosPestContractsSpecialtyPest::create(['contract_id' => $pest_contract->id, 'pest_id' => $value]);
            }
        }

        if ($request->tags) {
            PocomosPestContractsTag::where('contract_id', $pest_contract->id)->delete();

            foreach ($request->tags as $value) {
                PocomosPestContractsTag::create(['contract_id' => $pest_contract->id, 'tag_id' => $value]);
            }
        }

        if ($request->custom_fields) {
            PocomosCustomField::where('pest_control_contract_id', $pest_contract->id)->delete();

            foreach ($request->custom_fields as $key => $value) {
                PocomosCustomField::create(['pest_control_contract_id' => $pest_contract->id, 'custom_field_configuration_id' => $key, 'value' => $value, 'active' => true]);
            }
        }

        $pest_contract->county_id         = $request->county ?? null;
        $pest_contract->technician_id     = $request->technician ?? null;
        $pest_contract->week_of_the_month = $request->week_of_the_month ?? '';
        $pest_contract->day_of_the_week   = $request->day_of_the_week ?? '';
        $pest_contract->preferred_time    = $request->preferred_time ?? null;
        $pest_contract->map_code          = $request->map_code ?? '';
        $pest_contract->number_of_jobs    = $request->number_of_jobs ?? 0;
        $pest_contract->custom_color      = $request->custom_color;
        $pest_contract->contract_color    = $request->contract_color ?? '';

        $contract->tax_code_id           = $contract_details['tax_code'];
        $contract->sales_status_id       = $contract_details['sales_status'] ?? null;
        $contract->salesperson_id        = $contract_details['sales_person'] ?? null;
        $contract->found_by_type_id      = $request->found_by_type ?? null;
        $contract->purchase_order_number = $contract_details['purchase_order_number'] ?? null;
        $contract->auto_renew            = $contract_details['auto_renew'] ?? 0;
        $contract->renewal_date          = $contract_details['renewal_date'] ?? date('Y-m-d');

        $sale_profile->date_signed_up = $contract_details['date_signed_up'] ?? date('Y-m-d');

        $customer->default_job_duration = $request->default_job_duration;

        $contract->save();
        $pest_contract->save();
        $sale_profile->save();
        $customer->save();

        // $profile = PocomosCustomerSalesProfile::whereCustomerId($request->customer_id)->first();
        // return env('APP_URL');
        $desc = '';
        if (auth()->user()) {
            $desc .= "<a href='/pocomos-admin/app/employees/users/" . auth()->user()->id . "/show'>" . auth()->user()->full_name . "</a> ";
        }

        $desc .= 'Updated the customer account';

        if (isset($customer)) {
            $desc .= " associated with <a href='/pocomos-admin/app/Customers/" . $customer->id . "/service-information'>" . $customer->first_name . " " . $customer->last_name . "</a>.";
        } else {
            $desc .= '.';
        }
        // return $currentSalesperson;

        $desc .= "Before the change the salesperson was - " . $currentSalesperson . " - If this has changed please talk to the person who did it.";

        // return $desc;

        $sql = 'INSERT INTO pocomos_activity_logs
                (type, office_user_id, customer_sales_profile_id, description, context, date_created)
                VALUES("Customer Information Changed", ' . auth()->user()->pocomos_company_office_user->id . ',
                    ' . $sale_profile->id . ', "' . $desc . '", "", "' . date('Y-m-d H:i:s') . '")';
        // return $sql;
        $result = DB::select(DB::raw($sql));

        return $this->sendResponse(true, __('strings.update', ['name' => 'Service information']));
    }

    /**
     * Upload Customer's new attachment
     *
     * @param Request $request
     */
    public function customerUploadAttachment(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'file' => 'required',
            'file_description' => 'required',
            'show_to_customer' => 'required|boolean'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office_user = PocomosCompanyOfficeUser::where('office_id', $request->office_id)->first();
        $file = $request->file;

        if ($file) {
            $attachment_id = $this->uploadFileOnS3('Customer', $file, $office_user->user_id);
        }

        PocomosCustomersFile::create(['customer_id' => $request->customer_id, 'file_id' => $attachment_id]);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'The file has been uploaded']));
    }

    /**
     * Updates a Customer's external account it
     *
     * @param Request $request
     */
    public function updateCustomerAccountId(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'external_account_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // $office = $this->getCurrentOffice();
        // $customer = $this->getRepository(Customer::class)->findOneByIdAndOffice($id, $office);

        // if (!$customer) {
        //     throw $this->createNotFoundException('Unable to find the Customer.');
        // }
        $customer = PocomosCustomer::findOrFail($request->customer_id);
        $customer->external_account_id = $request->external_account_id;
        $customer->save();

        return $this->sendResponse(true, __('strings.update', ['name' => 'Customer Account ID']));
    }

    /**
     * Updates a Customer's external account it
     *
     * @param Request $request
     */
    public function checkAccountIdDuplicate(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'external_account_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        $externalAccountId = $request->external_account_id;

        $duplicates = DB::select(DB::raw("SELECT csp.*
        FROM pocomos_customer_sales_profiles AS csp
        JOIN pocomos_customers AS c ON c.id = csp.customer_id
        WHERE csp.office_id = $request->office_id AND c.external_account_id = $externalAccountId AND c.id != $request->customer_id"));

        $data['results'] = (count($duplicates) ? 1 : 0);
        return $this->sendResponse(true, __('strings.details', ['name' => 'Customer external account exist']), $data);
    }

    /**
     * update-contract-first-year-value
     *
     * @param Request $request
     */
    public function updateFirstYearContractValueAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        $pest_contract = PocomosPestContract::where('contract_id', $request->contract_id)->first();
        if (!$pest_contract) {
            return $this->sendResponse(false, 'Unable to find the Contract.');
        }

        $result = $this->updateFirstYearContractValue($request->contract_id, $pest_contract);

        return $this->sendResponse(true, 'Contract First Year Value has been updated.');
    }

    /**
     *  Add customer to Mission export list
     *
     * @param Request $request
     */
    public function addCustomerToExportAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'contract_id' => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $missionConfiguration = PocomosMissionConfig::where('office_id', $request->office_id)->where('active', 1)->where('enabled', 1)->first();

        if (!$missionConfiguration) {
            return $this->sendResponse(false, 'Mission Export is not enabled for this office.');
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        if (!$customer) {
            return $this->sendResponse(false, 'Can not find the customer.');
        }

        $pest_contract = PocomosPestContract::where('contract_id', $request->contract_id)->first();

        $result = $this->queueForExport($request->office_id, $request->customer_id, $pest_contract);

        return $this->sendResponse(true, 'Customer added to the Mission export queue.');
    }

    /**
     *  Queue customer for pestpac export
     *
     * @param Request $request
     */
    public function addCustomerToExport(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'contract_id' => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        if (!$customer) {
            return $this->sendResponse(false, 'Can not find the customer.');
        }

        $pest_contract = PocomosPestContract::where('contract_id', $request->contract_id)->first();

        $result = $this->queueForPPExport($request->office_id, $request->customer_id, $pest_contract);

        return $this->sendResponse(true, 'Customer added to export queue.');
    }

    public function removeChildCustomers($data)
    {
        foreach ($data as $key => $item) {
            if ($item->is_child == true) {
                unset($data[$key]);
            }
        }
        return array_values($data);
    }

    /**
     * Resend email to customer
     *
     * @param Request $request
     */
    public function resendEmail(Request $request)
    {
        $v = validator($request->all(), [
            'type' => 'required',
            'summary' => 'nullable',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'contract_id' => 'nullable|exists:pocomos_contracts,id',
            'invoices' => 'array',
            'invoices.*' => 'exists:pocomos_invoices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);
        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->firstOrFail();
        $office_user = PocomosCompanyOfficeUser::where('office_id', $request->office_id)->firstOrFail();
        $formData = $request->all();

        $this->resendEmails($profile, $office_user, $formData);
        return $this->sendResponse(true, __('strings.message', ['message' => 'The message will be sent shortly. You will be notified when the email is sent.']));
    }

    /**
     * force Contract Renew api
     *
     * @param  mixed $request
     * @return void
     */
    public function forceContractRenew(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id'   =>  'required|exists:pocomos_customers,id',
            'office_id'     =>  'required|exists:pocomos_company_offices,id',
            'contract_id'   =>  'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);
        $contract = PocomosPestContract::where('contract_id', $request->contract_id)->firstOrFail();

        try {
            DB::beginTransaction();
            $this->createJobsForContractRenewal($contract);
            DB::commit();
            $status = true;
            $message = __('strings.sucess', ['name' => 'The Contract Renew']);
        } catch (\Exception $e) {
            Log::info("forceContractRenew input data: " . json_encode($request->all()) . ' Error: ' . $e->getMessage());
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }
        return $this->sendResponse($status, $message);
    }


    /**
     * Creates all the jobs for a renewed Contract
     *
     * @param  mixed $contract
     * @return void
     */
    public function createJobsForContractRenewal($contract)
    {
        $exceptionsArray  = array();
        $exceptions       = unserialize($contract->exceptions);
        if ($exceptions) {
            foreach ($exceptions as $exception) {
                $exceptionsArray[] = date('m', strtotime($exception));
            }
        }

        $salesAgreement = $contract->contract_details->agreement_details;

        $lastJob = $this->getLastJob($contract);
        if (!$lastJob || $lastJob == null) {
            throw new \Exception(__('strings.message', ['message' => 'Job not found.']));
        }

        $dateScheduled = ($lastJob ? new DateTime($lastJob['date_scheduled']) : null);
        if (!$dateScheduled || $dateScheduled == null) {
            throw new \Exception(__('strings.message', ['message' => 'No last job date.']));
        }

        $newRenewStartDate = $contract->contract_details->renew_installment_start_date;

        // TODO: Move this to a separate service
        if ($contract->service_frequency == config('constants.CUSTOM')) {
            // If a Custom serviceSchedule does not fill up it's entire agreement length,
            // it will not renew correctly. This modification adds the remainder of the contract length,
            // adjusting for the number of the months the last job got rescheduled.

            $agreementLength    = $salesAgreement->length;
            $scheduleSum        = array_sum($contract->service_schedule ?: array());

            $rescheduleDiff = $dateScheduled->diff(new DateTime($lastJob['original_date_scheduled']))->m;
            $modification   = $agreementLength - $scheduleSum - $rescheduleDiff;
            $dateScheduled->modify('+' . $modification . ' month');
            $dateScheduled = $dateScheduled->format('Y-m-d');
        }

        $renewalEndDate = new DateTime($dateScheduled);

        if (!$renewalEndDate) {
            return null;
        }
        $renewalEndDate->modify('+' . $salesAgreement->length . ' months');

        $renewalEndDate = $renewalEndDate->format('Y-m-d');
        $generator      = $this->createGenerator($contract, $dateScheduled, $renewalEndDate);
        $newStartDate   = $dateScheduled;

        if ($newRenewStartDate != null) {
            $newRenewDate = $newRenewStartDate;
            $newRenewDate->modify('+1 year');
        }

        if ($exceptions) {
            $jobCount = 0;
            foreach ($generator as $dateScheduled) {
                $current_date = new \DateTime();

                if ($dateScheduled < $current_date->format('Y-m-d')) {
                    continue;
                }
                $jobCount++;
                if (!in_array(date('m', strtotime($dateScheduled)), $exceptionsArray)) {
                    $this->createContractJob($contract, config('constants.REGULAR'), null, $dateScheduled, $contract->preferred_time);
                }
            }
        } else {
            $jobCount = 0;
            foreach ($generator as $dateScheduled) {
                $current_date = new \DateTime();

                if ($dateScheduled < $current_date->format('Y-m-d')) {
                    continue;
                }
                $jobCount++;
                $this->createContractJob($contract, config('constants.REGULAR'), null, $dateScheduled, $contract->preferred_time);
            }
        }

        if ($jobCount === 0 && ($renewalEndDate < date('Y-m-d h:i:s'))) {
            $contract->contract_details->auto_renew = false;
            $contract->contract_details->renewal_disabled = date('Y-m-d h:i:s');
            $contract->contract_details->save();
        }

        $contract->date_renewal_end = $renewalEndDate;
        $contract->save();

        $renewableFrequencies = ['Per service'];
        if (in_array($contract->contract_details->billing_frequency, $renewableFrequencies)) {
            $this->handleDiscountTypeForJobInvoiceForceContractRenew($contract);
        }

        $renewableFrequencies = [config('constants.INITIAL_MONTHLY'), config('constants.MONTHLY')];
        if (in_array($contract->contract_details->billing_frequency, $renewableFrequencies)) {
            /* foreach ($renewableFrequencies as $val) {
                $generator = $this->createGenerator($contract, $newStartDate, $renewalEndDate, $val);
                foreach ($generator as $dateScheduled) {
                    $current_date = new \DateTime();
                    if ($dateScheduled < $current_date->format('Y-m-d')) {
                        continue;
                    }
                    if (!in_array(date('m', strtotime($dateScheduled)), $exceptionsArray)) {
                        $this->createContractJob($contract, config('constants.REGULAR'), null, $dateScheduled, $contract->preferred_time);
                    }
                }
            } */
            $this->handleDiscountTypeForMiscInvoiceForceContractRenew($contract);
        }

        $renewableFrequencies = ['Installments'];
        if ($contract->auto_renew_installments == true) {
            if (in_array($contract->contract_details->billing_frequency, $renewableFrequencies)) {
                $this->handleDiscountTypeForMiscInvoiceForceContractRenew($contract);
                if ($newRenewDate != null) {
                    $contract->contract_details->update(['renew_installment_start_date' => $newRenewDate]);
                }
            }
        }
    }


    /**
     * Enqueues reporting jobs for the customer
     *
     * @param  mixed $request
     * @return void
     */
    public function forceStateUpdate(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id'   =>  'required|exists:pocomos_customers,id',
            'office_id'     =>  'required|exists:pocomos_company_offices,id',
            'contract_id'   =>  'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = $this->findCustomerByIdAndOffice($request->customer_id, $request->office_id);
        if (!$customer) {
            throw new \Exception('Unable to find the Customer.');
        }

        $args['ids'] = array($customer->id);
        CustomerStateJob::dispatch($args);

        $sales_profile      = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->firstOrFail();
        $contracts          = PocomosContract::where('profile_id', $sales_profile->id)->pluck('id')->toArray();
        $pest_contract_ids  = PocomosPestContract::whereIn('contract_id', $contracts)->pluck('id')->toArray();

        $args['ids'] = $pest_contract_ids;
        SearchStateJob::dispatch($args);

        $args['ids'] = $contracts;
        ContractStateJob::dispatch($args);

        return $this->sendResponse(true, __('strings.message', ['message' => 'The jobs have been enqueued']));
    }

    public function showAgreementDetails(Request $request)
    {
        $v = validator($request->all(), [
            'pest_agreement_id' => 'required|exists:pocomos_pest_agreements,id',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        /** @var PocomosPestAgreement $entity */
        $entity = $this->findAgreementByIdAndOffice($request->pest_agreement_id, $request->office_id);
        $pestOfficeConfig = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();
        //$currentAgreement = $entity->id;

        $agreement = $entity->agreement_detail;

        $returnTypes = [];
        $serviceTypes = PocomosPestContractServiceType::where('office_id', $request->office_id)->get();

        foreach ($serviceTypes as $serviceType) {
            $returnTypes[$serviceType->id] = $serviceType->name;
        }
        $defaultServiceTypeId = 0;
        $defaultServiceType =  $agreement->service_type_detail;
        if ($defaultServiceType) {
            $defaultServiceTypeId =  $defaultServiceType->id;
        } else {
            $defaultServiceTypeId =  array_keys($returnTypes)[0];
        }
        if (!$entity) {
            throw new \Exception('Unable to find the Agreement.');
        }

        $isSerializedValid = @unserialize($entity->exceptions);
        $exceptions_months = array();
        if ($isSerializedValid) {
            $exceptions_months = unserialize($entity->exceptions);
        }

        $res = array(
            "id" => $entity->id ?? null,
            "name" => $entity->agreement_detail->name ?? null,
            "description" => $entity->agreement_detail->description ?? null,
            "one_month_followup" => $entity->one_month_followup ?? null,
            "billing_frequencies" => unserialize($entity->agreement_detail->billing_frequencies) ?? null,
            "service_frequencies" => unserialize($entity->service_frequencies) ?? null,
            "variable_length" => $entity->agreement_detail->variable_length ?? null,
            "length" => $entity->agreement_detail->length ?? null,
            "bill_immediately" => $entity->agreement_detail->bill_immediately ?? null,
            "delay_welcome_email" => $entity->delay_welcome_email ?? null,
            "max_jobs" => $entity->max_jobs ?? null,
            "agreement_body" => $entity->agreement_detail->agreement_body ?? null,
            "invoice_intro" => $entity->agreement_detail->invoice_intro ?? null,
            "signature_terms" => $entity->agreement_detail->signature_agreement_text ?? null,
            "auto_renew" => $entity->agreement_detail->auto_renew ?? null,
            "auto_renew_lock" => $entity->agreement_detail->auto_renew_lock ?? null,
            "auto_renew_initial" => $entity->agreement_detail->auto_renew_initial ?? null,
            "auto_renew_initial_job_lock" => $entity->agreement_detail->initial_job_lock ?? null,
            "custom_agreement" => ($entity->agreement_detail->custom_agreement_template == null ? 0 : 1),
            "regular_initial_price" => $entity->agreement_detail->regular_initial_price ?? null,
            "initial_price" => $entity->agreement_detail->initial_price ?? null,
            "recurring_price" => $entity->agreement_detail->recurring_price ?? null,
            "enable_default_price" => $entity->agreement_detail->enable_default_price ?? null,
            "allow_addendum" => $entity->allow_addendum ?? null,
            "allow_dates_in_the_past" => $entity->allow_dates_in_the_past ?? null,
            "default_service_type" => $defaultServiceType ?? null,
            "default_job_duration" => $entity->regular_duration ?? null,
            "default_initial_job_duration" => $entity->initial_duration ?? null,
            "default_office_job_duration" => $pestOfficeConfig->regular_duration ?? null,
            "default_office_initial_job_duration" => $pestOfficeConfig->initial_duration ?? null,
            "schedule_duration_option" => $pestOfficeConfig->show_service_duration_option_agreement,
            "exceptions_months" => array_values($exceptions_months),
            "specify_number_of_jobs" => $entity->agreement_detail->specifyNumberOfJobs,
            "enable_billing_frequencies" => $entity->agreement_detail->enableBillingFrequencies
        );

        return $this->sendResponse(true, __('strings.details', ['name' => 'Agreement']), $res);
    }

    public function createNewContract(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_type_id' => 'nullable|exists:pocomos_pest_agreements,id',
            'service_type_id' => 'nullable|exists:pocomos_pest_contract_service_types,id',
            'service_frequency' => 'nullable',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
            'billing_frequency' => 'nullable',
            'two_payments_days_limit' => 'nullable',
            'regular_initial_price' => 'nullable',
            'initial_discount' => 'nullable',
            'initial_price' => 'nullable',
            'installment_start_date' => 'nullable',
            'installment_end_date' => 'nullable',
            'number_of_payments' => 'nullable',
            'installment_frequency' => 'nullable',
            'recurring_price' => 'nullable',
            'discount' => 'nullable',
            'initial_date' => 'nullable',
            'technician' => 'nullable',
            'time_slot' => 'nullable',
            'initial_job_duration' => 'nullable',
            'recurring_job_duration' => 'nullable',
            'tax_code' => 'nullable',
            'map_code' => 'nullable',
            'county' => 'nullable',
            'custom_fields' => 'nullable',
            'auto_renew' => 'nullable',
            'renew_initial_job' => 'nullable',
            'make_tech_preferred' => 'nullable',
            'specific_recurring_schedule' => 'nullable',
            'recurring_week' => 'nullable',
            'recurring_day' => 'nullable',
            'recurring_time' => 'nullable',
            'pests' => 'nullable|array',
            'specialty' => 'nullable|array',
            'tags' => 'nullable|array',
            'salesperson' => 'nullable',
            'found_by_type' => 'nullable',
            'sales_status' => 'nullable',
            'job_note' => 'nullable',
            'addendum' => 'nullable',
            'agreement' => 'nullable',
            'signature_data' => 'nullable',
            'exceptions' => 'nullable',
            'service_schedule' => 'nullable',
            'number_of_jobs' => 'nullable',
            'auto_renew_installments' => 'nullable|boolean',
            'renew_installment_initial_price' => 'nullable',
            'renew_installment_start_date' => 'nullable',
            'renew_number_of_payment' => 'nullable',
            'renew_installment_frequency' => 'nullable',
            'renew_installment_price' => 'nullable',
            'pest_discount_types' => 'nullable|array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        DB::beginTransaction();
        $res = array();
        try {
            $input_detail = $request->all();
            $customer = PocomosCustomer::findOrFail($input_detail['customer_id']);
            $tax_code = PocomosTaxCode::findOrFail($input_detail['tax_code']);
            $sales_profile_data = PocomosCustomerSalesProfile::where('customer_id', $input_detail['customer_id'])->firstOrFail();
            $pest_agr =  PocomosPestAgreement::whereId($input_detail['contract_type_id'])->first();
            $custom_fields = $input_detail['custom_fields'] ?? array();
            $tags = $input_detail['tags'] ?? array();
            $targeted_pests = $input_detail['pests'] ?? array();
            $specialty_pests = $input_detail['specialty'] ?? array();
            $pest_discount_types = $request->pest_discount_types ?? array();

            if ($pest_agr) {
                $pest_agreement_id = $pest_agr->id ?? null;
            } else {
                $pest_agreement['agreement_id'] = $pest_agr->agreement_id ?? null;
                $pest_agreement['service_frequencies'] = serialize($input_detail['service_frequency']) ?? null;
                $pest_agreement['exceptions'] = serialize($input_detail['exceptions']) ?? null;
                $pest_agreement['active'] = true;
                $pest_agreement['initial_duration'] = $input_detail['initial_job_duration'];
                $pest_agreement['regular_duration'] = $input_detail['initial_job_duration'];
                $pest_agreement['one_month_followup'] = false;
                $pest_agreement['default_agreement'] = false;
                $agreement = PocomosPestAgreement::create($pest_agreement);
                $pest_agreement_id = $agreement->id ?? null;
            }

            if ($input_detail['signature_data']) {
                $agreement_signature = $input_detail['signature_data'];
            } else {
                $agreement_signature = null;
            }
            $signed = false;
            $agreement_sign = null;
            if ($agreement_signature) {
                //store file into document folder
                $agreement_sign_detail['path'] = $agreement_signature->store('public/files');

                // $agreement_sign_detail['user_id'] = $or_user->id ?? null;
                //store your file into database
                $agreement_sign_detail['filename'] = $agreement_signature->getClientOriginalName();
                $agreement_sign_detail['mime_type'] = $agreement_signature->getMimeType();
                $agreement_sign_detail['file_size'] = $agreement_signature->getSize();
                $agreement_sign_detail['active'] = 1;
                $agreement_sign_detail['md5_hash'] =  md5_file($agreement_signature->getRealPath());
                $agreement_sign =  OrkestraFile::create($agreement_sign_detail);
                $signed = true;
            }

            $autopay_signature = $input_detail['signature_data'] ?? null;
            if ($autopay_signature) {
                //store file into document folder
                $autopay_sig_detail['path'] = $autopay_signature->store('public/files');

                // $autopay_sig_detail['user_id'] = $or_user->id ?? null;
                //store your file into database
                $autopay_sig_detail['filename'] = $autopay_signature->getClientOriginalName();
                $autopay_sig_detail['mime_type'] = $autopay_signature->getMimeType();
                $autopay_sig_detail['file_size'] = $autopay_signature->getSize();
                $autopay_sig_detail['active'] = 1;
                $autopay_sig_detail['md5_hash'] =  md5_file($autopay_signature->getRealPath());
                // $autopay_sign =  OrkestraFile::create($autopay_sig_detail);
            }

            $pocomos_contract['profile_id'] = $sales_profile_data->id;
            $pocomos_contract['agreement_id'] = $pest_agr->agreement_id ?? null;
            $pocomos_contract['signature_id'] = $agreement_sign->id ?? null;
            $pocomos_contract['billing_frequency'] = '';
            $pocomos_contract['status'] = 'Active';
            $pocomos_contract['date_start'] = $input_detail['start_date'];
            $pocomos_contract['date_end'] = $input_detail['end_date'];
            $pocomos_contract['active'] = true;
            $pocomos_contract['salesperson_id'] = $input_detail['salesperson'] ?? null;
            $pocomos_contract['auto_renew'] = $input_detail['auto_renew_installments'] ?? false;
            $pocomos_contract['tax_code_id'] = $input_detail['tax_code'] ?? null;
            $pocomos_contract['signed'] = $signed;
            $pocomos_contract['autopay_signature_id'] = $agreement_sign->id ?? null;
            $pocomos_contract['sales_tax'] = $tax_code['tax_rate'] ?? 0.0;
            $pocomos_contract['sales_status_id'] = $input_detail['sales_status'] ?? null;
            $pocomos_contract['found_by_type_id'] = $input_detail['found_by_type'] ?? null;
            // $pocomos_contract['auto_renew'] = $input_detail['auto_renew_installments'] ?? null;
            $pocomos_contract['renew_installment_initial_price'] = $input_detail['renew_installment_initial_price'] ?? 0.0;
            $pocomos_contract['renew_installment_start_date '] = $input_detail['renew_installment_start_date'] ?? null;
            $pocomos_contract['renew_number_of_payment'] = $input_detail['renew_number_of_payment'] ?? 1;
            $pocomos_contract['renew_installment_frequency'] = $input_detail['renew_installment_frequency'] ?? null;
            $pocomos_contract['renew_installment_price'] = $input_detail['renew_installment_price'] ?? 0.0;
            $cus_contract = PocomosContract::create($pocomos_contract);

            if ($agreement_sign) {
                $files_input = [
                    [
                        'customer_id' => $customer->id,
                        'file_id' => $agreement_sign->id ?? null
                    ]
                ];
                PocomosCustomersFile::insert($files_input);
            }

            $pest_contract['contract_id'] = $cus_contract->id;
            $pest_contract['agreement_id'] = $pest_agreement_id;
            $pest_contract['service_frequency'] = $input_detail['service_frequency'] ?? '';
            $pest_contract['exceptions'] = serialize($input_detail['exceptions']) ?? null;
            $pest_contract['initial_price'] = $input_detail['initial_price'] ?? 0;
            $pest_contract['recurring_price'] = $input_detail['recurring_price'] ?? 0;
            $pest_contract['initial_discount'] = $input_detail['initial_discount'] ?? 0;
            $pest_contract['regular_initial_price'] = $input_detail['regular_initial_price'] ?? 0;

            $amount = 0;
            $original_value = 0;
            $modifiable_original_value = 0;
            $first_year_contract_value = 0;

            if (isset($input_detail['billing_frequency']) && in_array($input_detail['billing_frequency'], ['Monthly'])) {
                $original_value = $input_detail['recurring_price'] ?? 0;
                $modifiable_original_value = $input_detail['recurring_price'] ?? 0;
                $first_year_contract_value = $input_detail['recurring_price'] ?? 0;
                $amount = $input_detail['recurring_price'] ?? 0;
            } elseif (isset($input_detail['billing_frequency']) &&  in_array($input_detail['billing_frequency'], ['Initial monthly', 'Due at signup'])) {
                $original_value = $input_detail['initial_price'] ?? 0;
                $modifiable_original_value = $input_detail['initial_price'] ?? 0;
                $first_year_contract_value = $input_detail['initial_price'] ?? 0;
                $amount = $input_detail['initial_price'] ?? 0;
            } elseif (isset($input_detail['billing_frequency']) &&  in_array($input_detail['billing_frequency'], ['Two payments'])) {
                $original_value = $input_detail['initial_price'] + $input_detail['recurring_price'];
                $modifiable_original_value = $input_detail['initial_price'] + $input_detail['recurring_price'];
                $first_year_contract_value = $input_detail['initial_price'] + $input_detail['recurring_price'];
                $amount = $original_value;
            } elseif (isset($input_detail['billing_frequency']) &&  in_array($input_detail['billing_frequency'], ['Installments'])) {
                $original_value = $input_detail['initial_price'];
                $modifiable_original_value = $input_detail['initial_price'];
                $first_year_contract_value = $input_detail['initial_price'];
                $pest_contract['installment_frequency'] = $input_detail['installment_frequency'];
                $pest_contract['installment_start_date'] = $input_detail['installment_start_date'];
                $pest_contract['installment_end_date'] = $input_detail['installment_end_date'];
                $amount = $input_detail['initial_price'] ?? 0;
            }

            $pest_contract['original_value'] = $original_value;
            $pest_contract['auto_renew_installments'] = $input_detail['auto_renew_installments'] ?? false;
            $pest_contract['modifiable_original_value'] = $modifiable_original_value;
            $pest_contract['first_year_contract_value'] = $first_year_contract_value;

            $pest_contract['active'] = true;
            $pest_contract['service_type_id'] = $input_detail['service_type_id'];
            $pest_contract['service_schedule'] = '';
            $pest_contract['week_of_the_month'] = $input_detail['recurring_week'] ?? null;
            $pest_contract['day_of_the_week'] = $input_detail['recurring_day'] ?? null;
            $pest_contract['date_renewal_end'] = date('Y-m-d', strtotime('+2 year'));
            $pest_contract['preferred_time'] = $input_detail['recurring_time'] ?? null;
            $pest_contract['county_id'] = $service_address['county'] ?? null;
            $pest_contract['technician_id'] = $input_detail['technician'];
            // $pest_contract['renew_initial_job'] = '';
            $pest_contract['number_of_jobs'] = $input_detail['number_of_jobs'] ?? null;
            $pest_contract['original_value'] = 0.0;
            $pest_contract['modifiable_original_value'] = 0.0;
            $pest_contract['map_code'] = $input_detail['map_code'] ?? '';
            $pest_contract['addendum'] = $input_detail['addendum'] ?? null;

            $pest_contract_res = PocomosPestContract::create($pest_contract);

            foreach ($custom_fields as $key => $value) {
                PocomosCustomField::create(['pest_control_contract_id' => $pest_contract_res->id, 'custom_field_configuration_id' => $key, 'value' => $value, 'active' => true]);
            }

            foreach ($tags as $value) {
                PocomosPestContractsTag::create(['contract_id' => $pest_contract_res->id, 'tag_id' => $value]);
            }

            // $note_detail['user_id'] = $or_user->id ?? null;
            $initial_job_note_data['summary'] = $input_detail['job_note'] ?? '';
            $initial_job_note_data['interaction_type'] = 'Other';
            $initial_job_note_data['active'] = true;
            $initial_job_note_data['body'] = '';
            $initial_job_note = PocomosNote::create($initial_job_note_data);

            $permanent_job_note_data['summary'] = $input_detail['job_note'] ?? '';
            $permanent_job_note_data['interaction_type'] = 'Other';
            $permanent_job_note_data['active'] = true;
            $permanent_job_note_data['body'] = '';
            $permanent_job_note = PocomosNote::create($permanent_job_note_data);

            PocomosCustomersNote::create(['customer_id' => $customer->id, 'note_id' => $initial_job_note->id]);
            PocomosCustomersNote::create(['customer_id' => $customer->id, 'note_id' => $permanent_job_note->id]);

            foreach ($targeted_pests as $value) {
                PocomosPestContractsPest::create(['contract_id' => $pest_contract_res->id, 'pest_id' => $value]);
            }

            foreach ($specialty_pests as $value) {
                PocomosPestContractsPest::create(['contract_id' => $pest_contract_res->id, 'pest_id' => $value]);
            }

            $service_schedule = $input_detail['service_schedule'] ?? array();
            $i = 0;
            foreach ($service_schedule as $schedule) {
                // create invoice
                $invoice_input['contract_id'] = $cus_contract->id;
                $invoice_input['date_due'] = date('Y-m-d', strtotime($schedule));
                $invoice_input['amount_due'] = $amount;
                $invoice_input['status'] = 'Not sent';
                $invoice_input['balance'] = 0.00;
                $invoice_input['active'] = true;
                $invoice_input['sales_tax'] = $tax_code['tax_rate'] ?? 0.0;
                $invoice_input['tax_code_id'] = $input_detail['tax_code'] ?? null;
                $invoice_input['closed'] = false;
                $pocomos_invoice = PocomosInvoice::create($invoice_input);

                $invoice_items['description'] = 'Description';
                $invoice_items['price'] = $amount;
                $invoice_items['invoice_id'] = $pocomos_invoice->id;
                $invoice_items['active'] = true;
                $invoice_items['sales_tax'] = $tax_code['tax_code'] ?? 0.0;
                $invoice_items['tax_code_id'] = $input_detail['tax_code'] ?? null;
                $invoice_items['type'] = '';
                PocomosInvoiceItems::create($invoice_items);

                PocomosPestContractsInvoice::create(['pest_contract_id' => $pest_contract_res->id, 'invoice_id' => $pocomos_invoice->id]);

                $input = [];
                $input['contract_id'] = $pest_contract_res->id;
                $input['invoice_id'] = $pocomos_invoice->id;
                $input['date_scheduled'] = date('Y-m-d', strtotime($schedule));
                $input['type'] = $request->type;
                $input['status'] = 'Pending';
                $input['active'] = true;
                if ($i == 0) {
                    $input['type'] = 'Initial';
                } else {
                    $input['type'] = 'Regular';
                }
                $input['original_date_scheduled'] = date('Y-m-d', strtotime($schedule));
                $input['note'] = $request->permanent_notes ?? '';
                $input['color'] = '';
                $input['commission_type'] = 'None';
                $input['commission_value'] = 0;
                $input['commission_edited'] = 0;
                $input['technician_note'] = '';
                $input['weather'] = '';
                $input['treatmentNote'] = '';
                $input['technician_id'] = $input_detail['technician'] ?? '';
                PocomosJob::create($input);

                $i = $i + 1;
            }

            foreach ($pest_discount_types as $discountVal) {
                PocomosPestDiscountTypeItem::create([
                    'discount_id' => $discountVal['discount_type_id'],
                    'rate'        => 0.00,
                    'amount' => $discountVal['amount'],
                    'type' => 'static',
                    'description' => $discountVal['description'],
                    'contract_id' => $cus_contract->id,
                ]);
            }

            DB::commit();
            $status = true;
            $message = __('strings.create', ['name' => 'New Contract']);
            $res['contract_id'] = $cus_contract->id;
        } catch (\Exception $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }
        return $this->sendResponse($status, $message, $res);
    }

    public function customerAdvanceSearchFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;

        $agreements = PocomosAgreement::whereOfficeId($request->office_id)->whereActive(true)->get(['id', 'name']);

        // for preferred techs also
        $technicians = PocomosTechnician::select('*', 'pocomos_technicians.id')
            ->join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
            ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->where('pcou.office_id', $request->office_id)
            ->where('pcou.active', 1)
            ->where('pocomos_technicians.active', 1)
            ->where('ou.active', 1)
            ->orderBy('ou.first_name')
            ->orderBy('ou.last_name')
            ->get();

        $salesPeople = PocomosSalesPeople::select('*', 'pocomos_salespeople.id')
            ->join('pocomos_company_office_users as pcou', 'pocomos_salespeople.user_id', 'pcou.id')
            ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->where('pcou.active', 1)
            ->where('pocomos_salespeople.active', 1)
            ->where('ou.active', 1)
            ->where('pcou.office_id', $officeId)
            ->orderBy('ou.first_name')
            ->orderBy('ou.last_name')
            ->get();

        // $tags = PocomosTag::whereOfficeId($officeId)->whereActive(1)->get();

        // $customFields = PocomosPestOfficeSetting::with('custom_field_configurations')->whereOfficeId($officeId)->get();

        return $this->sendResponse(true, 'Customer Advance Search filters', [
            'agreements'   => $agreements,
            'technicians'   => $technicians,
            'salespeople'   => $salesPeople,
            // 'custom_fields'   => $customFields,
            // 'office_address' => $officeAddress,
        ]);
    }

    /**
     * List all Customer details from an Advanced Search.
     *
     * @param CustomerAdvanceRequest $request
     * @return array
     */
    public function customerAdvanceSearch(CustomerAdvanceRequest $request)
    {
        $officeExist = PocomosCompanyOffice::findOrFail($request->office_id);
        // $pestOfficeConfig = PocomosPestOfficeSetting::whereOfficeId($request->office_id)->firstOrFail();
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->firstOrFail();
        $inputs = $request->all();
        $user = array(); //AS NOW REMAIN FOR COMPLETE LOGIN FUNCTIONALITY

        if ($request->all_branches) {
            $office = array();
            if ($this->isGranted(array('ROLE_ADMIN', 'ROLE_OWNER', 'ROLE_SALES_ADMIN'))) {
                $childOffices = PocomosCompanyOffice::where('parent_id', $request->office_id)->pluck('id')->toArray();
                if (count($childOffices)) {
                    $office = $childOffices;
                    $office[] = (int)$request->office_id;
                } else {
                    $childOffices = PocomosCompanyOffice::findOrFail($request->office_id);
                    $office = PocomosCompanyOffice::where('parent_id', $childOffices->parent_id)->pluck('id')->toArray();
                }
            } else {
                if (auth()->user()) {
                    $userOffices = PocomosCompanyOfficeUser::where('user_id', auth()->user()->id)->pluck('office_id')->toArray();

                    foreach ($userOffices as $userOffice) {
                        $childOffices = PocomosCompanyOffice::where('parent_id', $userOffice)->pluck('id')->toArray();
                        $officeRes = array();
                        if (count($childOffices)) {
                            $officeRes = $childOffices;
                            $office[] = $userOffice;
                        } else {
                            $childOffices = PocomosCompanyOffice::findOrFail($userOffice);
                            if ($childOffices->parent_id) {
                                $officeRes = PocomosCompanyOffice::where('parent_id', $childOffices->parent_id)->pluck('id')->toArray();
                            } else {
                                $office[] = $userOffice;
                            }
                            $office[] = (int)$request->office_id;
                        }
                        $office = array_merge($office, $officeRes);
                    }
                } else {
                    $office[] = (int)$request->office_id;
                }
            }
            $office = array_unique($office);
        } else {
            $office = array($officeExist->id);
        }
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];

        $dataTmp = $this->getSearchResults($office, $user, $inputs, $page, $perPage, $request->search);
        $data = $dataTmp['res'];

        foreach ($data as $value) {
            $value->is_parent = $this->is_cutomer_parent($value->id);
            $value->is_child = $this->is_cutomer_child($value->id);
            $value->multiple_contracts = $this->is_cutomer_multiple_contracts($value->id);
            $value->commercial_account = $value->account_type == config('constants.COMMERCIAL') ? true : false;
        }
        // $data = $this->removeChildCustomers($data);
        $data = [
            'customers' => $data,
            'customer_ids' => $dataTmp['customer_ids'],
            'count' => $dataTmp['count']
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Customers']), $data);
    }

    /**
     * Get details for Map of customers based on zip code provided.
     *
     * @param Request $request
     * @return array
     */
    public function mapDetails(Request $request)
    {
        $v = validator($request->all(), [
            'zip' => 'nullable',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'user_id' => 'nullable|exists:orkestra_users,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $group_by_query = '';
        $select_query = 'SELECT pc.*, pcad.latitude, pcad.longitude, pcad.street, pcad.city, pcad.suite FROM pocomos_customers as pc ';
        $join_query = "JOIN pocomos_customer_sales_profiles as pcsp ON pc.id = pcsp.customer_id JOIN pocomos_addresses as pcad ON pc.contact_address_id = pcad.id";
        $where_query = " WHERE pc.status = '" . config('constants.ACTIVE') . "' AND pcad.latitude IS NOT NULL AND pcad.longitude IS NOT NULL AND pcsp.office_id = $request->office_id";

        if ($request->zip) {
            $where_query .= " AND pcad.postal_code = $request->zip";
        }

        //CONSTANTS BASE MANAGE IS TEMPRORY BECAUSE NOW NOT IMPLETEMENTED LOGIN WILL UPDATE IN FEATURE ONCE LOGIN WILL DONE
        if (config('constants.ROLE_TECH_RESTRICTED')) {
            $join_query .= " JOIN pocomos_contracts as pcd ON pcsp.id = pcd.profile_id JOIN pocomos_pest_contracts as ppc ON pcd.id = ppc.contract_id JOIN pocomos_technicians as pt ON ppc.technician_id = pt.id JOIN pocomos_company_office_users as pcou ON pt.user_id = pcou.id JOIN orkestra_users as ou ON pcou.user_id = ou.id";

            $where_query .= " AND pcad.postal_code = $request->zip";
            $group_by_query .= " GROUP BY pc.id";
            if ($request->user_id) {
                $where_query .= " AND ou.id = $request->user_id";
            }
        }
        $merged_query = $select_query . '' . $join_query . '' . $where_query . '' . $group_by_query;
        $res = DB::select(DB::raw($merged_query));

        return $this->sendResponse(true, __('strings.details', ['name' => 'Customers Map']), $res);
    }

    public function saveDefaultContract(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'customer_id' => 'nullable|exists:pocomos_customers,id',
            'contract_id' => 'nullable|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        Session::start();
        $office_id = $request->office_id;
        $customer = PocomosCustomer::findOrFail($request->customer_id);
        $contract = PocomosContract::findOrFail($request->contract_id);

        if ($contract !== null) {
            $data[config('constants.ACTIVE_CONTEXT_KEY')]["customer"] = $request->customer_id;
            $data[config('constants.ACTIVE_CONTEXT_KEY')]["contract"] = $request->contract_id;
        } else {
            $data[config('constants.ACTIVE_CONTEXT_KEY')]["customer"] = null;
            $data[config('constants.ACTIVE_CONTEXT_KEY')]["contract"] = null;
        }
        // $request->session()->put($data);
        // $request->session()->save();
        Session::put($data);
        Session::save();
        // Session::push('test_key', $data);
        // session(['test_key' => 'test value']);

        // Session::put('test_key', 'test value');
        // Session::save();
        return (Session::all());
        // dd(Session::get(config('constants.ACTIVE_CONTEXT_KEY')));
        return $this->sendResponse(true, __('strings.save', ['name' => 'Contract Sessions']));
    }


    /**
     * Enqueues reporting jobs for the customer
     *
     * @param  mixed $request
     * @return void
     */
    public function forceAutoPay(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id'   => 'required|exists:pocomos_customers,id',
            'office_id'     => 'required|exists:pocomos_company_offices,id',
            'contract_id'   => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = $this->findCustomerByIdAndOffice($request->customer_id, $request->office_id);
        if (!$customer) {
            throw new \Exception('Unable to find the Customer.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->firstOrFail();

        foreach ($profile->contract_details as $contract) {
            try {
                $this->doHandleContract($contract);
            } catch (\Exception $e) {
                $this->logDiagnostic($e, $contract);
            }
        }

        return $this->sendResponse(true, __('strings.processed', ['name' => 'Force Auto Pay']));
    }

    /**
     * Find duplicate custopmers details with filters
     */
    public function findDuplicateCustomers(Request $request)
    {
        $v = validator($request->all(), [
            'email' => 'nullable',
            'first_name' => 'nullable',
            'last_name' => 'nullable',
            'phone' => 'nullable',
            'office_id' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $email = $request->email;
        $first_name = $request->first_name;
        $last_name = $request->last_name;
        $phone = $request->phone;
        $office_id = $request->office_id;

        $select_sql = 'SELECT cu.id, CONCAT(cu.first_name, \' \', cu.last_name) As name, cu.email, pn.number
        FROM pocomos_customers AS cu
        JOIN pocomos_customer_sales_profiles AS csp ON cu.id = csp.customer_id
        LEFT JOIN pocomos_customers_phones AS cph ON csp.id = cph.profile_id
        JOIN pocomos_phone_numbers AS pn ON cph.phone_id = pn.id';

        $where_sql = ' WHERE csp.office_id = ' . $office_id . ' AND cu.active = 1 AND csp.active = 1 ';

        $where_sql_arr = array();

        if (strlen($email) > 7) {
            $where_sql_arr[] = ' cu.email LIKE "%' . $email . '%"';
        }

        if (strlen($first_name) > 3) {
            $where_sql_arr[] = ' cu.first_name LIKE "%' . $first_name . '%"';
        }

        if (strlen($last_name) > 3) {
            $where_sql_arr[] = ' cu.last_name LIKE "%' . $last_name . '%"';
        }

        if (strlen($phone) > 5) {
            $where_sql_arr[] = ' pn.number LIKE "%' . $phone . '%"';
        }

        if (count($where_sql_arr)) {
            $where_sql .= ' AND (' . implode(' OR ', $where_sql_arr) . ') ';
        }

        $limit_sql = ' GROUP BY cu.id LIMIT 5;';

        $merge_sql = $select_sql . '' . $where_sql . '' . $limit_sql;

        $res = DB::select(DB::raw($merge_sql));
        return $this->sendResponse(true, __('strings.list', ['name' => 'Duplicate Customers']), $res);
    }

    /**
     * Show welcome letter details
     */
    public function showWelcomeLetterDetails(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = $this->findCustomerByIdAndOffice($request->customer_id, $request->office_id);
        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Customer.']));
        }

        $pestContract = $this->findContractByCustomer($request->contract_id, $request->customer_id);
        if (!$pestContract) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate Pest ControlContract.']));
        }

        $office = PocomosCompanyOffice::findOrFail($request->office_id);
        $config = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();
        $customer = PocomosCustomer::findOrFail($customer->id);
        $pestContract = PocomosPestContract::findOrFail($pestContract->id);

        $welcomeLetter = $this->renderDynamicTemplate($config->welcome_letter, null, $customer, $pestContract);
        // return($welcomeLetter);
        // try {
        //     /** @var PestControlAgreement $pestControllAggrement */
        //     $pestControlAgreement = $pestContract->getAgreement();
        //     $pestAgreement = $pestControlAgreement->getAgreement();
        //     $this->createACS($pestControlAgreement, $pestAgreement, $pestContract);
        // } catch (\Exception $e) {
        //     $this->logException(LogLevel::CRITICAL, "Failed to send SMS", $e);
        // }
        $res = array(
            'office' => $office,
            'customer' => $customer,
            'pest_contract' => $pestContract,
            'welcome_letter' => $welcomeLetter
        );

        return $this->sendResponse(true, __('strings.details', ['name' => 'Office welcome letter']), $res);
    }

    /**
     * Get remote completion details based on received email hash decode
     */
    public function getRemoteCompletionDetails(Request $request)
    {
        $v = validator($request->all(), [
            'hash' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pest_contract_id = Crypt::decryptString($request->hash);

        if (!$pest_contract_id) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate the contract.']));
        }

        $pestContract = PocomosPestContract::with('county', 'contract_tags', 'custom_fields.custom_field')->findOrFail($pest_contract_id);
        $salesContract = $pestContract->contract_details ?? null;
        $agreement = $salesContract->agreement_detail ?? null;
        $customer = $salesContract->profile_details ? $salesContract->profile_details->customer_details : null;
        $office = $agreement->office_details ?? null;
        $signature_data = ($pestContract->contract_details ? ($pestContract->contract_details->signature_details ? $pestContract->contract_details->signature_details->full_path : '') : '');

        $pestOfficeConfig = PocomosPestOfficeSetting::where('office_id', $office->id)->firstOrFail();

        $tax = $pestContract->contract_details->tax_details;

        $customAgreementTemplate = $salesContract->agreement_detail->custom_agreement_template;

        $firstJob = PocomosJob::with('slot')->whereContractId($pest_contract_id)->first();
        $firstJob = $firstJob ?? array();

        $res = array(
            'office' => $office,
            'pest_contract' => $pestContract,
            'agreement' => $agreement,
            'customer' => $customer,
            'tax' => $tax,
            'pest_office_config' => $pestOfficeConfig,
            'custom_agreement' => $customAgreementTemplate,
            'job' => $firstJob,
            'signature_data' => $signature_data
        );

        return $this->sendResponse(true, __('strings.details', ['name' => 'Remote completion details']), $res);
    }

    /**
     * Get remote completion agreement body details
     */
    public function getRemoteAgreementBody(Request $request)
    {
        $v = validator($request->all(), [
            'contract_id' => 'nullable|exists:pocomos_pest_contracts,id',
            'profile' => 'nullable|array',
            'county_id' => 'nullable',
            'custom_fields' => 'nullable|array',
            'agree' => 'nullable',
            'contract' => 'nullable|array'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pestContract = PocomosPestContract::with('county')->findOrFail($request->contract_id);
        $salesContract = $pestContract->contract_details ?? null;
        $agreement = $salesContract->agreement_detail ?? null;
        $office = $agreement->office_details ?? null;
        $customer = $salesContract->profile_details ? $salesContract->profile_details->customer_details : null;
        $billingAddress = $customer->billing_address;

        $pestOfficeConfig = PocomosPestOfficeSetting::where('office_id', $office->id)->firstOrFail();
        $profile = $salesContract->profile_details;

        $profile_details = $request->profile ?? array();
        $address_details = $request->profile['contact_address'] ?? array();
        $custom_fields = $request->custom_fields ?? array();
        $contract = $request->contract ?? array();
        $contract_start_date = ($pestContract->contract_details ? ($pestContract->contract_details->date_start ? date('Y-m-d', strtotime($pestContract->contract_details->date_start)) : '') : '');

        if (is_null($salesContract->agreement_details->custom_agreement_template)) {
            $agreement_body = $salesContract->agreement_details->agreement_body;
        } else {
            $agreement_body = $salesContract->agreement_details->custom_agreement_template->agreement_body;
        }

        $contract_type_id = $request->contract_id;
        $pest_agreement = PocomosPestAgreement::where('agreement_id', $agreement->id)->firstOrFail();
        $exceptions = unserialize($pest_agreement->exceptions);

        if (isset($pestContract->service_type_id) && $pestContract->service_type_id) {
            $service_type_res = PocomosPestContractServiceType::findOrFail($pestContract->service_type_id);
        } else {
            $service_type_res = array();
        }
        $tax_code = PocomosTaxCode::findOrFail($salesContract->tax_code_id);
        $technician_id = $pestContract->technician_id;

        $res = array();
        try {
            $signature_path = '';
            if (isset($contract['signature_data']) && $contract['signature_data']) {
                if (filter_var($contract['signature_data'], FILTER_VALIDATE_URL)) {
                    $signature_path = $contract['signature_data'];
                } else {
                    $signature = $contract['signature_data'];
                    // $signature_path = $signature->store('public/files');
                    $signature_id = $this->uploadFileOnS3('Customer', $signature);
                    $file = OrkestraFile::findOrFail($signature_id);
                    $signature_path = $file['path'];
                }
            }

            $variables = PocomosFormVariable::where('enabled', true)->where('active', true)->get();

            $res = DB::select(DB::raw("SELECT ofd.path as 'technician_photo', oud.first_name as 'technician_name', pco.fax as 'office_fax', pco.customer_portal_link, cld.path as 'company_logo', CONCAT(pad.suite, ', ' , pad.street, ', ' , pad.city, ', ' , pad.postal_code) as 'office_address', CONCAT(tad.suite, ', ' , tad.street, ', ' , tad.city, ', ' , tad.postal_code) as 'technician_address'
            FROM pocomos_technicians AS pt
            JOIN pocomos_company_office_users AS cou ON pt.user_id = cou.id
            JOIN pocomos_company_office_user_profiles AS oup ON cou.profile_id = oup.id
            JOIN orkestra_files AS ofd ON oup.photo_id = ofd.id
            JOIN orkestra_users AS oud ON oup.user_id = oud.id
            JOIN pocomos_company_offices AS pco ON cou.office_id = pco.id
            JOIN orkestra_files AS cld ON pco.logo_file_id = cld.id
            JOIN pocomos_addresses AS pad ON pco.contact_address_id = pad.id
            JOIN pocomos_addresses AS tad ON pt.routing_address_id = tad.id
            WHERE pt.id = '$technician_id' AND pt.active = 1"));
            $res = $res[0] ?? array();

            $technician = $res->technician_name ?? '';
            $service_type = $service_type_res->name ?? '';
            $office_address = $res->office_address ?? '';
            $office_phone = $res->office_fax ?? '';
            $service_addr = $address_details['suite'] . ', ' . $address_details['street'] . ', ' . $address_details['city'] . ', ' . ($address_details['state'] ?? '') . ', ' . $address_details['postal_code'];
            $company_logo = $res->company_logo ?? '';
            $billing_address = $billingAddress['suite'] . ', ' . $billingAddress['street'] . ', ' . $billingAddress['city'] . ', ' . ($billingAddress['state'] ?? '') . ', ' . $billingAddress['postal_code'];

            $pests_ids = PocomosPestContractsPest::where('contract_id', $salesContract->id)->get('pest_id')->toArray();
            $pests_name = PocomosPest::whereIn('id', $pests_ids)->pluck('name')->toArray();

            $selected_pests = implode(', ', $pests_name);
            $agreement_length = $agreement['length'] ?? 'N/A';
            $technician_photo = $res->technician_photo ?? '';
            $technician_bio = $res->technician_address ?? '';
            $initial_price_tax = $tax_code->tax_rate ?? '';
            $contract_value_tax = $tax_code->tax_rate ?? '';
            $initial_price_with_tax = $pestContract['initial_price'] - ($pestContract['initial_price'] * $tax_code->tax_rate / 100);
            $customer_portal_link = $res->customer_portal_link ?? '';

            foreach ($variables as $var) {
                if (@unserialize($var['type'])) {
                    $types_res = unserialize($var['type']);

                    if ($types_res !== false) {
                        if (in_array('Pest Agreement', $types_res)) {
                            $variable_name = $var['variable_name'] ?? null;

                            if (strpos($agreement_body, $variable_name) !== false) {
                                if ($variable_name === 'customer_name') {
                                    $value = $profile_details['first_name'] . ' ' . $profile_details['last_name'];
                                } elseif ($variable_name === 'service_address') {
                                    $value = $service_addr;
                                } elseif ($variable_name === 'customer_service_address') {
                                    $value = $service_addr;
                                } elseif ($variable_name === 'service_city') {
                                    $value = $address_details['city'] ?? '';
                                } elseif ($variable_name === 'service_state') {
                                    $value = $address_details['state'] ?? '';
                                } elseif ($variable_name === 'service_zip') {
                                    $value = $address_details['postal_code'] ?? '';
                                } elseif ($variable_name === 'customer_phone') {
                                    $value = $address_details['phone'] ?? '';
                                } elseif ($variable_name === 'customer_email') {
                                    $value = $address_details['email'] ?? '';
                                } elseif ($variable_name === 'contract_start_date') {
                                    $value = $contract_start_date;
                                } elseif ($variable_name === 'customer_signature') {
                                    if ($signature_path) {
                                        $value = '<img height="100px" width="200px" src="' . $signature_path . '">';
                                    } else {
                                        $value = '';
                                    }
                                } elseif ($variable_name === 'salesperson_signature') {
                                    // $value = '<img height="100px" width="200px" src="'.storage_path('app'). '/' . $signature_path.'">';
                                } elseif ($variable_name === 'balance') {
                                    $value = 0.00;
                                } elseif ($variable_name === 'credit') {
                                    $value = 0.00;
                                } elseif ($variable_name === 'invoice_numbers') {
                                    $value = '';
                                } elseif ($variable_name === 'technician') {
                                    $value = $technician;
                                } elseif ($variable_name === 'service_date') {
                                    $value = $scheduling_information['initial_date'] ?? '';
                                } elseif ($variable_name === 'service_time') {
                                    $value = '';
                                } elseif ($variable_name === 'service_frequency') {
                                    $value = implode(', ', unserialize($pestContract['service_frequency']));
                                } elseif ($variable_name === 'service_type') {
                                    $value = $service_type ?? '';
                                } elseif ($variable_name === 'office_address') {
                                    $value = $office_address ?? '';
                                } elseif ($variable_name === 'office_phone') {
                                    $value = $office_phone;
                                } elseif ($variable_name === 'service_address') {
                                    $value = $service_addr;
                                } elseif ($variable_name === 'company_logo') {
                                    $value = $company_logo ?? '';
                                } elseif ($variable_name === 'customer_last_name') {
                                    $value = $address_details['last_name'];
                                } elseif ($variable_name === 'customer_service_address') {
                                    $value = $address_details;
                                } elseif ($variable_name === 'customer_billing_address') {
                                    $value = $billing_address;
                                } elseif ($variable_name === 'agreement_price_info') {
                                    $value = $pestContract['initial_price'] ?? 0.00;
                                } elseif ($variable_name === 'auto_pay_checkbox') {
                                    // $value = $billing_information['is_enroll_auto_pay'] ? 'Autopay' : 'No Autopay';
                                } elseif ($variable_name === 'selected_pests') {
                                    $value = $selected_pests;
                                } elseif ($variable_name === 'agreement_length') {
                                    $value = $agreement_length;
                                } elseif ($variable_name === 'total_contract_value') {
                                    $value = $pestContract['initial_price'] ?? 0.00;
                                } elseif ($variable_name === 'customer_company_name') {
                                    $value = $address_details['company_name'] ?? '';
                                } elseif ($variable_name === 'next_service') {
                                    $value = '';
                                } elseif ($variable_name === 'contract_addendum') {
                                    $value = $agreement_input['addendum'] ?? '';
                                } elseif ($variable_name === 'customer_portal_link') {
                                    $value = $customer_portal_link;
                                } elseif ($variable_name === 'contract_recurring_price') {
                                    $value = $pestContract['recurring_price'];
                                } elseif ($variable_name === 'technician_photo') {
                                    $value = $technician_photo;
                                } elseif ($variable_name === 'technician_bio') {
                                    $value = $technician_bio;
                                } elseif ($variable_name === 'contract_initial_price') {
                                    $value = $pestContract['initial_price'];
                                } elseif ($variable_name === 'contract_total_contract_value_tax') {
                                    $value = $contract_value_tax;
                                } elseif ($variable_name === 'contract_initial_price_with_tax') {
                                    $value = $initial_price_with_tax;
                                } elseif ($variable_name === 'contract_initial_discount') {
                                    $value = $pestContract['initial_discount'];
                                } elseif ($variable_name === 'contract_initial_price_tax') {
                                    $value = $initial_price_tax;
                                } elseif ($variable_name === 'customer_last_service_date') {
                                    $value = '';
                                } elseif ($variable_name === 'contract_recurring_discount') {
                                    $value = 0;
                                } else {
                                    $value = '';
                                }
                                $agreement_body = str_replace('{{ ' . $variable_name . ' }}', $value, $agreement_body);
                            }
                        }
                    }
                }
            }

            $data = array(
                // 'pricing_overview' => $serviceCalendarHelper->getPricingOverview($contract),
                'service_schedule' => $this->getRemoteEmailServiceSchedule($request->all()),
                'billing_schedule' => $this->getRemoteEmailBillingSchedule($request->all()),
                'agreement_body' => $agreement_body,
                'exception_list' => $exceptions,
                'input_data' => $request->all()
            );
        } catch (\Exception $e) {
            $status = false;
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        return $this->sendResponse(true, __('strings.details', ['name' => 'Remote completion agreement body']), $data);
    }

    /**
     * Update customer details based on remote completion or finiliaze customer contract
     */
    public function remoteUpdateCustomer(Request $request)
    {
        $v = validator($request->all(), [
            'contract_id' => 'nullable|exists:pocomos_pest_contracts,id',
            'profile' => 'nullable|array',
            'county_id' => 'nullable',
            'agree' => 'nullable',
            'contract' => 'nullable|array'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        DB::beginTransaction();
        try {
            $pestContract = PocomosPestContract::with('county')->findOrFail($request->contract_id);
            $salesContract = $pestContract->contract_details ?? null;
            $agreement = $salesContract->agreement_detail ?? null;
            $office = $agreement->office_details ?? null;
            $customer = $salesContract->profile_details ? $salesContract->profile_details->customer_details : null;
            $contact_address = $customer->contact_address ?? null;

            $primary_phone = $customer->contact_address->primaryPhone ?? null;
            $alt_phone = $customer->contact_address->altPhone ?? null;

            $pestOfficeConfig = PocomosPestOfficeSetting::where('office_id', $office->id)->firstOrFail();
            $profile = $salesContract->profile_details;

            $profile_details = $request->profile ?? array();
            $address_details = $request->profile['contact_address'] ?? array();
            $custom_fields = ($request->profile ? ($request->profile['custom_fields'] ?? array()) : array());
            $contract = $request->contract ?? array();

            // $this->generateWelcomeEmail($pestContract);

            $customer->email_verified = false;

            // if ($pestOfficeConfig->alert_on_remote_completion) {
            //     $this->notifyRemoteCompletion($office, $customer, /* notifyEmail */
            //         $pestOfficeConfig->email_on_remote_completion);
            // }

            $customer->first_name = $profile_details['first_name'] ?? '';
            $customer->last_name = $profile_details['last_name'] ?? '';
            $customer->company_name = $profile_details['company_name'] ?? '';
            $customer->email = $profile_details['email'] ?? '';

            $contact_address->street = $address_details['street'] ?? '';
            $contact_address->suite = $address_details['suite'] ?? '';
            $contact_address->city = $address_details['city'] ?? '';
            $contact_address->region_id = $address_details['region'] ?? '';
            $contact_address->postal_code = $address_details['postal_code'] ?? '';
            $contact_address->city = $address_details['city'] ?? '';

            $primary_phone->number = $profile_details['phone'];
            $primary_phone->type = $profile_details['phone_type'];

            $alt_phone->number = $profile_details['alt_phone'];
            $alt_phone->type = $profile_details['alt_phone_type'];

            $pestContract->county_id = $request->county_id;

            $signature_id = null;
            $signed = false;
            if (isset($contract['signature_data']) && $contract['signature_data']) {
                $signature_id = $this->uploadFileOnS3('Customer', $contract['signature_data']);
                $signed = true;
                $salesContract->signed = $signed;
                $salesContract->signature_id = $signature_id;
            }

            $autopay_signature_id = null;
            $autopay_signed = false;
            if (isset($contract['autopay_signature_data']) && $contract['autopay_signature_data']) {
                $autopay_signature_id = $this->uploadFileOnS3('Customer', $contract['autopay_signature_data']);
                $autopay_signed = true;
                $salesContract->autopay_signature_id = $autopay_signature_id;
            }

            $customer->save();
            $contact_address->save();
            $primary_phone->save();
            $alt_phone->save();
            $salesContract->save();

            if ($custom_fields) {
                PocomosCustomField::where('pest_control_contract_id', $request->contract_id)->delete();
                foreach ($custom_fields as $key => $value) {
                    PocomosCustomField::create(['pest_control_contract_id' => $request->contract_id, 'custom_field_configuration_id' => $key, 'value' => $value, 'active' => true]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        // replace old agreement file with the new one
        /** @var \Pocomos\Bundle\SalesManagementBundle\Helper\FileHelper $fileHelper */
        // $path = $this->get('pocomos.sales.helper.file')->getContractFilename($salesContract);
        // if (file_exists($path)) {
        //     unlink($path);
        // }
        $params = array(
            'office' => $office,
            'customer' => $customer,
            'agreement' => $salesContract->agreement_details,
            'contract' => $salesContract,
            'pestContract' => $pestContract,
        );
        $template = $this->generateContractAgreement($params);

        $url =  "contract/" . preg_replace('/[^A-Za-z0-9\-]/', '', $salesContract->id) . '.pdf';

        $pdf = PDF::loadView('pdf.dynamic_render', compact('template'));

        Storage::disk('s3')->put($url, $pdf->output(), 'public');

        $path = Storage::disk('s3')->url($url);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Remote completion']), $path);
        // return new JsonRedirectResponse($this->generateUrl('customer_remote_completion_success'));
    }

    /**
     * Get required details for after remote completion process is finished
     */
    public function customerRemoteCompletionSuccess(Request $request)
    {
        $v = validator($request->all(), [
            'contract_id' => 'nullable|exists:pocomos_pest_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pestContract = PocomosPestContract::findOrFail($request->contract_id);
        $salesContract = $pestContract->contract_details;
        $agreement = $salesContract->agreement_details;
        $customer = $salesContract->profile_details->customer_details;
        $office = $agreement->office_details;

        $config = $office->office_configuration;

        $welcomeLetter = $this->renderDynamicTemplate($config->welcome_letter, null, $customer, $pestContract);

        $res = array(
            'office' => $office,
            'customer' => $customer,
            'pest_contract' => $pestContract,
            'welcome_letter' => $welcomeLetter
        );

        return $this->sendResponse(true, __('strings.details', ['name' => 'Remote completion success']), $res);
    }

    /**
     * Downlaod agreement contract
     */
    public function downlaodAgreement(Request $request)
    {
        $v = validator($request->all(), [
            'contract_id' => 'nullable|exists:pocomos_pest_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pestContract = PocomosPestContract::findOrFail($request->contract_id);
        $salesContract = $pestContract->contract_details ?? null;
        $agreement = $salesContract->agreement_detail ?? null;
        $customer = $salesContract->profile_details ? $salesContract->profile_details->customer_details : null;
        $office = $agreement->office_details ?? null;

        $url =  config('constants.S3_INTERNAL_PATH') . "contract/" . preg_replace('/[^A-Za-z0-9\-]/', '', $salesContract->id) . '.pdf';

        // TODO Move this somewhere better
        // if (!file_exists($url)) {
        //     $this->generateAgreement($office, $customer, $salesContract, $pestContract);
        // }

        // header("Cache-Control: public");
        // header("Content-Description: File Transfer");
        // header("Content-Disposition: attachment; filename=" . basename($url));
        // header("Content-Type: " . $salesContract->id.'.pdf');

        // return readfile($url);
        $res['url'] = $url;
        return $this->sendResponse(true, __('strings.details', ['name' => 'Agreement File']), $res);
    }

    /**Customer email verify */
    public function verifyEmail(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'nullable|exists:pocomos_customers,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);
        $customer->email_verified = true;
        $customer->save();
        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Email verified']));
    }

    /**
     * Resend emails
     *
     * @param Request $request
     */
    public function resendEmailBulk(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'type' => 'required|in:verification,invoices,summary,contract,customer_user,remote_completion',
            'summary' => 'nullable',
            'jobs' => 'array',
            'jobs.*' => 'exists:pocomos_jobs,id',
            'invoices' => 'array',
            'invoices.*' => 'exists:pocomos_invoices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $jobs = $request->jobs ?? array();
        $invoices = $request->request->get('invoices', []);
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->whereUserId(auth()->user()->id)->first();

        $args = array_merge(array(
            'jobIds' => $jobs,
            'invoiceIds' => $invoices,
            'officeId' => $request->office_id,
            'officeUserId' => $officeUser->id
        ), $request->all());

        ResendBulkEmailJob::dispatch($args);

        return $this->sendResponse(true, __('strings.message', ['message' => 'The messages will be sent shortly. You will be notified when the emails are sent.']));
    }

    /**
     * advanced_search_send_form_letter
     *
     * @param Request $request
     */
    public function sendFormLetterAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'form_letter_id' => 'required|exists:pocomos_form_letters,id',
            'customers' => 'required|array',
            'customers.*' => 'exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customerIds = $request->customers;

        if (count($customerIds) > 500) {
            $customer_chunk = array_chunk($customerIds, 500);
            foreach ($customer_chunk as $customers) {
                $args = array_merge(array(
                    'officeId' => $request->office_id,
                    'letterId' => $request->form_letter_id,
                    'customerIds' => $customers,
                ), $request->all());

                SendMassEmailJob::dispatch($args);
            }
        } else {
            $args = array_merge(array(
                'officeId' => $request->office_id,
                'letterId' => $request->form_letter_id,
                'customerIds' => $customerIds,
            ), $request->all());

            SendMassEmailJob::dispatch($args);
        }

        return $this->sendResponse(true, __('strings.message', ['message' => 'The form letters will be sent shortly.']));
    }

    /**Customer general search */
    public function customerSearch(Request $request)
    {
        $v = validator($request->all(), [
            'search' => 'nullable|min:3',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'active' => 'required|nullable|boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office_id = $request->office_id;

        $sql = 'SELECT cu.id, CONCAT(cu.first_name, \' \', cu.last_name) As name,
        CONCAT(ca.street, " ",  ca.city, ", ",reg.code, " ", ca.postal_code) as address, cu.email, pn.number, cu.status as status,
        cph.phone_id
        FROM pocomos_customers AS cu
        JOIN pocomos_customer_sales_profiles csp on cu.id = csp.customer_id
        JOIN pocomos_contracts co on csp.id = co.profile_id
        LEFT JOIN pocomos_invoices i on co.id = i.contract_id
        JOIN pocomos_customer_state cus                  on cus.customer_id = cu.id
        JOIN pocomos_addresses ca                        on cu.contact_address_id = ca.id
        JOIN orkestra_countries_regions reg              on ca.region_id = reg.id
        LEFT JOIN pocomos_customers_phones AS cph ON csp.id = cph.profile_id
        JOIN pocomos_phone_numbers AS pn ON cph.phone_id = pn.id
        WHERE csp.office_id = ' . $office_id;

        if ($request->search) {
            $search = $request->search;
            $sql .= ' AND (
                i.id LIKE "%' . $search . '%"
                OR cu.email LIKE "%' . $search . '%"
                OR CONCAT(cu.first_name, \' \', cu.last_name) LIKE "%' . $search . '%"
                OR CONCAT(ca.street, \' \', ca.suite, \' \', ca.city) LIKE "%' . $search . '%"
                OR CONCAT(ca.street, \' \', ca.suite) LIKE "%' . $search . '%"
                OR cu.first_name LIKE "%' . $search . '%"
                OR ca.street LIKE "%' . $search . '%"
                OR ca.suite LIKE "%' . $search . '%"
                OR ca.city LIKE "%' . $search . '%"
                OR cu.company_name LIKE "%' . $search . '%"
                OR ca.postal_code LIKE "%' . $search . '%"
                OR cu.external_account_id LIKE "%' . $search . '%"
                OR cu.id LIKE "%' . $search . '%"';

            $phoneNumber = preg_replace('/[^0-9]/', '', $search);
            if (is_numeric($phoneNumber) && strlen($phoneNumber) > 9) {
                $sql .= ' OR pn.number LIKE ' . $phoneNumber . ' ';
            }

            $sql .= ')';
        }

        if ($request->active == 0) {
            $sql .= ' AND cu.status IN ("Active", "On-Hold") AND csp.active = 1';
        } else {
            $sql .= ' AND cu.status IN ("Active", "Inactive", "On-Hold") AND csp.active = 1';
        }

        $sql .= ' GROUP BY cu.id';

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";
        $customers = DB::select(DB::raw("$sql"));

        $data = [
            'customers' => $customers,
            'count' => $count
        ];
        return $this->sendResponse(true, __('strings.list', ['name' => 'Customers']), $data);
    }

    public function customerlookup(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
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

        $office_id = $request->office_id;
        $newOffice = $profile->office_id;

        if ($office_id != $newOffice) {
            $this->switchOffice($office_id);

            $officeUserId = Session::get(config('constants.ACTIVE_OFFICE_USER_ID'));
            $officeUser = PocomosCompanyOfficeUser::findOrFail($officeUserId);
            $user = OrkestraUser::whereId($officeUser->user_id)->first();
            // with('pocomos_company_office_users.company_details.office_settings')->
            $allOffices = PocomosCompanyOffice::with('office_settings', 'logo', 'coontact_address')->where('parent_id', $office_id)->get()->toArray();
            if (!$allOffices) {
                $allOffices = PocomosCompanyOffice::whereId($office_id)->first();
                $allOffices = PocomosCompanyOffice::with('office_settings', 'logo', 'coontact_address')->whereId($allOffices->parent_id)->get()->toArray();
            }
            $parentOffice = PocomosCompanyOffice::with('office_settings', 'logo', 'coontact_address')->whereId($office_id)->first()->toArray();
            $allOffices[] = $parentOffice;
            $success['customer'] =  $customer;
            $success['customer_profile'] =  $profile;
            $success['user'] =  $user;
            //Create new token
            $success['token'] =  $user->createToken('MyAuthApp')->plainTextToken;

            $i = 0;
            foreach ($allOffices as $office) {
                $current_active_office = Session::get(config('constants.ACTIVE_OFFICE_ID'));
                $is_default_selected = false;
                if ($current_active_office == $office['id']) {
                    $is_default_selected = true;
                }
                $allOffices[$i]['is_default_selected'] = $is_default_selected;
                $i = $i + 1;
            }
            $user->offices_details = $allOffices;

            return $this->sendResponse(true, __('strings.sucess', ['name' => 'New Office Details']), $success);
        }
        $userId = auth()->user()->pocomos_company_office_user->user_id;
        // $officeUserId = Session::get(config('constants.ACTIVE_OFFICE_USER_ID'));
        // $officeUser = PocomosCompanyOfficeUser::findOrFail($officeUserId);
        // $user = OrkestraUser::whereId($officeUser->user_id)->first();
        $user = OrkestraUser::whereId($userId)->firstOrFail();

        $success['customer'] =  $customer;
        $success['customer_profile'] =  $profile;
        $success['user'] =  $user;

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'current Office Details']), $success);

        // $res = PocomosCustomer::with(['contact_address.primaryPhone', 'contact_address.altPhone', 'billing_address.primaryPhone', 'billing_address.altPhone', 'contact_address.region', 'billing_address.region', 'sales_profile.points_account', 'sales_profile.autopay_account', 'sales_profile.external_account', 'sales_profile.sales_people.office_user_details.user_details', 'sales_profile.sales_people.office_user_details.profile_details', 'sales_profile.sales_people.office_user_details.company_details', 'sales_profile.contract_details.agreement_details', 'sales_profile.contract_details.tax_details', 'sales_profile.contract_details.pest_contract_details', 'sales_profile.contract_details.pest_contract_details.pest_agreement_details', 'sales_profile.contract_details.pest_contract_details.service_type_details', 'sales_profile.contract_details.pest_contract_details.contract_tags', 'sales_profile.contract_details.pest_contract_details.contract_tags.tag_details', 'sales_profile.contract_details.marketing_type', 'sales_profile.contract_details.sales_status', 'notes_details.note', 'sales_profile.contract_details.pest_contract_details.targeted_pests.pest', 'state_details', 'sales_profile.contract_details.pest_contract_details.custom_fields.custom_field', 'sales_profile.contract_details.state_report', 'sales_profile.contract_details.search_report_state', 'sales_profile.contract_details.pest_contract_details.county', 'sales_profile.contract_details.pest_contract_details.jobs_details' => function ($q) {
        //     $q->where('status', config('constants.COMPLETE'));
        //     $q->orderBy('date_completed', 'DESC');
        //     $q->orderBy('id', 'DESC');
        // }, 'sales_profile.contract_details.pest_contract_details.jobs_details.route_detail'])->findOrFail($id);

        // $c = 0;
        // $i = 0;
        // foreach ($res->sales_profile->contract_details as $val) {
        //     $session = Session::get(config('constants.ACTIVE_CONTEXT_KEY'));
        //     $session = (array)$session;

        //     $is_default_selected = false;
        //     if (isset($session['contract']) && $session['contract'] == $val->id) {
        //         $is_default_selected = true;
        //     }

        //     if (!$is_default_selected) {
        //         $c = $c + 1;
        //     }
        //     if (count($res->sales_profile->contract_details) == $c) {
        //         $res->sales_profile->contract_details[$i]['is_default_selected'] = true;
        //     } else {
        //         $res->sales_profile->contract_details[$i]['is_default_selected'] = $is_default_selected;
        //     }
        //     $i = $i + 1;
        // }
        // Session::start();

        // $res->sessions = Session::all();
    }

    /**Send customer sms */
    public function sendFormSmsAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'form_letter_id' => 'required|exists:pocomos_sms_form_letters,id',
            'customers' => 'required|array',
            'customers.*' => 'exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        try {
            DB::beginTransaction();

            $customerIds = $request->request->get('customers', []);

            $args = array_merge(array(
                'officeId' => $request->office_id,
                'letterId' => $request->form_letter_id,
                'customerIds' => $customerIds,
            ), $request->all());

            SendMassSmsJob::dispatch($args);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        return $this->sendResponse(true, __('strings.message', ['message' => 'The form letters will be sent shortly.']));
    }

    /**Send customer sms */
    public function sendCustomerEmployeeSms(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'nullable|exists:pocomos_customers,id',
            'phone_id' => 'required|exists:pocomos_phone_numbers,id',
            'message' => 'required',
            'letter_id' => 'nullable|exists:pocomos_sms_form_letters,id',
            'recipient' => 'required|in:customer,employee',
            'office_user_id' => 'required_if:recipient,==,employee|exists:pocomos_company_office_users,id',
            'office_user_cc' => 'nullable|array',
            'office_user_cc.*' => 'exists:pocomos_company_office_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        try {
            DB::beginTransaction();

            $recipient = $request->recipient;
            $phone = PocomosPhoneNumber::findOrFail($request->phone_id);
            $office = PocomosCompanyOffice::findOrFail($request->office_id);

            if (!$request->letter_id) {
                $letter = PocomosSmsFormLetter::create([
                    'office_id' => $request->office_id,
                    'category' => true,
                    'title' => '',
                    'message' => $request->message,
                    'description' => '',
                    'confirm_job' => false,
                    'require_job' => false,
                    'active' => true
                ]);
            } else {
                $letter = PocomosSmsFormLetter::findOrFail($request->letter_id);
            }

            $currentUser = auth()->user()->id;
            $officeUser = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->whereUserId(auth()->user()->id)->first();
            if ($recipient == 'customer') {
                $customer = PocomosCustomer::findOrFail($request->customer_id);
                $this->sendSmsFormLetterByPhone($customer, $phone, $letter, $officeUser);
            } else {
                $this->sendMessage($office, $phone, $request->message, $officeUser, true /* seen */);
                $office_user_cc = $request->office_user_cc ?? array();
                // $office_user[] = $request->office_user_id;
                // $users = array_merge($office_user_cc, $office_user);

                foreach ($office_user_cc as $user) {
                    $user = PocomosCompanyOfficeUser::findOrFail($user);
                    $userProfile = PocomosCompanyOfficeUserProfile::findOrFail($user->profile_id);
                    $phoneNew = $userProfile->phone_details ?? $phone;
                    $this->sendMessage($office, $phoneNew, $request->message, $user, true /* seen */);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }
        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Message sended']));
    }

    /**
     * Finds and displays an Unanswered Messages entity.
     * @return array
     */
    public function getCustomerTextMessagesList(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'phone_id' => 'nullable|exists:pocomos_phone_numbers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $phoneId = $request->phone_id ?? null;
        $officeId = $request->office_id;
        $customerId = $request->customer_id;
        $office = PocomosCompanyOffice::findOrFail($officeId);
        $messages = $phones = $customer = array();

        $customerProfile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->where('office_id', $officeId)->firstOrFail();
        $customer = PocomosCustomer::findOrFail($request->customer_id);

        $phones = DB::table('pocomos_customers')
            ->join('pocomos_customer_sales_profiles', 'pocomos_customer_sales_profiles.customer_id', '=', 'pocomos_customers.id')
            ->join('pocomos_customers_notify_mobile_phones', 'pocomos_customers_notify_mobile_phones.profile_id', '=', 'pocomos_customer_sales_profiles.id')
            ->join('pocomos_phone_numbers', 'pocomos_phone_numbers.id', '=', 'pocomos_customers_notify_mobile_phones.phone_id')
            ->selectRaw('pocomos_phone_numbers.*')
            ->where('pocomos_customers.id', $request->customer_id)
            ->where('pocomos_phone_numbers.active', true)
            ->get()->toArray();

        $messages = array();
        $seen = $phone = false;
        if (!$phoneId) {
            // return 11;
            $phoneId = $this->getLastMessagePhoneByCustomer($customerId);
        }

        if ($phoneId) {
            // return 22;
            $phone = $this->findActiveNotifyPhone($customerProfile, $phoneId);
        } elseif (count($phones)) {
            $phone = count($phones) ? $phones[0] : array();
        }

        // return $phone;

        if ($phone) {
            $perPage = $page = false;
            if ($request->page && $request->perPage) {
                $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
                $page    = $paginateDetails['page'];
                $perPage = $paginateDetails['perPage'];
            }
            $messages = $this->getPhoneMessages($officeId, $phone->id, $perPage, $page);
        }

        return $this->sendResponse(true, __('strings.list', ['name' => 'Messages']), array(
            'seen' => $seen,
            'messages' => $messages,
            'customer_phone' => $phone,
            'phones' => $phones,
            'customer' => $customer,
        ));
    }

    /**
     * Mark message as read
     */
    public function markMessageAsReadAction(Request $request)
    {
        $v = validator($request->all(), [
            'message_id' => 'nullable|exists:pocomos_sms_usage,id',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sql = 'UPDATE `pocomos_sms_usage` SET seen = 1 WHERE office_id = ' . $request->office_id;

        if ($request->message_id) {
            $sql .= ' AND id = ' . $request->message_id;
        }

        DB::select(DB::raw($sql));

        // return $this->sendResponse(true, __('strings.update', ['name' => 'Message']));
        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Marked all as read for both customer and employee']));
    }

    // mark all as read particular for particular customer
    public function markAsReadAction(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'phone_id' => 'required|exists:pocomos_phone_numbers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        $customer = $this->findOneByIdAndOffice_customerRepo($custId, $officeId);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find Customer entity.']));
        }

        $custProfile = PocomosCustomerSalesProfile::findorfail($customer->profile_id);

        $phone = $this->findActiveNotifyPhone($custProfile, $request->phone_id);
        if (!$phone) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find Phone entity.']));
        }

        $this->markMessagesAsRead($phone);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Marked all as read for particular customer/employee']));
    }

    public function getCustomerPhoneNumbers($custId)
    {
        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        $customer = $this->findOneByIdAndOffice_customerRepo($custId, $officeId);

        if (!$customer) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find Customer entity.']));
        }

        $profile = PocomosCustomerSalesProfile::whereCustomerId($customer->id)->first();

        $phones = $this->getActiveNotifyMobilePhones($profile);

        return $this->sendResponse(true, __('strings.list', ['name' => 'Customer Phone Numbers']), array(
            'phones' => $phones,
            'customer' => $customer,
        ));
    }

    public function getEmployeePhoneNumbers($couId)
    {
        $phones = PocomosCompanyOfficeUser::with('profile_details.phone_details')->findorfail($couId);

        return $this->sendResponse(true, __('strings.list', ['name' => 'Employee Phone Numbers']), $phones);
    }

    /**
     * Lists unanswered text messages
     * @return array
     */
    public function getUnansweredTextMessages(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'seen' => 'required|boolean',
            'filter' => 'required|in:customer,employee,all',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable',
            'sort' => 'nullable|in:name,date',
            'sort_type' => 'nullable|in:desc,asc'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $messages = $unansweredMessages = $messageData = $employeeMessages = $phones = array();
        $phone = $officeUser = $customer = (object)array();
        $phoneId = null;

        $seen = $request->seen;
        $sort = $request->sort;
        $sortType = $request->sort_type;
        $search = $request->search;
        $filter = $request->filter;
        $officeId = $request->office_id;
        $office = PocomosCompanyOffice::findOrFail($officeId);

        if ($sort == 'name') {
            $sortStr = 'customer_name';
        } else {
            $sortStr = 'date_created';
        }

        if ($sortType == 'asc') {
            $sortTypeStr = SORT_ASC;
        } else {
            $sortTypeStr = SORT_DESC;
        }

        if ($filter == "customer") {
            $unansweredMessages = $this->getInboundMessages($officeId, $seen, $search, $sort, $sortType);
            $employeeMessages = array();
        } elseif ($filter == "employee") {
            $employeeMessages = array();
            $unansweredMessages = $this->getEmployeeMessages($officeId, $seen, $search, $sort, $sortType);
        } else {
            $unansweredMessages = $this->getInboundMessages($officeId, $seen, $search, $sort, $sortType);
            $employeeMessages = $this->getEmployeeMessages($officeId, $seen, $search, $sort, $sortType);
        }

        if (count($unansweredMessages) > 0) {
            $lastMessage = (array)$unansweredMessages[0];
            if (array_key_exists('customer_id', $lastMessage)) {
                $id = $lastMessage['customer_id'];
                if (count($lastMessage) > 0) {
                    $customer = $this->findCustomerByIdAndOffice($id, $officeId);
                    $customer = PocomosCustomer::findOrFail($customer->id);
                    if (!$customer) {
                        throw new \Exception(__('strings.message', ['message' => 'Unable to find Customer']));
                    }
                    $phones = $this->getActiveNotifyMobilePhones($customer->sales_profile);
                    $messages = array();
                    $phone = false;
                    if (!$customer) {
                        $phoneId = $this->getLastMessagePhoneByCustomer($customer->id);
                    }
                    if ($phoneId) {
                        $phone = $this->findActiveNotifyPhone($customer->sales_profile, $phoneId);
                    } elseif (count($phones)) {
                        $phone = count($phones) ? $phones[0] : array();
                    }
                    if ($phone) {
                        $perPage = $page = false;
                        if ($request->page && $request->perPage) {
                            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
                            $page    = $paginateDetails['page'];
                            $perPage = $paginateDetails['perPage'];
                        }
                        $messages = $this->getPhoneMessages($officeId, $phone->id, $perPage, $page);
                    }
                }
            } else {
                if (count($lastMessage) > 0) {
                    $officeUserId = $lastMessage['user_id'];
                    $user = OrkestraUser::findOrFail($officeUserId);
                    $officeUser = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereUserId($officeUserId)->first();
                    if (!$officeUser) {
                        throw new \Exception(__('strings.message', ['message' => 'User has no access to this office']));
                    }
                    $phone =  $officeUser->profile_details->phone_details;
                    $phones[] =  $officeUser->profile_details->phone_details;
                    $messages = array();
                    $perPage = $page = false;
                    if ($request->page && $request->perPage) {
                        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
                        $page    = $paginateDetails['page'];
                        $perPage = $paginateDetails['perPage'];
                    }
                    $messages = $this->getPhoneMessages($officeId, $phone->id, $perPage, $page);
                }
            }
        }

        // for customers/employees list
        $messageData = array_merge($unansweredMessages, $employeeMessages);

        $key_values = array_column($messageData, $sortStr);
        array_multisort($key_values, $sortTypeStr, $messageData);

        return $this->sendResponse(
            true,
            __('strings.list', ['name' => 'Messages']),
            array(
                'unanswered_messages' => $messageData,
                'seen' => $seen,
                'messages' => $messages,
                'customer_phone' => $phone,
                'phones' => $phones,
                'customer' => $customer,
                'office_user' => $officeUser,
            )
        );
    }

    /**
     * Finds and displays an Unanswered Messages entity.
     * @Secure(roles="ROLE_SECRETARY")
     * @return array
     */
    public function getEmployeeTextMessagesList(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|exists:orkestra_users,id',
            'phone_id' => 'nullable|exists:pocomos_phone_numbers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $userId = $request->user_id;    //get from getUnansweredTextMessages api > unanswered_messages key
        $office = PocomosCompanyOffice::findOrFail($officeId);
        $seen = $phone = false;
        $messages = $phones = $customer = array();
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereUserId($userId)->first();
        if (!$officeUser) {
            throw new \Exception(__('strings.message', ['message' => 'User has no access to this office']));
        }
        $phone =  $officeUser->profile_details->phone_details;
        $phones[] =  $officeUser->profile_details->phone_details;
        $messages = array();

        $perPage = $page = false;
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
        }
        $messages = $this->getPhoneMessages($officeId, $phone->id, $perPage, $page);

        if ($phone) {
            $messages = $this->getPhoneMessages($officeId, $phone->id, $perPage, $page);
        }

        return $this->sendResponse(true, __('strings.list', ['name' => 'Messages']), array(
            'seen' => $seen,
            'messages' => $messages,
            'customer_phone' => $phone,
            'phones' => $phones,
            'office_user' => $officeUser,
            'office_user_id' => $userId,
        ));
    }

    /**
     * Mark message as unread
     */
    public function changeMessageStatus(Request $request)
    {
        $v = validator($request->all(), [
            'message_id' => 'nullable|exists:pocomos_sms_usage,id',
            'phone_id' => 'nullable|exists:pocomos_sms_usage,phone_id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'seen' => 'required|boolean'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $seen = $request->seen;
        $messageId = $request->message_id;
        $phoneId = $request->phone_id;

        $sql = 'UPDATE `pocomos_sms_usage` SET seen = ' . $seen . ' WHERE office_id = ' . $officeId;

        if (is_numeric($messageId)) {
            $sql .= ' AND id = ' . $messageId;
        }

        if (is_numeric($phoneId)) {
            $sql .= ' AND phone_id = ' . $phoneId;
        }

        DB::select(DB::raw($sql));
        return $this->sendResponse(true, __('strings.update', ['name' => 'Message status']));
    }

    /**
     * Send a bulk resend email form
     */
    public function sendBulkEmailToCustomers(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'type' => 'required',
            'summary' => 'nullable',
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:pocomos_customers,id'
        ], [
            'customer_ids.*.exists' => __('validation.exists', ['attribute' => 'customer id'])
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $customerIds = $request->customer_ids;
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereUserId(auth()->user()->id)->firstOrFail();

        $args = array_merge(array(
            'customerIds' => $customerIds,
            'officeId' => $officeId,
            'officeUserId' => $officeUser->id,
            'type' => $request->type
        ));

        SendEmailToOfficeCustomersJob::dispatch($args);
        return $this->sendResponse(true, __('strings.message', ['message' => 'The messages will be sent shortly. You will be notified when the emails are sent']));
    }

    /**
     * Save a bulk tax code for customer
     */
    public function bulkSaveCustomerTaxCode(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'tax_code' => 'required',
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:pocomos_customers,id'
        ], [
            'customer_ids.*.exists' => __('validation.exists', ['attribute' => 'customer id'])
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $customerIds = $request->customer_ids;
        $taxCodeId = $request->tax_code;
        $taxCode = PocomosTaxCode::where('id', $taxCodeId)->whereActive(true)->firstOrFail();

        foreach ($customerIds as $customerId) {
            $customer = $this->findCustomerByIdAndOffice($customerId, $officeId);
            if (!$customer) {
                throw new \Exception(__('strings.message', ['message' => 'Unable to find the Customer']));
            }
            $customer = PocomosCustomer::findOrFail($customer->id);
            $salesProfile = $customer->sales_profile;
            $contracts = $salesProfile->contract_details;
            foreach ($contracts as $contract) {
                $pestContract = $contract->pest_contract_details;
                $result = $this->updatePestContractTaxCode($pestContract->id, $taxCode);
            }
        }

        return $this->sendResponse(true, __('strings.update', ['name' => 'Customer tax code']));
    }

    /**
     * Updates a recurring price associated with a contract
     */
    public function bulkUpdateRecurringPrice(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'agreement_id' => 'required|exists:pocomos_agreements,id',
            'recurring_price' => 'nullable',
            'initial_price' => 'nullable',
            'original_value' => 'nullable',
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:pocomos_customers,id'
        ], [
            'customer_ids.*.exists' => __('validation.exists', ['attribute' => 'customer id'])
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $setContract = null;
        $officeId = $request->office_id;
        $agreementId = $request->agreement_id;
        $customerIds = $request->customer_ids;

        foreach ($customerIds as $customerId) {
            $customer = $this->findCustomerByIdAndOffice($customerId, $officeId);
            if (!$customer) {
                throw new \Exception(__('strings.message', ['message' => 'Unable to find the Customer']));
            }
            $customer = PocomosCustomer::findOrFail($customer->id);
            $salesProfile = $customer->sales_profile;
            $contracts = $salesProfile->contract_details;
            $agreement = PocomosAgreement::findOrFail($agreementId);
            foreach ($contracts as $contract) {
                if ($contract->agreement_id == $agreementId) {
                    $setContract = $contract;
                    break;
                }
            }
            if (isset($setContract)) {
                $contract = $setContract->pest_contract_details;
            } else {
                continue;
            }
            $contractState = PocomosReportsContractState::whereContractId($contract->id)->first();
            $initialPrice = $request->initial_price;

            $result = $this->updateRecurringPrice($contract, $request->recurring_price);
            if ($contract->modifiable_original_value != $contract->modifiable_original_value) {
                $contract->date_original_value_updated = date('Y-m-d H:i:s');
            }
            $contract->modifiable_original_value = $contract->modifiable_original_value;
            if (abs($initialPrice - $contract->initial_price) > 0.00) {
                $job = $this->getFirstJobNew($contract->id);
                $job = PocomosJob::findOrFail($job['id']);
                $invoice = $job->invoice_detail;
                if (
                    $job->type == config('constants.INITIAL')
                    && $job->status != config('constants.COMPLETE')
                    && !(config('constants.PAID') === $invoice->status)
                ) {
                    $invoiceItem = $invoice->invoice_items;
                    $invoiceItem = $invoiceItem[0] ?? array();
                    $this->updateInvoiceItem($invoiceItem, $initialPrice);
                }

                $contract->initial_price = $initialPrice;
            }
            $contract->save();
        }
        return $this->sendResponse(true, __('strings.update', ['name' => 'The recurring price has been']));
    }

    /**
     * Activate a Customer.
     *
     * @Secure(roles="ROLE_CUSTOMER_WRITE")
     * @param Request $request
     * @param $id
     */
    public function activateCustomerStatus(Request $request)
    {
        $v = validator($request->all(), [
            'modify_sales_status' => 'required|boolean',
            'customer_id'         => 'required|exists:pocomos_customers,id',
            'status'              => 'required|in:Active',
            'sales_status'        => 'required|exists:pocomos_sales_status,id',
            'contracts'           => 'nullable|exists:pocomos_contracts,id|array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if ($request->modify_sales_status) {
            $update_details['sales_status_id'] = $request->sales_status ?? null;
            $update_details['sales_status_modified'] = date('Y-m-d H:i:s');
        }

        if ($request->contracts) {
            PocomosContract::whereIn('id', $request->contracts)->update($update_details);
        }

        $this->activateCustomer($request->customer_id);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Customer activated']));
    }
}
