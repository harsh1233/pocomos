<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosPestContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosLead;
use Illuminate\Support\Facades\Mail;
use App\Models\Pocomos\PocomosFormLetter;
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosEmail;

class SendMassEmailLeadJob implements ShouldQueue
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
        Log::info("SendMassEmailLeadJob Job Started");

        $leadIds = $this->args['leadIds'];

        $leads = PocomosLead::whereIn('id', $leadIds)->get();

        $this->EmailAction($leads, $this->args);

        Log::info("SendMassEmailLeadJob Job End");
    }

    /**
     */
    private function EmailAction($leadIds, $args)
    {
        foreach ($leadIds as $lead) {
            $office = PocomosCompanyOffice::findOrFail($this->args['officeId']);
            $office_email = unserialize($office->email);

            if (isset($office_email[0])) {
                $from = $office_email[0];
            } else {
                throw new \Exception(__('strings.something_went_wrong'));
            }
            $customerEmail = $lead->email;
            if (filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $agreement_body = $this->sendLeadFormLetter($this->args['letterId'], $lead);
                $formLetter = PocomosFormLetter::findOrFail($this->args['letterId']);

                Mail::send('emails.dynamic_email_render', compact('agreement_body'), function ($message) use ($formLetter, $customerEmail, $from) {
                    $message->from($from);
                    $message->to($customerEmail);
                    $message->subject($formLetter['subject']);
                });

                $email_input['office_id'] = $office->id;
                $email_input['office_user_id'] = Session::get(config('constants.ACTIVE_OFFICE_USER_ID')) ?? null;
                $email_input['lead_id'] = $lead->id;
                $email_input['type'] = 'Welcome Email';
                $email_input['body'] = $agreement_body;
                $email_input['subject'] = $formLetter['subject'];
                $email_input['reply_to'] = $from;
                $email_input['reply_to_name'] = $office->name ?? '';
                $email_input['sender'] = $from;
                $email_input['sender_name'] = $office->name ?? '';
                $email_input['active'] = true;
                PocomosEmail::create($email_input);
            }
        }
    }
}
