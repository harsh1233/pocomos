<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Orkestra\OrkestraFile;
use Illuminate\Queue\SerializesModels;
use App\Models\Pocomos\PocomosContract;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosOfficeAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class AgreementExportJob implements ShouldQueue
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
        Log::info("AgreementExportJob Job Started");

        $officeId = $this->args['officeId'];
        $exportYear = $this->args['exportYear'];
        $fileHash = $this->args['hash'];
        $status = $this->convertArrayInStrings(array(config('constants.ACTIVE'), config('constants.ON_HOLD')));
        $excludedContracts = array();
        $createdContracts = 0;

        $office = PocomosCompanyOffice::where('id', $officeId)->whereActive(true)->firstOrFail();

        $sql = "SELECT c.*, csp.*, pcc.*, a.*, cu.*
        FROM pocomos_contracts AS c
        JOIN pocomos_customer_sales_profiles AS csp ON c.profile_id = csp.id
        JOIN pocomos_pest_contracts AS pcc ON c.id = pcc.id
        JOIN pocomos_agreements AS a ON c.agreement_id = a.id
        JOIN pocomos_customers AS cu ON csp.customer_id = cu.id
        WHERE csp.office_id = $officeId AND cu.status IN ($status)";

        if (is_numeric($exportYear)) {
            $sql .= " AND c.date_start >= ".$exportYear . '-01-01'.''." AND c.date_end <= ".$exportYear . '-12-31';
        }
        $contracts = DB::select(DB::raw($sql));

        $zip = new \ZipArchive();
        $tempFilePath = $fileHash . ".zip";

        if ($zip->open($tempFilePath, \ZipArchive::CREATE) !== true) {
            exit('cannot open <' . $tempFilePath . '>\n');
        }

        foreach ($contracts as $contract) {
            $contract = PocomosContract::findOrFail($contract->id);
            $path = $this->getContractFilename($contract);

            if (!file_exists($path)) {
                $pdf = $this->generateContractAgreement(array(
                    'office' => $office,
                    'customer' => $contract->profile_details->customer_details,
                    'agreement' => $contract->agreement_detail,
                    'contract' => $contract,
                    'pestContract' => $contract->pest_contract_details,
                ));

                if ($pdf) {
                    file_put_contents($path, $pdf);
                } else {
                    $excludedContracts[] = $contract->id;
                }
            }

            if (file_exists($path)) {
                $createdContracts++;
                $zip->addFromString($contract->id . '.pdf', file_get_contents($path));
            }
        }
        $zip->close();

        if (file_exists($tempFilePath) && $createdContracts > 0) {
            $filename = 'AgreementExport_'.$exportYear . '-01-01'.'_to_'.$exportYear . '-12-31'.'_'.strtotime(date('Y-m-d H:i:s')).'.zip';
            $url =  'Exports/' . $filename;
            Storage::disk('s3')->put($url, file_get_contents($tempFilePath), 'public');
            $filePath = Storage::disk('s3')->url($url);

            $transactionsFile = OrkestraFile::create([
                'path' => $filePath, 'filename' => $filename, 'mime_type' => 'application/zip', 'file_size' => '', 'active' => true, 'md5_hash' => $fileHash.'.zip', 'date_created' => date('Y-m-d H:i:s')
            ]);

            $alert_details['name'] = 'Agreement Jobs Export';
            $alert_details['description'] = 'The Agreement Jobs export has completed successfully<br><br><a href="'.$filePath.'">Download  Export File </a>';
            $alert_details['status'] = 'Posted';
            $alert_details['priority'] = 'Success';
            $alert_details['type'] = 'Alert';
            $alert_details['date_due'] = null;
            $alert_details['active'] = true;
            $alert_details['notified'] = false;
            $alert = PocomosAlert::create($alert_details);

            $office_alert_details['alert_id'] = $alert->id;
            $office_alert_details['assigned_by_user_id'] = auth()->user()->pocomos_company_office_user->id ?? null;
            $office_alert_details['assigned_to_user_id'] = auth()->user()->pocomos_company_office_user->id ?? null;
            $office_alert_details['active'] = true;
            $office_alert_details['date_created'] = date('Y-m-d H:i:s');
            PocomosOfficeAlert::create($office_alert_details);

            unlink($tempFilePath);
        }

        Log::info("AgreementExportJob Job End");
    }
}
