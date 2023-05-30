<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosLead;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosEmail;
use Illuminate\Support\Facades\Mail;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosEmailMessage;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomerSalesProfile;

class EmailHistoryController extends Controller
{
    use Functions;

    /**
     * Lists all Emails related to this customers account
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function indexAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find Customer entity.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $sql = "SELECT e.id AS email_id,
                  m.id AS message_id,
                  CONCAT(u.first_name,' ',u.last_name) AS sending_user,
                  m.recipient,
                  m.recipient_name,
                  e.type,
                  e.date_created,
                  m.status,
                  date_status_changed
                FROM pocomos_email_messages m
                JOIN pocomos_emails e ON m.email_id = e.id
                LEFT JOIN pocomos_company_office_users AS cou ON cou.id = m.office_user_id
                LEFT JOIN orkestra_users AS u ON cou.user_id = u.id
                WHERE e.office_id = '$profile->office_id' AND e.customer_sales_profile_id = '$profile->id'";

        if ($request->search) {
            $search = "'%" . $request->search . "%'";
            $sql .= ' AND (e.id LIKE ' . $search . ' OR CONCAT(u.first_name, \' \', u.last_name) LIKE ' . $search . ' OR m.id LIKE ' . $search . '   OR m.recipient_name LIKE ' . $search . '  OR m.recipient LIKE ' . $search . ' OR e.type LIKE ' . $search . ' OR e.date_created LIKE ' . $search . ' OR m.status LIKE ' . $search . ' OR date_status_changed LIKE ' . $search . ')';
        }

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $batches = DB::select(DB::raw(($sql)));

        return $this->sendResponse(true, 'List', [
            'Email_History' => $batches,
            'count' => $count,
        ]);
    }

    /**
     * Shows a single email
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function showAction(Request $request)
    {
        $v = validator($request->all(), [
            'email_id' => 'required|exists:pocomos_emails,id',
            'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find Customer entity.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $results = PocomosEmail::where('id', $request->email_id)->where('office_id', $profile->office_id)->orderBy('date_created', 'DESC')->with('office_user_detail.user_details_name', 'receive_office_user_detail.user_details_name', 'office')->get();

        if (!$results) {
            return $this->sendResponse(false, 'Unable to find Email entity.');
        }

        return $this->sendResponse(true, 'Email Details', $results);
    }


    /**
     * Resends a specific email message
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function resendAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'messageid' => 'required|exists:pocomos_email_messages,id',
            'email_id' => 'required|exists:pocomos_emails,id',
            'sameRecipient' => 'required|boolean',
            'recipients' => 'array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);
        $office = $customer->sales_profile->office_details;

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find Customer entity.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $emailMsg = PocomosEmailMessage::findOrFail($request->messageid);
        $linkData = $this->getVerifyEmailLink($customer);
        
        $data = array(
            'customer' => $customer,
            'office' => $office,
            'id' => $customer->id,
            'hash' => $this->getEmailVerificationHash($customer),
            'html_link' => $linkData['html_link'] ?? '',
            'url' => $linkData['url'] ?? ''
        );

        $email = PocomosEmail::where('id', $request->email_id)->first();
        $subject = $email->subject;
        $recipient = $emailMsg->recipient;
        $sender = auth()->user()->email;

        if ($request->sameRecipient == 1) {
            $input['email_id'] = $emailMsg->email_id;
            $input['recipient'] = $emailMsg->recipient;
            $input['recipient_name'] = $emailMsg->recipient_name;
            $input['date_status_changed'] = $emailMsg->date_status_changed;
            $input['status'] = $emailMsg->status;
            $input['external_id'] = $emailMsg->external_id;
            $input['active'] = $emailMsg->active;
            $input['office_user_id'] = $emailMsg->office_user_id;

            $alert = PocomosEmailMessage::create($input);

            $results = PocomosEmail::where('id', $request->email_id)->where('office_id', $profile->office_id)->orderBy('date_created', 'DESC')->with('office_user_detail.user_details_name', 'receive_office_user_detail.user_details_name', 'office')->get();

            Mail::send('emails.verify_email', ['data' => $data], function ($message) use ($subject, $recipient, $sender) {
                $message->from($sender);
                $message->to($recipient);
                $message->subject($subject);
            });
        } else {
            foreach ($request->recipients as $values) {
                $input['email_id'] = $emailMsg->email_id;
                $input['recipient'] = $values['email'];
                $input['recipient_name'] = $values['name'];
                $input['date_status_changed'] = $emailMsg->date_status_changed;
                $input['status'] = $emailMsg->status;
                $input['external_id'] = $emailMsg->external_id;
                $input['active'] = $emailMsg->active;
                $input['office_user_id'] = $emailMsg->office_user_id;
                $alert = PocomosEmailMessage::create($input);

                Mail::send('emails.verify_email', ['data' => $data], function ($message) use ($subject, $recipient, $sender) {
                    $message->from($sender);
                    $message->to($recipient);
                    $message->subject($subject);
                });
            }
        }

        $results = DB::select(DB::raw("SELECT e.id AS email_id, m.id AS message_id, COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'System') AS sending_user, m.recipient, m.recipient_name, e.type, e.date_created, m.status, date_status_changed FROM pocomos_email_messages m JOIN pocomos_emails e ON m.email_id = e.id LEFT JOIN pocomos_company_office_users ou ON ou.id = COALESCE(m.office_user_id, e.office_user_id) LEFT JOIN orkestra_users u ON ou.user_id = u.id WHERE e.office_id = '$profile->office_id' AND e.customer_sales_profile_id = '$profile->id'"));

        return $this->sendResponse(true, 'The message has been resent to the selected recipients.', $results);
    }

    /**
     * Lists all Emails related to this customers account
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function leadindexAction(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosLead::findOrFail($request->lead_id);

        if (!$PocomosLead) {
            return $this->sendResponse(false, 'Unable to locate Lead');
        }

        $sql = "SELECT e.id AS email_id,
                  m.id AS message_id,
                  e.reply_to_name AS sending_user,
                  m.recipient,
                  m.recipient_name,
                  e.type,
                  e.date_created,
                  m.status,
                  m.date_status_changed
                FROM pocomos_email_messages m
                  JOIN pocomos_emails e ON m.email_id = e.id
                  WHERE e.office_id = '$request->office_id'
                  AND e.lead_id = '$request->lead_id'";

        if ($request->search) {
            $search = "'%" . $request->search . "%'";
            $sql .= ' AND (e.id LIKE ' . $search . ' OR m.recipient LIKE ' . $search . ' OR e.type LIKE ' . $search . ' OR e.date_created LIKE ' . $search . ' OR m.status LIKE ' . $search . ' OR m.date_status_changed LIKE ' . $search . ')';
        }

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $batches = DB::select(DB::raw(($sql)));

        return $this->sendResponse(true, 'List', [
            'Email_History' => $batches,
            'count' => $count,
        ]);
    }

    /**
     * Shows a single email
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function leadshowAction(Request $request)
    {
        $v = validator($request->all(), [
            'email_id' => 'required|exists:pocomos_emails,id',
            'lead_id' => 'required|exists:pocomos_leads,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosLead::findOrFail($request->lead_id);

        if (!$PocomosLead) {
            return $this->sendResponse(false, 'Unable to find Lead entity.');
        }

        $results = PocomosEmail::where('id', $request->email_id)->where('office_id', $request->office_id)->orderBy('date_created', 'DESC')->with('office_user_detail.user_details_name', 'receive_office_user_detail.user_details_name', 'office')->get();

        if (!$results) {
            return $this->sendResponse(false, 'Unable to find Email entity.');
        }

        return $this->sendResponse(true, 'Email Details', $results);
    }


    /**
     * Resends a specific email message
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function leadresendAction(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'messageid' => 'required|exists:pocomos_email_messages,id',
            'email_id' => 'required|exists:pocomos_emails,id',
            'sameRecipient' => 'required|boolean',
            'recipients' => 'array',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosLead::findOrFail($request->lead_id);

        if (!$PocomosLead) {
            return $this->sendResponse(false, 'Unable to find Lead entity.');
        }

        $email = PocomosEmail::findOrFail($request->email_id);
        $recipient = PocomosEmailMessage::findOrFail($request->messageid);

        $office = PocomosCompanyOffice::findOrFail($request->office_id);
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->whereUserId(auth()->user()->id)->first();
        $from = $this->getOfficeEmail($request->office_id);
        $subject = $email->subject;
        $recipient_email = $recipient->recipient;

        if ($request->sameRecipient == 1) {
            $email_input['office_id'] = $office->id;
            $email_input['office_user_id'] = $officeUser->id;
            $email_input['customer_sales_profile_id'] = null;
            $email_input['type'] = 'Lead email';
            $email_input['body'] = $email->body;
            $email_input['subject'] = $email->subject;
            $email_input['reply_to'] = $from;
            $email_input['reply_to_name'] = $office->name ?? '';
            $email_input['sender'] = $from;
            $email_input['sender_name'] = $office->name ?? '';
            $email_input['active'] = true;
            $email_input['lead_id'] = $request->lead_id;
            $email = PocomosEmail::create($email_input);

            $input['email_id'] = $email->id;
            $input['recipient'] = $recipient->recipient;
            $input['recipient_name'] = $recipient->recipient_name;
            $input['date_status_changed'] = $recipient->date_status_changed;
            $input['status'] = $recipient->status;
            $input['external_id'] = $recipient->external_id;
            $input['active'] = $recipient->active;
            $input['office_user_id'] = $recipient->office_user_id;
            PocomosEmailMessage::create($input);
            $template = $email->body;

            Mail::send('pdf.dynamic_render', compact('template'), function ($message) use ($subject, $recipient_email, $from) {
                $message->from($from);
                $message->to($recipient_email);
                $message->subject($subject);
            });
        } else {
            foreach ($request->recipients as $values) {
                $email_input['office_id'] = $office->id;
                $email_input['office_user_id'] = $officeUser->id;
                $email_input['customer_sales_profile_id'] = null;
                $email_input['type'] = 'Lead email';
                $email_input['body'] = $email->body;
                $email_input['subject'] = $email->subject;
                $email_input['reply_to'] = $from;
                $email_input['reply_to_name'] = $office->name ?? '';
                $email_input['sender'] = $from;
                $email_input['sender_name'] = $office->name ?? '';
                $email_input['active'] = true;
                $email_input['lead_id'] = $request->lead_id;
                $email = PocomosEmail::create($email_input);

                $input['email_id'] = $email->id;
                $input['recipient'] = $values['email'];
                $input['recipient_name'] = $values['name'];
                $input['date_status_changed'] = $recipient->date_status_changed;
                $input['status'] = $recipient->status;
                $input['external_id'] = $recipient->external_id;
                $input['active'] = $recipient->active;
                $input['office_user_id'] = $recipient->office_user_id;
                PocomosEmailMessage::create($input);

                $recipient_email = $values['email'];
                $template = $email->body;
                
                Mail::send('pdf.dynamic_render', compact('template'), function ($message) use ($subject, $recipient_email, $from) {
                    $message->from($from);
                    $message->to($recipient_email);
                    $message->subject($subject);
                });
            }
        }

        $results = DB::select(DB::raw("SELECT e.id AS email_id,
                  m.id AS message_id,
                  e.reply_to_name AS sending_user,
                  m.recipient,
                  m.recipient_name,
                  e.type,
                  e.date_created,
                  m.status,
                  date_status_changed
                FROM pocomos_email_messages m
                  JOIN pocomos_emails e ON m.email_id = e.id
                  WHERE e.office_id = '$request->office_id'
                  AND e.lead_id = '$request->lead_id'"));

        return $this->sendResponse(true, 'The message has been resent to the selected recipients.', $results);
    }
}
