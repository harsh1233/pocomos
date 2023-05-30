<?php

namespace App\Jobs;

use DB;
use Illuminate\Bus\Queueable;
use App\Models\Pocomos\PocomosJob;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosEmail;
use Illuminate\Support\Facades\Mail;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosInvoice;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Session;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosPestContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosEmailsAttachedFile;
use App\Models\Pocomos\PocomosCustomerSalesProfile;

class ResendEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $args;
    public $profile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($args)
    {
        $this->args = $args;
        $this->profile = PocomosCustomerSalesProfile::findOrFail($args['cspId']);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("ResendEmailJob Job Started");
        try {
            switch ($this->args['type']) {
                case config('constants.VERIFICATION'):
                    $this->generateVerificationEmail($this->profile->customer_details, $this->args['officeUserId']);

                    break;

                case config('constants.INVOICES'):
                    $office = $this->profile->office_details;
                    $this->generateResendInvoicesEmail($office, $this->profile->customer_details, $this->args['invoices']);

                    break;

                case config('constants.SUMMARY'):
                    $pestContract = $this->hydratePestContract($this->args['contract_id']);
                    $this->generateBillingSummaryEmail($pestContract, $this->args['summary'] == 'paid', $this->args['officeUserId']);

                    break;

                case config('constants.CONTRACT'):
                    $pestContract = $this->hydratePestContract($this->args['contract_id']);
                    $this->generateWelcomeEmailNew($pestContract, $this->args['officeUserId']);

                    break;

                case config('constants.CUSTOMER_USER'):
                    $this->generateCustomerPortalEmail($this->profile, $this->args['officeUserId']);

                    break;

                case config('constants.REMOTE_COMPLETION'):
                    $pestContract = $this->hydratePestContract($this->args['contract_id']);
                    $this->generateRemoteCompletionEmail($pestContract, $this->args['officeUserId']);

                    break;
            }
        } catch (\Exception $e) {
            Log::info("Error : File - " . $e->getFile() . " Line - ".$e->getLine()." Message - ".$e->getMessage().' customer id : '.$this->args['customer_id'].' type '.$this->args['type']);
        }
        Log::info('Success : customer id : '.$this->args['customer_id'].' type '.$this->args['type']);
        Log::info("ResendEmailJob Job End");
    }
}
