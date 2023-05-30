<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosTaxCode;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class TaxRecalculationJob implements ShouldQueue
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
        Log::info("TaxRecalculationJob Job Started");

        $taxCodeId = $this->args['taxCodeId'];
        $batchCount = (count($this->args['pestContractIds']) / 200);

        $segment = (int)((count($this->args['pestContractIds']) / $batchCount) + 1);
        $count = 0;

        $current = 0;

        while ($current < count($this->args['pestContractIds'])) {
            $ids = array_slice($this->args['pestContractIds'], $current, $segment);
            $current += $segment;
            $ids = $this->convertArrayInStrings($ids);

            $pestContracts = DB::select(DB::raw("SELECT pcc.id as contrid FROM pocomos_pest_contracts pcc
                JOIN pocomos_contracts c ON pcc.contract_id = c.id
                LEFT JOIN pocomos_invoices i ON c.id = i.contract_id
                LEFT JOIN pocomos_jobs j ON pcc.id = j.contract_id
                LEFT JOIN pocomos_invoice_items ii ON i.id = ii.invoice_id
                WHERE pcc.id IN ($ids) GROUP BY pcc.id"));

            $taxCode = PocomosTaxCode::where('id', $taxCodeId)->whereActive(true)->firstOrFail();

            foreach ($pestContracts as $i => $pestContract) {
                $result = $this->updatePestContractTaxCode($pestContract->contrid, $taxCode);
            }
        }

        Log::info("TaxRecalculationJob Job End");
    }
}
