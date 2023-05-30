<?php

namespace App\Jobs;

use DB;
use Excel;
use Illuminate\Bus\Queueable;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Pocomos\PocomosInvoice;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomerSalesProfile;

class BulkApplyAccountCreditJob implements ShouldQueue
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
        Log::info("BulkApplyAccountCreditJob Job Started");

        $args = $this->args;

        $profileIds = $args['profile_ids'];
        $officeUserId = $args['office_user_id'];
        $officeId = $args['office_id'];

        $officeUser = PocomosCompanyOfficeUser::find($officeUserId);
        // $office = PocomosCompanyOffice::find($officeId);

        $csps = PocomosCustomerSalesProfile::whereIn('id', $profileIds)->whereOfficeId($officeId)->get();

        // dd($csps);

        foreach ($csps as $csp) {
            $result = $this->applyCredit($csp, $officeUser);
        }

        Log::info("BulkApplyAccountCreditJob  Job End For Export");
    }
}
