<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosImportBatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessCustomerImportJob implements ShouldQueue
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
        Log::info("ProcessCustomerImportJob Job Started");

        DB::beginTransaction();
        try {
            $uploadBatch = PocomosImportBatch::findOrFail($this->args['id']);

            /** @var \Pocomos\Bundle\PestManagementBundle\Helper\CustomerImport\CustomerImportConverter $customerImportConverter */
            // $customerImportConverter = $this->getContainer()->get('pocomos.pest.helper.customer_import_converter');
            $imported = true;

            $results = DB::select(DB::raw("SELECT ic.*, b.*, o.*, ic.id as 'id'
                FROM pocomos_import_customers AS ic
                JOIN pocomos_import_batches AS b ON ic.upload_batch_id = b.id
                JOIN pocomos_company_offices AS o ON b.office_id = o.id
                WHERE b.id = $uploadBatch->id"));

            foreach ($results as $result) {
                $importedCustomer = $result;
                if ($importedCustomer->imported) {
                    continue;
                }

                $customerImported = false;
                $uploadBatch = PocomosImportBatch::find($importedCustomer->upload_batch_id);

                // Explicitly persist again
                $errors = array();
                // if ($uploadBatch->import_type != config('constants.LEADS')) {
                //     $errors = $this->getContainer()->get('validator')->validate($importedCustomer);
                // }

                if (count($errors)) {
                    foreach ($errors as $error) {
                        // $importedCustomer->errors = $error->getPropertyPath(), $error->getMessage();
                    }
                } else {
                    try {
                        $creationResult = $this->transformAndCreate($importedCustomer, $uploadBatch);
                        $imported = true;
                        $customerImported = true;
                    } catch (\Exception $e) {
                        Log::info("ProcessCustomerImportJob Error : ".json_encode($e->getMessage()));
                    }
                }

                if (!$customerImported) {
                    $imported = false;
                }
            }

            $uploadBatch->imported = $imported;
            $uploadBatch->save();
            DB::commit();
        } catch (\Exception $e) {
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));

            DB::rollback();
        }
        Log::info("ProcessCustomerImportJob Job End");
    }

    public function transformAndCreate($importedCustomer, $uploadBatch)
    {
        if ($uploadBatch->import_type == config('constants.LEADS')) {
            $customerModel = $this->transformImportedCustomerToCustomerModel($importedCustomer);
            $creationResult = $this->convertCustomerToEntity($importedCustomer, $customerModel, $uploadBatch->office_detail);
        } else {
            $creationResult = $this->transformImportedCustomerToLead($importedCustomer);
        }

        return $creationResult;
    }
}
