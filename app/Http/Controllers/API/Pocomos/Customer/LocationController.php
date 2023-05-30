<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Excel;
use Illuminate\Http\Request;
use App\Jobs\ExportCustomers;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Exports\ExportQrReport;
use App\Models\Pocomos\PocomosPestDiscountTypeItem;
use App\Models\Pocomos\PocomosJob;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosLead;
use App\Models\Pocomos\PocomosSubCustomer;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosPest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SendEmailCustomerExport;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosCustomersPhone;
use App\Http\Requests\LocationRequest;
use App\Mail\RemoteCompletionCustomer;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosLeadNote;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Pocomos\PocomosLeadQuote;
use App\Mail\RemoteCompletionRecruitment;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosFormVariable;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosLeadQuoteTag;
use App\Models\Pocomos\PocomosLeadQuotPest;
use App\Models\Pocomos\PocomosLeadReminder;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosCustomersFile;
use App\Models\Pocomos\PocomosCustomersNote;
use App\Models\Pocomos\PocomosCustomerState;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\PocomosPestContractsPest;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosPestContractsInvoice;
use App\Models\Pocomos\PocomosCustomersWorkorderNote;
use App\Models\Pocomos\PocomosLeadQuoteSpecialtyPest;
use App\Models\Pocomos\PocomosPestContractServiceType;
use App\Models\Pocomos\PocomosCustomersNotifyMobilePhone;

class LocationController extends Controller
{
    use Functions;

