<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Twilio\Rest\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosLead;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosSmsUsage;
use Twilio\Rest\Client as TwillioClient;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosSmsFormLetter;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Pocomos\PocomosCustomersNotifyMobilePhone;

class SMSController extends Controller
{
    use Functions;

    /**
     * API for send sms
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function sendSMS(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'message' => 'required',
            'phone_id' => 'required|exists:pocomos_phone_numbers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            // 'user_id' => 'required|exists:pocomos_company_office_users,id',
            'job_id' => 'nullable|exists:pocomos_jobs,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        try {
            DB::beginTransaction();

            $customer = PocomosCustomer::find($request->customer_id);
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

            $job = null;
            if ($request->job_id) {
                $job = $this->findJobByIdAndOffice($request->job_id, $request->office_id);
            }
            $phone = PocomosPhoneNumber::find($request->phone_id);
            $currentUser = auth()->user()->id;
            $officeUser = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->whereUserId(auth()->user()->id)->first();

            $this->sendSmsFormLetterByPhone($customer, $phone, $letter, $officeUser, (array)$job);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        // OLD FLOW
        // $accountSid = env('TWILIO_ACCOUNT_SID');
        // $authToken  = env('TWILIO_AUTH_TOKEN');

        // $client = new Client($accountSid, $authToken);

        // $find_office_phone_number = PocomosOfficeSetting::where('office_id', $request->office_id)->with('sender_phone_details')->first();
        // if (!$find_office_phone_number) {
        //     return $this->sendResponse(false, 'Unable to find the sender number');
        // }

        // // $find_lead_number_id = PocomosPhoneNumber::where('number', substr($request->phone_number, 2))->firstOrFail();

        // // if ($find_lead_number_id) {
        // //     $input['phone_id'] = $find_lead_number_id->id;
        // // }

        // $input = [];
        // $input['office_id'] = $request->office_id;
        // $input['phone_id'] = $request->phone_id;
        // $input['message_part'] = $request->message;
        // $input['sender_phone_id'] = '299636';
        // $input['office_user_id'] = $request->user_id;
        // $input['inbound'] = '0';
        // $input['answered'] = '0';
        // $input['seen'] = '0';
        // $input['active'] = '1';

        // $twilio_number = config('constants.TWILLIO_NUMBER');

        // try {
        //     // Use the client to do fun stuff like send text messages!
        //     $message =    $client->messages->create(
        //         // the number you'd like to send the message to
        //         '+18044064234',
        //         array(
        //             // A Twilio phone number you purchased at twilio.com/console
        //             'from' => $twilio_number,
        //             // the body of the text message you'd like to send
        //             'body' => $request->message
        //         )
        //     );
        //     $status = true;
        //     $message = __('strings.sucess', ['name' => 'Message sended']);
        // } catch (\Exception $e) {
        //     $status = false;
        //     $message = $e->getMessage();
        // }

        // $message_create = PocomosSmsUsage::create($input);
        // END OLD FLOW

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Message sended']));
    }

    public function sendTextMsg(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'recipient' => 'required',
            'message' => 'required',
            'phone_id' => 'required|exists:pocomos_phone_numbers,id',
            // 'office_users' => 'required',
            // 'user_id' => 'required|exists:pocomos_company_office_users,id',
            // 'job_id' => 'nullable|exists:pocomos_jobs,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office = PocomosCompanyOffice::with('coontact_address')->findOrFail($request->office_id);

        $customer = PocomosCustomer::findOrFail($custId);

        $profile = PocomosCustomerSalesProfile::where('customer_id', $custId)->firstorfail();

        $phone = PocomosPhoneNumber::findorfail($request->phone_id);

        $recipient = $request->recipient;
        $cou = auth()->user()->pocomos_company_office_user;
        $msg = $request->message;

        if ($recipient == 'employee') {
            $msg = trim(strip_tags($msg));
            $msg = trim(str_replace('<br/>', ' ', $msg));
            $msg = str_replace("<br />", ' ', $msg);
            $msg = str_replace("\n", ' ', $msg);
            $msg = trim(str_replace('=', '', $msg));

            if ($msg) {
                $this->sendMessage($office, $phone, $msg, $cou);
            }

            $officeUsers = $request->office_users;

            if ($officeUsers) {
                // return 11;
                foreach ($officeUsers as $user) {
                    $user = PocomosCompanyOfficeUser::with('profile_details.phone_details')->findorfail($user);
                    $phone = isset($user->profile_details->phone_details) ? $user->profile_details->phone_details : null;

                    if ($phone) {
                        if ($msg) {
                            $this->sendMessage($office, $phone, $msg, $cou);
                        }
                    }
                }
            }
        } else {
            $this->sendMessage($office, $phone, $msg, $cou);
        }

        return $this->sendResponse(true, 'Text message successfully sent.');
    }


    public function textEmployees(Request $request, $custId)
    {
        $v = validator($request->all(), [
            'message' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'office_users' => 'required|array|exists:pocomos_company_office_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $office = PocomosCompanyOffice::with('coontact_address')->findOrFail($request->office_id);

        $msg = $request->message;

        $msg = trim(strip_tags($msg));
        $msg = trim(str_replace('<br/>', ' ', $msg));
        $msg = str_replace("<br />", ' ', $msg);
        $msg = str_replace("\n", ' ', $msg);
        $msg = trim(str_replace('=', '', $msg));

        $officeUsers = $request->office_users;

        foreach ($officeUsers as $user) {
            $user = PocomosCompanyOfficeUser::with('profile_details.phone_details')->findorfail($user);
            $phone = isset($user->profile_details->phone_details) ? $user->profile_details->phone_details : null;

            if ($phone) {
                if ($msg) {
                    $this->sendMessage($office, $phone, $msg, auth()->user()->pocomos_company_office_user->id);
                }
            }
        }
        return $this->sendResponse(true, 'Text message successfully sent..');
    }

    /**
     * API for SMS History
   .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function SmsHistory(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'phone_id' => 'required|exists:pocomos_phone_numbers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find Customer entity.');
        }

        $find_messages = PocomosSmsUsage::where('office_id', $request->office_id)->where('phone_id', $request->phone_id)->orderBy('date_created', 'ASC')->with('office', 'office_user_detail.user_details_name')->get();

        return $this->sendResponse(true, 'Message List.', $find_messages);

        // $find_sales_profile_id = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();
        // if (!$find_sales_profile_id) {
        //     return $this->sendResponse(false, 'Customer profile not found.');
        // }

        // $find_phone_ids = PocomosCustomersNotifyMobilePhone::where('profile_id', $find_sales_profile_id->id)->pluck('phone_id')->toArray();

        // $find_lead_number_id = PocomosLead::where('id', $request->lead_id)->with('addresses')->first();
        // if ($find_phone_ids) {
        //     $find_messages = PocomosSmsUsage::where('office_id', $request->office_id)->whereIn('phone_id', $find_phone_ids)->orderBy('date_created', 'DESC')->get();

        //     $find_messages->map(function ($message) {
        //         $find_user_data = PocomosAddress::where('phone_id', $message->phone_id)->with('address_details')->first();
        //         $message->user_data = $find_user_data;
        //     });
        // }
    }

    /**
     * API for List of phone numbers for sms
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listNumber(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_sales_profile_id = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();
        if (!$find_sales_profile_id) {
            return $this->sendResponse(false, 'Customer profile not found.');
        }

        $find_phone_ids = PocomosCustomersNotifyMobilePhone::where('profile_id', $find_sales_profile_id->id)->pluck('phone_id')->toArray();

        $phoner_numbers = PocomosPhoneNumber::whereIn('id', $find_phone_ids)->get();

        return $this->sendResponse(true, 'List of phone numbers', $phoner_numbers);
    }

    public function customerPhoneNumbers($custId)
    {
        $phoner_numbers = PocomosCustomerSalesProfile::with(['phone_numbers.phone' => function ($q) {
            $q->whereActive(1)
                ->whereType('Mobile');
        }])
            ->where('customer_id', $custId)->firstorfail();

        return $this->sendResponse(true, 'List of phone numbers', $phoner_numbers);
    }

    public function employees(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $roles = [
            'ROLE_ADMIN',
            'ROLE_OWNER',
            'ROLE_BRANCH_MANAGER',
            'ROLE_SECRETARY',
            'ROLE_SALES_MANAGER',
            'ROLE_SALES_ADMIN',
            'ROLE_SALESPERSON',
            'ROLE_ROUTE_MANAGER',
            'ROLE_TECHNICIAN',
            'ROLE_COLLECTIONS',
        ];

        $users = PocomosCompanyOfficeUser::with('profile_details.phone_details')
            ->select('*', 'pocomos_company_office_users.id')
            ->join('orkestra_users as ou', 'pocomos_company_office_users.user_id', 'ou.id')
            ->join('orkestra_user_groups as oug', 'ou.id', 'oug.user_id')
            ->join('orkestra_groups as og', 'oug.group_id', 'og.id')
            ->where('og.role', '!=', 'ROLE_CUSTOMER')
            ->where('pocomos_company_office_users.office_id', $request->office_id)
            ->where('pocomos_company_office_users.active', 1)
            ->where('ou.active', 1)
            ->whereIn('og.role', $roles)
            ->groupBy('pocomos_company_office_users.id')
            ->orderBy('ou.first_name')
            ->orderBy('ou.last_name')
            ->get();

        return $this->sendResponse(true, 'Employees', $users);
    }

    /**
     * API for Lead SMS History
   .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function leadSmsHistory(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'lead_id' => 'required|exists:pocomos_leads,id',
            'phone_id' => 'required|exists:pocomos_phone_numbers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosLead::findOrFail($request->lead_id);

        if (!$PocomosLead) {
            return $this->sendResponse(false, 'Unable to locate Lead');
        }

        $find_messages = PocomosSmsUsage::where('office_id', $request->office_id)->where('phone_id', $request->phone_id)->orderBy('id', 'ASC')->with('office', 'office_user_detail.user_details_name')->get();

        return $this->sendResponse(true, 'Message List.', $find_messages);
    }



    /**
     * API for List of phone numbers for  lead sms
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function leadlistNumber(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosLead::findOrFail($request->lead_id);

        if (!$PocomosLead) {
            return $this->sendResponse(false, 'Unable to locate Lead');
        }

        $find_phone_ids = PocomosAddress::where('id', $PocomosLead->contact_address_id)->pluck('phone_id')->toArray();
        $find_alt_phone_ids = PocomosAddress::where('id', $PocomosLead->contact_address_id)->pluck('alt_phone_id')->toArray();

        $id = array_merge($find_phone_ids, $find_alt_phone_ids);

        $phoner_numbers = PocomosPhoneNumber::whereIn('id', $id)->get();

        return $this->sendResponse(true, 'List of phone numbers', $phoner_numbers);
    }


    /**
     * API for send sms in lead
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function leadSendSMSBkp(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'message' => 'required',
            'phone_id' => 'required|exists:pocomos_phone_numbers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'user_id' => 'required|exists:pocomos_company_office_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosLead::findOrFail($request->lead_id);

        $accountSid = env('TWILIO_ACCOUNT_SID');
        $authToken  = env('TWILIO_AUTH_TOKEN');

        $client = new TwillioClient($accountSid, $authToken);

        $find_office_phone_number = PocomosOfficeSetting::where('office_id', $request->office_id)->with('sender_phone_details')->first();
        if (!$find_office_phone_number) {
            return $this->sendResponse(false, 'Unable to find the sender number');
        }

        $phone = PocomosPhoneNumber::findOrFail($request->phone_id);

        $input = [];
        $input['office_id'] = $request->office_id;
        $input['phone_id'] = $request->phone_id;
        $input['message_part'] = $request->message;
        $input['sender_phone_id'] = '299636';
        $input['office_user_id'] = $request->user_id;
        $input['inbound'] = '0';
        $input['answered'] = '0';
        $input['seen'] = '0';
        $input['active'] = '1';

        $twilio_number = config('constants.TWILLIO_NUMBER');

        try {
            // Use the client to do fun stuff like send text messages!
            $message =    $client->messages->create(
                // the number you'd like to send the message to
                $phone->number,
                array(
                    // A Twilio phone number you purchased at twilio.com/console
                    'from' => $twilio_number,
                    // the body of the text message you'd like to send
                    'body' => $request->message
                )
            );
            $status = true;
            $message = __('strings.sucess', ['name' => 'Message sended']);
        } catch (\Exception $e) {
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        PocomosSmsUsage::create($input);
        return $this->sendResponse($status, $message);
    }

    public function leadSendSMS(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'message' => 'required',
            'phone_id' => 'required|exists:pocomos_phone_numbers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'user_id' => 'nullable|exists:pocomos_company_office_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        try {
            DB::beginTransaction();

            $recipient = $request->recipient;

            $lead = PocomosLead::find($request->lead_id);
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

            $officeUser = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->whereUserId(auth()->user()->id)->first();

            $this->sendLeadSmsFormLetterByPhone($lead, $phone, $letter, $officeUser);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }
        
        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Message sended']));
    }

    /**Get customer jobs */
    public function customerAssociatedJobs(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sql = "SELECT j.*
        FROM pocomos_jobs AS j
        JOIN pocomos_pest_contracts AS pcc ON j.contract_id = pcc.id
        JOIN pocomos_contracts AS c ON pcc.contract_id = c.id
        JOIN pocomos_customer_sales_profiles AS p ON c.profile_id = p.id
        WHERE p.customer_id = $request->customer_id AND j.date_scheduled >= '" . date('Y-m-d') . "' AND j.status != '" . config('constants.CANCELLED') . "'
        ORDER BY j.date_scheduled ASC";

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $sql .= " LIMIT $perPage offset $page";
        }
        /**End */

        $data = DB::select(DB::raw($sql));

        $data = [
            "jobs" => $data,
            "count" => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Customer jobs']), $data);
    }
}
