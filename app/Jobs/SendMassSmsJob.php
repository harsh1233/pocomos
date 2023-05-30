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
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Support\Facades\Mail;
use App\Models\Pocomos\PocomosSmsFormLetter;
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosEmail;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosCustomersNotifyMobilePhone;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosCompanyOfficeUser;

class SendMassSmsJob implements ShouldQueue
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
        Log::info("SendMassSmsJob Job Started");

        $customerIds = $this->args['customerIds'];

        $customers = PocomosCustomer::with('sales_profile.office_details')->whereIn('id', $customerIds)->get();

        $this->EmailAction($customers, $this->args);

        Log::info("SendMassSmsJob Job End");
    }

    /**
     */
    private function EmailAction($customerIds, $args)
    {
        foreach ($customerIds as $customer) {
            $customerrs = PocomosCustomer::find($customer->id);

            $letter = PocomosSmsFormLetter::find($this->args['letterId']);

            $officeUser = PocomosCompanyOfficeUser::whereOfficeId($this->args['officeId'])->whereUserId(auth()->user()->id)->first();
            $customerProfile = PocomosCustomerSalesProfile::where('customer_id', $customer->id)->firstOrFail();

            $NotifyMobilePhone = PocomosCustomersNotifyMobilePhone::where('profile_id', $customerProfile->id)->get();

            foreach ($NotifyMobilePhone as $phone) {
                $number = PocomosPhoneNumber::find($phone->phone_id);

                $this->sendSmsFormLetterByPhone($customerrs, $number, $letter, $officeUser);
            }
        }
    }
}
