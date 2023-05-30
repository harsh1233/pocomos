<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Mail\LeadSendExportEmail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosPest;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pocomos\PocomosOfficeAlert;
use App\Models\Pocomos\PocomosLeadQuoteTag;
use App\Models\Pocomos\PocomosLeadQuotPest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\PocomosLeadQuoteSpecialtyPest;

class SendEmailLeadExport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $columns;
    public $request_data;
    public $customerIds;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($columns, $request_data, $customer_ids)
    {
        $this->columns = $columns;
        $this->request_data = $request_data;
        $this->customerIds = $customer_ids;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("SendEmailLeadExport Job Started For Export");

        $heading = array();
        $whereSql = '';
        $heading[] = 'Lead Id';
        $str = "pl.id as lead_id, pl.quote_id";

        if (in_array('name', $this->columns)) {
            $heading[] = 'Name';
            $str .= ", CONCAT(pl.first_name, ' ' , pl.last_name) as 'lead_name'";
        }
        if (in_array('phone', $this->columns)) {
            $heading[] = 'Phone';
            $str .= ", padn.number as phone";
        }
        if (in_array('email', $this->columns)) {
            $heading[] = 'Email';
            $str .= ", pc.email";
        }
        if (in_array('address', $this->columns)) {
            $heading[] = 'Address';
            $str .= ", CONCAT(pad.suite, ', ' , pad.street, ', ' , pad.postal_code ) as 'contact_address'";
        }
        if (in_array('postal_code', $this->columns)) {
            $heading[] = 'Postal Code';
            $str .= ", pad.postal_code";
        }
        if (in_array('status', $this->columns)) {
            $heading[] = 'Status';
            $str .= ", pl.status";
        }
        if (in_array('date_created', $this->columns)) {
            $heading[] = 'Date Created';
            $str .= ", pl.date_created";
        }
        if (in_array('first_name', $this->columns)) {
            $heading[] = 'First Name';
            $str .= ", pl.first_name";
        }
        if (in_array('last_name', $this->columns)) {
            $heading[] = 'Last Name';
            $str .= ", pl.last_name";
        }
        if (in_array('office', $this->columns)) {
            $heading[] = 'Office';
            $str .= ", co.name as 'office_name'";
        }
        if (in_array('company_name', $this->columns)) {
            $heading[] = 'Company Name';
            $str .= ", pc.company_name";
        }
        if (in_array('secondary_emails', $this->columns)) {
            $heading[] = 'Secondary Emails';
            $str .= ", pc.secondary_emails";
        }
        if (in_array('street', $this->columns)) {
            $heading[] = 'Street';
            $str .= ", pad.street";
        }
        if (in_array('city', $this->columns)) {
            $heading[] = 'City';
            $str .= ", pad.city";
        }
        if (in_array('region', $this->columns)) {
            $heading[] = 'State';
            $str .= ", ocr.name as 'region_name'";
        }
        if (in_array('lead_status', $this->columns)) {
            $heading[] = 'Lead Status';
            $str .= ", pl.status as 'lead_status'";
        }
        if (in_array('agreement', $this->columns)) {
            $heading[] = 'Agreement';
            $str .= ", pa.name as 'agreement_name'";
        }
        if (in_array('service_type', $this->columns)) {
            $heading[] = 'Service Type';
            $str .= ", pcst.name as 'service_type'";
        }
        if (in_array('service_frequency', $this->columns)) {
            $heading[] = 'Service Frequency';
            $str .= ", ppa.service_frequencies";
        }
        if (in_array('salesperson', $this->columns)) {
            $heading[] = 'Salesperson';
            $str .= ", sou.first_name as 'salesperson'";
        }
        if (in_array('found_by_type', $this->columns)) {
            $heading[] = 'Map Code';
            $str .= ", ppc.map_code";
        }
        if (in_array('map_code', $this->columns)) {
            $heading[] = 'Service Type';
            $str .= ", pcst.name as 'service_type'";
        }
        if (in_array('autopay', $this->columns)) {
            $heading[] = 'Autopay(Y/N)';
            $str .= ", csp.autopay";
        }
        if (in_array('date_signed_up', $this->columns)) {
            $heading[] = 'Date signed up';
            $str .= ", pl.date_created";
        }
        if (in_array('initial_price', $this->columns)) {
            $heading[] = 'Initial price';
            $str .= ", pa.initial_price";
        }
        if (in_array('recurring_price', $this->columns)) {
            $heading[] = 'Recurring price';
            $str .= ", pa.regular_initial_price";
        }
        if (in_array('regular_initial_price', $this->columns)) {
            $heading[] = 'Regular initial price';
            $str .= ", pa.regular_initial_price";
        }
        if (in_array('technician', $this->columns)) {
            $heading[] = 'Technician';
            $str .= ", ou.first_name as 'technician_name'";
        }

        $data = DB::select(DB::raw("SELECT $str
        FROM pocomos_leads AS pl
        LEFT JOIN pocomos_lead_quotes AS plq ON pl.quote_id = plq.id
        LEFT JOIN pocomos_customers AS pc ON pc.id = pl.customer_id
        LEFT JOIN pocomos_customer_sales_profiles AS csp ON pc.id = csp.customer_id
        LEFT JOIN pocomos_company_offices AS co ON csp.office_id = co.id
        LEFT JOIN pocomos_addresses AS pad ON pl.contact_address_id = pad.id
        LEFT JOIN pocomos_phone_numbers AS padn ON pad.phone_id = padn.id
        LEFT JOIN orkestra_countries_regions AS ocr ON pad.region_id = ocr.id
        LEFT JOIN pocomos_contracts AS pcd ON csp.id = pcd.profile_id
        LEFT JOIN pocomos_agreements AS pa ON plq.agreement_id = pa.id
        LEFT JOIN pocomos_sales_status AS pss ON pcd.sales_status_id = pss.id
        LEFT JOIN pocomos_salespeople AS psp ON csp.salesperson_id = psp.id
        LEFT JOIN pocomos_pest_contracts AS ppc ON pcd.id = ppc.contract_id
        LEFT JOIN pocomos_pest_contract_service_types AS pcst ON plq.service_type_id = pcst.id
        LEFT JOIN pocomos_pest_agreements AS ppa ON plq.pest_agreement_id = ppa.id
        LEFT JOIN pocomos_customer_state AS pcs ON pc.id = pcs.customer_id
        LEFT JOIN pocomos_technicians AS pt ON plq.technician_id = pt.id
        LEFT JOIN pocomos_company_office_users AS pcou ON pt.user_id = pcou.id
        LEFT JOIN orkestra_users AS ou ON pcou.user_id = ou.id
        LEFT JOIN pocomos_company_office_users AS spcou ON psp.user_id = spcou.id
        LEFT JOIN orkestra_users AS sou ON spcou.user_id = sou.id

        WHERE pl.active = 1 $whereSql
        GROUP BY pl.id ORDER BY pl.id DESC"));

        $heading[] = 'Pests';
        $heading[] = 'Special pests';
        $heading[] = 'Tags';
        foreach ($data as $value) {
            $leadPestIds = PocomosLeadQuotPest::where('lead_quote_id', $value->quote_id)->pluck('pest_id')->toArray();
            $leadPestNames = PocomosPest::whereIn('id', $leadPestIds)->pluck('name')->toArray();
            $value->pests = implode(', ', $leadPestNames);

            $leadSpecialPestIds = PocomosLeadQuoteSpecialtyPest::where('lead_quote_id', $value->quote_id)->pluck('pest_id')->toArray();
            $leadSpecialPestNames = PocomosPest::whereIn('id', $leadSpecialPestIds)->pluck('name')->toArray();
            $value->special_pests = implode(', ', $leadSpecialPestNames);

            $leadTagIds = PocomosLeadQuoteTag::where('lead_quote_id', $value->quote_id)->pluck('tag_id')->toArray();
            $leadTagNames = PocomosPest::whereIn('id', $leadTagIds)->pluck('name')->toArray();
            $value->tags = implode(', ', $leadTagNames);
        }

        /**Convert data result to csv formate data */
        $data_new = $this->convert_csv_formate_lead_data($heading, $data, $this->columns);

        $filename = storage_path('app/public/pdf/Leads.csv');
        // open csv file for writing
        $f = fopen($filename, 'w');
        // write each row at a time to a file
        foreach ($data_new as $row) {
            fputcsv($f, $row);
        }
        // close the file
        fclose($f);

        $alert_details['name'] = 'Lead Export';
        $alert_details['description'] = 'The Lead Export has completed successfully <br><br><p>The export has been mailed to '.$this->request_data['recipient'].'</p>';
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

        $email = Mail::to($this->request_data['recipient']);
        $email->send(new LeadSendExportEmail($this->request_data));

        /**Unlink csv file */
        unlink($filename);
        Log::info("SendEmailLeadExport Job End For Export");
    }
}
