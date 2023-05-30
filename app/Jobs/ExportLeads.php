<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosAlert;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosOfficeAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ExportLeads implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $columns;
    public $leadIds;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($columns, $leadIds)
    {
        $this->columns = $columns;
        $this->leadIds = $leadIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("ExportLeads Job Started For Export");

        $encodeArray = [
            'columns' => $this->columns,
            'lead_ids' => $this->leadIds
        ];
        $encode = json_encode($encodeArray);
        // $encode = Crypt::encryptString($encodedStr);
        $encode = base64_encode(json_encode($encode));

        $alert_details['name'] = 'Lead Export';
        $alert_details['description'] = 'The Lead Export has completed successfully<br><br><a href="'.config('constants.API_BASE_URL').'exportLeadsDownload/'.$encode.'" download>Download  Lead Export </a>';
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

        Log::info("ExportLeads Job End For Export");
    }
}
