<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Support\Facades\Session;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosFormLetter;
use App\Models\Pocomos\PocomosEmailMessage;
use App\Models\Pocomos\PocomosPestContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class SendMassEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $args;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($args)
    {
        $this->args = $args;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("SendMassEmailJob Job Started");

        $customerIds = $this->args['customerIds'];

        $customers = PocomosCustomer::with('sales_profile.office_details')->whereIn('id', $customerIds)->get();

        $this->EmailAction($customers, $this->args);

        Log::info("SendMassEmailJob Job End");
    }

    /**
     */
    private function EmailAction($customerIds, $args)
    {
        foreach ($customerIds as $customer) {
            $office = PocomosCompanyOffice::findOrFail($this->args['officeId']);
            $office_email = unserialize($office->email);

            $officeId = auth()->user()->pocomos_company_office_user->office_id;
            $officeUser = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereUserId(auth()->user()->id)->first();

            if (isset($office_email[0])) {
                $from = $office_email[0];
            } else {
                throw new \Exception(__('strings.something_went_wrong'));
            }
            $customerEmail = $customer->email;
            if (filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $agreement_body = $this->sendFormLetter($this->args['letterId'], $customer);
                $profile = $customer->sales_profile;
                $formLetter = PocomosFormLetter::findOrFail($this->args['letterId']);

                Mail::send('emails.dynamic_email_render', compact('agreement_body'), function ($message) use ($formLetter, $customerEmail, $from) {
                    $message->from($from);
                    $message->to($customerEmail);
                    $message->subject($formLetter['subject']);
                });

                $email_input['office_id'] = $office->id;
                $email_input['office_user_id'] = $officeUser->id;
                $email_input['customer_sales_profile_id'] = $profile->id;
                $email_input['type'] = 'Welcome Email';
                $email_input['body'] = $agreement_body;
                $email_input['subject'] = $formLetter['subject'];
                $email_input['reply_to'] = $from;
                $email_input['reply_to_name'] = $office->name ?? '';
                $email_input['sender'] = $from;
                $email_input['sender_name'] = $office->name ?? '';
                $email_input['active'] = true;
                $email = PocomosEmail::create($email_input);

                $input['email_id'] = $email->id;
                $input['recipient'] = $customer->email;
                $input['recipient_name'] = $customer->first_name.' '.$customer->last_name;
                $input['date_status_changed'] = date('Y-m-d H:i:s');
                $input['status'] = 'Delivered';
                $input['external_id'] = '';
                $input['active'] = true;
                $input['office_user_id'] = $officeUser->id;
                PocomosEmailMessage::create($input);
            }
        }
    }
}