    /**
     * API for Create Location
   .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(LocationRequest $request)
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
            $pest_discount_types = ($request->service_information ? $request->service_information['pest_discount_types'] ?? array() : array());

            // $tags = ($request->service_information ? $request->service_information['tags'] ?? array() : array());
            // $targeted_pests = ($request->service_information ? $request->service_information['targeted_pests'] ?? array() : array());
            // $specialty_pests = ($request->service_information ? $request->service_information['specialty_pests'] ?? array() : array());
            // $agreement_input = ($request->agreement ?? array());

            $input_details['first_name'] = $service_address['first_name'] ?? null;
            $input_details['last_name'] = $service_address['last_name'] ?? null;
            $input_details['email'] = $service_address['email'] ?? '';
            $input_details['active'] = true;
            $input_details['email_verified'] = true;
            $input_details['company_name'] = $service_address['company_name'] ?? '';
            $input_details['secondary_emails'] = implode(',', $service_address['secondary_emails'] ?? array());
            $input_details['account_type'] = 'Residential';

            if (isset($service_address['phone'])) {
                $phone_number['alias'] = 'Primary';
                $phone_number['number'] = $service_address['phone'];
                $phone_number['type'] = $service_address['phone_type'] ?? 'Home';
                $phone_number['active'] = true;
                $phone = PocomosPhoneNumber::create($phone_number);
            }

            if (isset($service_address['alt_phone'])) {
                $phone_number['alias'] = 'Alternate';
                $phone_number['number'] = $service_address['alt_phone'];
                $phone_number['type'] = $service_address['alt_phone_type'] ?? 'Home';
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
            $contact_address['alt_phone_id'] = $alt_phone->id  ?? null;
            $contact_address['region_id'] = $service_address['region_id'] ?? null;
            $contact_address['active'] = true;
            $c_address = PocomosAddress::create($contact_address);

            if (!$same_as_service_address && $same_as_service_address != null) {
                $billing_address['street'] = $billing_information['billing_street'] ?? '';
                $billing_address['suite'] = $billing_information['billing_suite'] ?? '';
                $billing_address['city'] = $billing_information['billing_city'] ?? '';
                $billing_address['postal_code'] = $billing_information['billing_postal'] ?? '';
                $billing_address['validated'] = 2;
                $billing_address['valid'] = 1;
                $billing_address['phone_id'] = ($phone ? $phone->id ?? null : null);
                $billing_address['alt_phone_id'] = $alt_phone->id  ?? null;
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
                $billing_address['alt_phone_id'] =  $alt_phone->id  ?? null;
                $billing_address['region_id'] = $service_address['region_id'] ?? null;
                $billing_address['active'] = true;
                $b_address = PocomosAddress::create($billing_address);
            }

            $input_details['contact_address_id'] = ($c_address ? $c_address->id ?? null : null);
            $input_details['billing_address_id'] = ($b_address ? $b_address->id ?? null : null);
            $input_details['subscribed'] = $subscribe_to_mailing_list;
            $input_details['billing_name'] = $billing_information['billing_name'] ?? null;
            $input_details['status'] = $billing_information['sales_status_id'] ?? '1';
            $input_details['external_account_id'] = '';
            $input_details['default_job_duration'] = $scheduling_information['job_duration'] ?? null;

            if (isset($billing_information['is_enroll_auto_pay']) && $billing_information['is_enroll_auto_pay']) {
                $account_details['ip_address'] = '';
                $account_details['alias'] = $billing_information['alias'] ?? null;
                $account_details['name'] = ($service_address['first_name'] ?? '') . ' ' . ($service_address['last_name'] ?? '');
                $account_details['address'] = $billing_information['billing_street'] ?? '' . ', ' . $billing_information['billing_suite'] ?? '' . ', ' . $billing_information['billing_city'] ?? '' . ', ' . $billing_information['billing_postal'] ?? '';
                $account_details['city'] = $service_address['city'] ?? '';
                $account_details['region'] = $billing_information['region'] ?? '';
                $account_details['country'] = $billing_information['country'] ?? null;
                $account_details['postal_code'] = $billing_information['billing_postal'] ?? '';
                $account_details['phoneNumber'] = $service_address['phone'];
                $account_details['active'] = true;
                $account_details['account_number'] = $billing_information['account_number'] ?? null;
                $account_details['type'] = 'CardAccount';
                $account_details['card_exp_month'] = $billing_information['exp_month'] ?? null;
                $account_details['card_exp_year'] = $billing_information['exp_year'];
                $account_details['card_cvv'] = $billing_information['cvv'];
                $account_details['email_address'] = $service_address['email'] ?? '';
                $account_details['external_person_id'] = '';
                $account_details['external_account_id'] = '';
                $account_details['account_type'] = '';
                $account_details['last_four'] = $billing_information['last_four'] ?? null;
                $autopay_account = OrkestraAccount::create($account_details);
            }

            $other_account_details['ip_address'] = '';
            $other_account_details['alias'] = 'External account';
            $other_account_details['name'] = ($service_address['first_name'] ?? '') . ' ' . ($service_address['last_name'] ?? '');
            $other_account_details['address'] = $billing_information['billing_street'] ?? '' . ', ' . $contact_address['suite'] . ', ' . $contact_address['city'] . ', ' . $contact_address['postal_code'];
            $other_account_details['city'] = $service_address['city'] ?? '';
            $other_account_details['region'] = $billing_information['region'] ?? '';
            $other_account_details['country'] = $billing_information['country'] ?? '';
            $other_account_details['postal_code'] = $contact_address['postal_code'];
            $other_account_details['phoneNumber'] = $service_address['phone'];
            $other_account_details['active'] = true;
            $other_account_details['account_number'] = $billing_information['account_number'] ?? '';
            $other_account_details['type'] = 'SimpleAccount';
            $other_account_details['ach_routing_number'] = null;
            $other_account_details['account_type'] = null;
            $other_account_details['email_address'] = $service_address['email'] ?? '';
            $other_account_details['external_person_id'] = '';
            $other_account_details['external_account_id'] = '';
            $external_account = OrkestraAccount::create($other_account_details);

            $other_account_details['alias'] = 'Cash or check';
            $other_account = OrkestraAccount::create($other_account_details);

            $other_account_details['alias'] = 'Account credit';
            $other_account_details['type'] = 'PointsAccount';
            $point_account = OrkestraAccount::create($other_account_details);

            $sales_profile['autopay'] = true;
            $customer = PocomosCustomer::create($input_details);

            $office = PocomosCompanyOffice::with('coontact_address')->findorfail($service_information['office_id']);
            //$creationResult = $this->convertCustomerToEntity(null, $customer, $office);

            // create child account pocomos_sub_customers
            $PocomosSubCustomer = [];
            $PocomosSubCustomer['parent_id'] = $request->customer_id;
            $PocomosSubCustomer['child_id'] = $customer->id;
            $PocomosSubCustomer['active'] = true;
            $sub_customer = PocomosSubCustomer::create($PocomosSubCustomer);

            $sales_profile['points_account_id'] = $point_account->id ?? null;
            $sales_profile['autopay_account_id'] = $autopay_account->id ?? null;
            $sales_profile['external_account_id'] = $external_account->id ?? null;
            $sales_profile['customer_id'] = $customer->id;
            $sales_profile['office_id'] = $service_information['office_id'] ?? null;
            $sales_profile['salesperson_id'] = $billing_information['sales_person_id'] ?? null;
            $sales_profile['active'] = true;
            $sales_profile['office_user_id'] = null;
            $sales_profile['date_signed_up'] = date('Y-m-d H:i:s');
            $sales_profile['imported'] = false;
            $sales_profile['balance'] = 0.00;

            $sales_profile_data = PocomosCustomerSalesProfile::create($sales_profile);

            if (isset($service_address['phone'])) {
                $phone_num_input = [

                    'profile_id' => $sales_profile_data->id,
                    'phone_id' => $phone->id
                ];
                PocomosCustomersPhone::insert($phone_num_input);
            }

            if (isset($service_address['alt_phone'])) {
                $phone_num_input = [
                    'profile_id' => $sales_profile_data->id,
                    'phone_id' => $alt_phone->id,
                ];
                PocomosCustomersPhone::insert($phone_num_input);
            }

            PocomosCustomersAccount::insert([
                'profile_id' => $sales_profile_data->id, 'account_id' => $autopay_account->id ?? null,
                'profile_id' => $sales_profile_data->id, 'account_id' => $external_account->id,
                'profile_id' => $sales_profile_data->id, 'account_id' => $point_account->id,
                'profile_id' => $sales_profile_data->id, 'account_id' => $other_account->id
            ]);

            $pest_agr =  PocomosPestAgreement::whereId($service_information['contract_type_id'])->first();

            if (isset($pest_agr)) {
                $pest_agreement_id = $pest_agr->id ?? null;
            } else {
                $pest_agreement['agreement_id'] = $pest_agr->agreement_id ?? null;
                $pest_agreement['service_frequencies'] = '';
                $pest_agreement['active'] = true;
                $pest_agreement['initial_duration'] = $scheduling_information['initial_job_duration'] ?? null;
                $pest_agreement['regular_duration'] = $scheduling_information['job_duration'] ?? null;
                $pest_agreement['one_month_followup'] = false;
                $pest_agreement['max_jobs'] = $service_information['num_of_jobs'] ?? 0;
                $pest_agreement['default_agreement'] = false;
                $agreement = PocomosPestAgreement::create($pest_agreement);
                $pest_agreement_id = $agreement->id ?? null;
            }

            // // $agreement_signature = $agreement_input['signature'] ?? null;
            // $signed = false;
            // if ($agreement_signature) {
            //     //store file into document folder
            //     $agreement_sign_detail['path'] = $agreement_signature->store('public/files');

            //     // $agreement_sign_detail['user_id'] = $or_user->id ?? null;
            //     //store your file into database
            //     $agreement_sign_detail['filename'] = $agreement_signature->getClientOriginalName();
            //     $agreement_sign_detail['mime_type'] = $agreement_signature->getMimeType();
            //     $agreement_sign_detail['file_size'] = $agreement_signature->getSize();
            //     $agreement_sign_detail['active'] = 1;
            //     $agreement_sign_detail['md5_hash'] =  md5_file($agreement_signature->getRealPath());
            //     $agreement_sign =  OrkestraFile::create($agreement_sign_detail);
            //     $signed = true;
            // }

            // // $autopay_signature = $agreement_input['signature'] ?? null;
            // if ($autopay_signature) {
            //     //store file into document folder
            //     $autopay_sig_detail['path'] = $autopay_signature->store('public/files');

            //     // $autopay_sig_detail['user_id'] = $or_user->id ?? null;
            //     //store your file into database
            //     $autopay_sig_detail['filename'] = $autopay_signature->getClientOriginalName();
            //     $autopay_sig_detail['mime_type'] = $autopay_signature->getMimeType();
            //     $autopay_sig_detail['file_size'] = $autopay_signature->getSize();
            //     $autopay_sig_detail['active'] = 1;
            //     $autopay_sig_detail['md5_hash'] =  md5_file($autopay_signature->getRealPath());
            //     // $autopay_sign =  OrkestraFile::create($autopay_sig_detail);
            // }

            $pocomos_contract['profile_id'] = $sales_profile_data->id;
            $pocomos_contract['agreement_id'] = $pest_agr->agreement_id ?? '';
            // $pocomos_contract['signature_id'] = $agreement_sign->id;
            $pocomos_contract['billing_frequency'] = '';
            $pocomos_contract['status'] = 'Active';
            $pocomos_contract['date_start'] = $service_information['contract_start_date'] ?? date('Y-m-d H:i:s');
            if (isset($service_information['contract_end_date'])) {
                $contract_end_date = $service_information['contract_end_date'];
            } else {
                $contract_end_date = date('Y-m-d', strtotime('+1 year'));
            }
            $pocomos_contract['date_end'] = $contract_end_date;
            $pocomos_contract['active'] = true;
            $pocomos_contract['salesperson_id'] = $billing_information['sales_person_id'] ?? null;;
            $pocomos_contract['auto_renew'] = $options['auto_renew'] ?? false;
            $pocomos_contract['tax_code_id'] = $additional_information['tax_code_id'] ?? null;

            if (isset($request->useParentTaxCode)) {
                if (isset($request->parentContracts)) {
                    $contracts = PocomosContract::findOrFail($request->parentContracts);
                    $pocomos_contract['tax_code_id'] = $contracts['tax_code_id'];
                }
            }

            $pocomos_contract['signed'] = 0;
            // $pocomos_contract['autopay_signature_id'] = $agreement_sign->id;
            $pocomos_contract['sales_tax'] = $additional_information['sales_tax'] ?? 0.0;

            if (isset($service_information['billing_frequency']) &&  in_array($service_information['billing_frequency'], ['Installments'])) {
                $pocomos_contract['number_of_payments'] = $pricing_information['number_of_payments'] ?? 0;
                $pocomos_contract['renew_installment_initial_price'] = $pricing_information['renew_installment_initial_price'] ?? 0;
                $pocomos_contract['renew_installment_start_date'] = $pricing_information['renew_installment_start_date'] ?? 0;
                $pocomos_contract['renew_number_of_payment'] = $pricing_information['renew_number_of_payments'] ?? 0;
                $pocomos_contract['renew_installment_frequency'] = $pricing_information['renew_installment_frequency'] ?? 0;
                $pocomos_contract['renew_installment_price'] = $pricing_information['renew_installment_price'] ?? 0;
            }

            $cus_contract = PocomosContract::create($pocomos_contract);
            // $files_input = [
            //     [
            //         'customer_id' => $customer->id,
            //         // 'file_id' => $agreement_sign->id
            //     ]
            // ];
            // PocomosCustomersFile::insert($files_input);

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
            $pest_contract['week_of_the_month'] = $service_information['week_of_the_month'] ?? null;
            $pest_contract['day_of_the_week'] = $service_information['day_of_the_week'] ?? null;
            $pest_contract['date_renewal_end'] = date('Y-m-d', strtotime('+2 year'));
            $pest_contract['preferred_time'] = $service_information['preferred_time'] ?? null;
            $pest_contract['county_id'] = $service_address['county_id'] ?? null;
            $pest_contract['technician_id'] = $scheduling_information['technician_id'];
            // $pest_contract['renew_initial_job'] = '';
            $pest_contract['number_of_jobs'] = $service_information['num_of_jobs'] ?? 0;
            $pest_contract['map_code'] = $service_information['map_code'] ?? '';
            $pest_contract['addendum'] = $agreement_input['addendum'] ?? null;
            $pest_contract['week_of_the_month'] = '';
            $pest_contract['day_of_the_week'] = '';
            $pest_contract_res = PocomosPestContract::create($pest_contract);

            // foreach ($tags as $value) {
            //     PocomosPestContractsTag::create(['contract_id' => $pest_contract_res->id, 'tag_id' => $value]);
            // }

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

            // foreach ($targeted_pests as $value) {
            //     PocomosPestContractsPest::create(['contract_id' => $pest_contract_res->id, 'pest_id' => $value]);
            // }

            // foreach ($specialty_pests as $value) {
            //     PocomosPestContractsPest::create(['contract_id' => $pest_contract_res->id, 'pest_id' => $value]);
            // }

            $customer_state['customer_id'] = $customer->id;
            $customer_state['next_service_date'] = date('Y-m-d H:i:s');
            $customer_state['active'] = true;
            $customer_state['balance_overall'] = 0.0;
            $customer_state['balance_outstanding'] = 0.0;
            $customer_state['balance_credit'] = 0.0;
            $customer_state['days_past_due'] = 0;
            $customer_state['card_on_file'] = 0;
            PocomosCustomerState::create($customer_state);

            $service_schedule = $service_information['service_schedule'] ?? array();

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
                $input['technician_id'] = $scheduling_information['technician_id'] ?? '';
                PocomosJob::create($input);

                $i = $i + 1;
            }

            DB::commit();
            $status = true;
            $message = __('strings.create', ['name' => 'Location']);
            $res['customer_id'] = $customer->id;
        } catch (\Exception $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }
        return $this->sendResponse($status, $message, $res);
    }

    /**
     * API for List parent Contracts
   .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function parentContracts(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        $parentContracts = DB::select(DB::raw("SELECT pa.name , pco.contract_id as 'contract_id' , sco.status as 'status', st.name AS 'service_type_name'
                    FROM pocomos_pest_contracts as pco
                    JOIN pocomos_contracts as sco ON sco.id = pco.contract_id
                    JOIN pocomos_agreements AS pa ON  pa.id =sco.agreement_id
                    JOIN pocomos_customer_sales_profiles as p ON p.id = sco.profile_id
                    JOIN pocomos_customers as pc ON pc.id = p.customer_id
                    LEFT JOIN pocomos_pest_contract_service_types st ON st.id  = pco.service_type_id
                    WHERE pc.id= " . $request->customer_id . " and  sco.status!= 'Cancelled'"));

        return $this->sendResponse(true, 'parentContracts', $parentContracts);
    }

    /**
     * API for List Location
   .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listLocation(Request $request)
    {
        $v = validator($request->all(), [
            'parent_id' => 'required|exists:pocomos_customers,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        $find_sub_customer_id = PocomosSubCustomer::where('parent_id', $request->parent_id)->pluck('child_id')->toArray();

        $val = $this->convertArrayInStrings($find_sub_customer_id);

        $sql = "SELECT *,pc.id as cust_id,pa.*
        FROM pocomos_customers AS pc
        LEFT JOIN pocomos_addresses AS pa ON pa.id = pc.contact_address_id
        WHERE pc.id IN ($val)";

        if ($request->search) {
            $search = "'%" . $request->search . "%'";
            $sql .= ' AND (pc.first_name LIKE ' . $search . ' OR pc.last_name LIKE ' . $search . ' OR pc.status LIKE ' . $search . ' OR pc.date_created LIKE ' . $search . ' OR pa.city LIKE ' . $search . ' OR pa.postal_code LIKE ' . $search . ' OR pa.suite LIKE ' . $search . ') OR pa.street LIKE ' . $search . '';
        }

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $batches = DB::select(DB::raw(($sql)));

        return $this->sendResponse(true, 'List', [
            'Location' => $batches,
            'count' => $count,
        ]);
    }
}
