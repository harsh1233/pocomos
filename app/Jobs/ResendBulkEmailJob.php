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

class ResendBulkEmailJob implements ShouldQueue
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
        Log::info("ResendBulkEmailJob Job Started");
        $office = PocomosCompanyOffice::findOrFail($this->args['officeId']);

        $jobIds = $this->args['jobIds'];
        if (empty($jobIds) === false) {
            $contracts = $this->getContractsByJobIds($jobIds, $office);
            $this->processContracts($contracts, $this->args);
        }

        $invoiceIds = $this->args['invoiceIds'];
        if (empty($invoiceIds) === false) {
            $contracts = $this->getContractsByInvoiceIds($invoiceIds, $office);
            $this->processContracts($contracts, $this->args);
        }
        Log::info("ResendBulkEmailJob Job End");
    }

    /**
     */
    private function getContractsByJobIds(array $jobIds, $office)
    {
        return $this->getPestContractsByJobIdsAndOffices($jobIds, [$office->id]);
    }

    /**
     */
    private function getContractsByInvoiceIds(array $invoiceIds, $office)
    {
        return $this->getPestContractsByInvoiceIdsAndOffices($invoiceIds, [$office->id]);
    }

    /**
     */
    private function processContracts(array $pestControlContracts, $args)
    {
        foreach ($pestControlContracts as $pestContract) {
            $pestContract = (array)$pestContract;
            $pestContract = PocomosPestContract::findOrFail($pestContract['id']);
            $profile = $pestContract->contract_details->profile_details;
            $params = $args;
            $params['contract'] = $pestContract;
            $params['contract_id'] = $pestContract->contract_details->id ?? null;
            $this->resendEmailAction($profile, $params);
        }
    }
}
