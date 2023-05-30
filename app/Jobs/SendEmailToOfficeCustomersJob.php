<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\PocomosCustomerSalesProfile;

class SendEmailToOfficeCustomersJob implements ShouldQueue
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
        Log::info("SendEmailToOfficeCustomersJob Job Started");

        $officeId = $this->args['officeId'];
        $customerIds = $this->args['customerIds'];

        $customerProfiles = $this->getCustomerProfiles($officeId, $customerIds);

        foreach ($customerProfiles as $customerProfile) {
            $profile = PocomosCustomerSalesProfile::find($customerProfile->id);
            $this->resendEmailAction($profile, $this->args);
        }

        Log::info("SendEmailToOfficeCustomersJob Job End");
    }

    /**
     * Get all office customer's profiles
     *
     * @param int $officeId
     * @param int[] $customerIds
     *
     * @return Customer[]
     */
    private function getCustomerProfiles($officeId, $customerIds)
    {
        $customerIds = $this->convertArrayInStrings($customerIds);
        $customerStatus = config('constants.ACTIVE');

        $res = DB::select(DB::raw("SELECT c.*
            FROM pocomos_customer_sales_profiles AS csp
            JOIN pocomos_customers AS c ON csp.customer_id = c.id
            JOIN pocomos_company_offices AS o ON csp.office_id = o.id
            WHERE o.id = '$officeId' AND c.active = 1 AND c.id IN ($customerIds) AND csp.active = true AND c.status = '$customerStatus' AND csp.office_user_id IS NULL"));

        return $res;
    }
}
