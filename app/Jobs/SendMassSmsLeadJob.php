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
use App\Models\Pocomos\PocomosSmsFormLetter;
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosEmail;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosCustomersNotifyMobilePhone;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosAddress;

class SendMassSmsLeadJob implements ShouldQueue
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
        Log::info("SendMassSmsLeadJob Job Started");

        $leadIds = $this->args['leadIds'];

        $leads = PocomosLead::whereIn('id', $leadIds)->get();

        $this->EmailAction($leads, $this->args);

        Log::info("SendMassSmsLeadJob Job End");
    }

    /**
     */
    private function EmailAction($leadIds, $args)
    {
        foreach ($leadIds as $lead) {
            $leads = PocomosLead::find($lead->id);

            $letter = PocomosSmsFormLetter::find($this->args['letterId']);

            $officeUser = PocomosCompanyOfficeUser::whereOfficeId($this->args['officeId'])->whereUserId(auth()->user()->id)->first();

            $PocomosAddress = PocomosAddress::find($leads->contact_address_id);

            $number = PocomosPhoneNumber::find($PocomosAddress->phone_id);

            $this->sendLeadSmsFormLetterByPhone($leads, $number, $letter, $officeUser);
        }
    }
}
