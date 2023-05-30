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

class AutopayJob implements ShouldQueue
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
        Log::info("AutopayJob Job Started");

        $ids = $this->args;
        $sqlIds = $this->convertArrayInStrings($ids);

        $results = DB::select(DB::raw("SELECT csp.*
        FROM pocomos_customer_sales_profiles AS csp
        JOIN pocomos_customers AS c ON csp.customer_id = c.id
        WHERE csp.id IN ($sqlIds)"));

        foreach ($results as $result) {
            $profile = PocomosCustomerSalesProfile::findOrFail($result->id);

            foreach ($profile->contract_details as $contract) {
                $result = $this->doHandleContract($contract);
            }
        }
        Log::info("AutopayJob Job End");
    }
}
