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

class ExportCustomers implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $columns;
    public $customerIds;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($columns, $customerIds)
    {
        $this->columns = $columns;
        $this->customerIds = $customerIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("ExportCustomers Job Started For Export");

        $encodeArray = [
            'columns' => $this->columns,
            'customer_ids' => $this->customerIds
        ];
        $encode = json_encode($encodeArray);
        // $encode = Crypt::encryptString($encodedStr);
        $encode = base64_encode(json_encode($encode));

        $alert_details['name'] = 'Customer Export';
        $alert_details['description'] = 'The Customer Export has completed successfully<br><br><a href="'.config('constants.API_BASE_URL').'exportCustomersDownload/'.$encode.'" download>Download  Customer Export </a>';
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

        // $heading = array();
        // $str = "pc.id, ppc.id as 'pest_contract_id', csp.id as 'sales_profile_id'";

        // if(in_array('name', $this->columns)){
        //     $heading[] = 'Name';
        //     $str .= ", CONCAT(pc.first_name, ' ' , pc.last_name ) as 'customer_name'";
        // }
        // if(in_array('office', $this->columns)){
        //     $heading[] = 'Office';
        //     $str .= ", co.name as 'office_name'";
        // }
        // if(in_array('office_fax', $this->columns)){
        //     $heading[] = 'Office Fax';
        //     $str .= ", co.fax as 'office_fax'";
        // }
        // if(in_array('email', $this->columns)){
        //     $heading[] = 'Email';
        //     $str .= ", pc.email as 'customer_email'";
        // }
        // if(in_array('company_name', $this->columns)){
        //     $heading[] = 'Company Name';
        //     $str .= ", pc.company_name";
        // }
        // if(in_array('billing_name', $this->columns)){
        //     $heading[] = 'Billing Name';
        //     $str .= ", pc.billing_name";
        // }
        // if(in_array('secondary_emails', $this->columns)){
        //     $heading[] = 'Secondary Emails';
        //     $str .= ", pc.secondary_emails";
        // }
        // if(in_array('street', $this->columns)){
        //     $heading[] = 'Street';
        //     $str .= ", pad.street";
        // }
        // if(in_array('city', $this->columns)){
        //     $heading[] = 'City';
        //     $str .= ", pad.city";
        // }
        // if(in_array('billing_street', $this->columns)){
        //     $heading[] = 'Billing Address';
        //     $str .= ", CONCAT(pbd.suite, ', ' , pbd.street ) as 'billing_street'";
        // }
        // if(in_array('billing_postal', $this->columns)){
        //     $heading[] = 'Billing Postal';
        //     $str .= ", pbd.postal_code as 'billing_postal'";
        // }
        // if(in_array('billing_city', $this->columns)){
        //     $heading[] = 'Billing City';
        //     $str .= ", pbd.city as 'billing_city'";
        // }
        // if(in_array('sales_status', $this->columns)){
        //     $heading[] = 'Sales Status';
        //     $str .= ", pss.name as 'sales_status'";
        // }
        // if(in_array('contract_start_date', $this->columns)){
        //     $heading[] = 'Contract Start Date';
        //     $str .= ", pcd.date_start as 'contract_start_date'";
        // }
        // if(in_array('salesperson', $this->columns)){
        //     $heading[] = 'Salesperson';
        //     $str .= ", CONCAT(ou.first_name, ' ',ou.last_name) as 'salesperson'";
        // }
        // if(in_array('map_code', $this->columns)){
        //     $heading[] = 'Map Code';
        //     $str .= ", ppc.map_code";
        // }
        // if(in_array('service_type', $this->columns)){
        //     $heading[] = 'Service Type';
        //     $str .= ", pcst.name as 'service_type'";
        // }
        // if(in_array('autopay', $this->columns)){
        //     $heading[] = 'Autopay(Y/N)';
        //     $str .= ", csp.autopay";
        // }
        // if(in_array('service_frequency', $this->columns)){
        //     $heading[] = 'Service Frequency';
        //     $str .= ", ppc.service_frequency";
        // }
        // if(in_array('date_created', $this->columns)){
        //     $heading[] = 'Date Created';
        //     $str .= ", pc.date_created";
        // }
        // if(in_array('initial_price', $this->columns)){
        //     $heading[] = 'Initial Price';
        //     $str .= ", ppc.initial_price";
        // }
        // if(in_array('recurring_price', $this->columns)){
        //     $heading[] = 'Recurring Price';
        //     $str .= ", ppc.recurring_price";
        // }
        // if(in_array('regular_initial_price', $this->columns)){
        //     $heading[] = 'Regular Initial Price';
        //     $str .= ", ppc.regular_initial_price";
        // }
        // if(in_array('last_service_date', $this->columns)){
        //     $heading[] = 'Last Service Date';
        //     $str .= ", pcs.last_service_date";
        // }
        // if(in_array('balance', $this->columns)){
        //     $heading[] = 'Balance';
        //     $str .= ", csp.balance";
        // }
        // if(in_array('first_name', $this->columns)){
        //     $heading[] = 'First Name';
        //     $str .= ", pc.first_name";
        // }
        // if(in_array('last_name', $this->columns)){
        //     $heading[] = 'Last Name';
        //     $str .= ", pc.last_name";
        // }
        // if(in_array('account_type', $this->columns)){
        //     $heading[] = 'Account Type';
        //     $str .= ", pc.account_type";
        // }
        // if(in_array('next_service_date', $this->columns)){
        //     $heading[] = 'Next Service Date';
        //     $str .= ", pcs.next_service_date";
        // }

        // $data = DB::select(DB::raw("SELECT $str
        // FROM pocomos_customers AS pc
        // JOIN pocomos_customer_sales_profiles AS csp ON pc.id = csp.customer_id
        // JOIN pocomos_company_offices AS co ON csp.office_id = co.id
        // JOIN pocomos_contracts AS pcd ON csp.id = pcd.profile_id
        // JOIN pocomos_agreements AS pa ON pcd.agreement_id = pa.id
        // JOIN pocomos_addresses AS pad ON pc.contact_address_id = pad.id
        // JOIN pocomos_addresses AS pbd ON pc.billing_address_id = pbd.id
        // LEFT JOIN pocomos_sales_status AS pss ON pc.status = pss.id
        // LEFT JOIN pocomos_salespeople AS psp ON csp.salesperson_id = psp.id
        // LEFT JOIN pocomos_company_office_users AS pcou ON psp.user_id = pcou.id
        // LEFT JOIN orkestra_users AS ou ON pcou.user_id = ou.id
        // LEFT JOIN pocomos_pest_contracts AS ppc ON pcd.id = ppc.contract_id
        // LEFT JOIN pocomos_pest_contract_service_types AS pcst ON ppc.service_type_id = pcst.id
        // LEFT JOIN pocomos_pest_agreements AS ppa ON pcd.id = ppa.id
        // LEFT JOIN pocomos_customer_state AS pcs ON pc.id = pcs.customer_id

        // WHERE pc.active = 1
        // ORDER BY co.id ASC"));
        // foreach($data as $value){
        //     $pest_tags_ids = PocomosPestContractsTag::where('contract_id', $value->pest_contract_id)->pluck('tag_id')->toArray();

        //     $tags = PocomosTag::whereIn('id', $pest_tags_ids)->pluck('name')->toArray();

        //     $is_account_linked = PocomosCustomersAccount::where('profile_id', $value->sales_profile_id)->count();
        //     $value->tags = implode(', ', $tags);
        //     $value->cc_or_ach_file = $is_account_linked ? true : false;
        //     $value->is_parent = $this->is_cutomer_parent($value->id);
        //     $value->is_child = $this->is_cutomer_child($value->id);
        // }
        // Excel::download(new ExportCustomer($heading, $data, $this->columns), 'Customers.csv');

        Log::info("ExportCustomers Job End For Export");
    }
}
