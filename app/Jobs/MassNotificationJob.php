<?php

namespace App\Jobs;

use DB;
use Excel;
use Illuminate\Bus\Queueable;
use App\Exports\ExportCustomer;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosAlert;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosOfficeAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosMassNotification;

class MassNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $request;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
        // $request = $this->$request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Unpaid invoice Job Started For Export");

        // dd(auth()->user()->pocomos_company_office_user->id);

        // $encode = base64_encode(json_encode($this->columns));
        $args = $this->request;

        // dd($args['roles']);

        $offices = PocomosCompanyOffice::whereActive(1)->whereIn('id', $args['offices'])->get();

        // dd($offices);

        foreach ($offices as $office) {
            // dd($this->findActiveEmployeesByOffice($office, $args['roles']));
            $officeUsers = $this->findActiveEmployeesByOffice($office, $args['roles']);

            foreach ($officeUsers as $officeUser) {
                $alert_details['name'] = $args['title'];
                $alert_details['description'] = $args['note'];
                $alert_details['status'] = 'Posted';
                $alert_details['priority'] = $args['alert_priority'];
                $alert_details['type'] = 'Alert';
                $alert_details['active'] = true;
                $alert_details['notified'] = false;
                $alert = PocomosAlert::create($alert_details);

                $office_alert_details['alert_id'] = $alert->id;
                $office_alert_details['assigned_by_user_id'] = auth()->user()->pocomos_company_office_user->id ?? null;
                $office_alert_details['assigned_to_user_id'] = $officeUser->id ?? null;
                $office_alert_details['active'] = true;
                $office_alert_details['date_created'] = date('Y-m-d H:i:s');
                PocomosOfficeAlert::create($office_alert_details);
            }

            $officesNames[] = $office->name;
        }

        // dd($officesNames);

        $input_details['offices'] =  serialize($officesNames);
        $input_details['alert_body'] = $args['note'];
        $input_details['alert_priority'] = $args['alert_priority'];
        $input_details['roles'] =  serialize($args['roles']);
        $input_details['assigned_by_user_id'] =  auth()->user()->pocomos_company_office_user->id;
        $input_details['active'] = 1;
        $PocomosMassNotification =  PocomosMassNotification::create($input_details);

        // Log::info("Unpaid invoice  Job End For Export");
    }
}
