<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use DB;
use Excel;
use App\Exports\ExportLead;
use App\Exports\ExportNote;
use App\Exports\ExportPhone;
use Illuminate\Http\Request;
use App\Exports\ExportAccount;
use App\Exports\ExportContract;
use App\Exports\ExportCustomer;
use App\Jobs\AgreementExportJob;
use App\Jobs\TransactionExportJob;
use App\Models\Pocomos\PocomosTag;
use App\Exports\ExportLeadsProcess;
use App\Exports\ExportRecruitement;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosLead;
use App\Models\Pocomos\PocomosPest;
use App\Http\Controllers\Controller;
use App\Jobs\UnpaidInvoicesExportJob;
use Illuminate\Support\Facades\Crypt;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosRecruits;
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosLeadQuoteTag;
use App\Models\Pocomos\PocomosLeadQuotPest;
use App\Models\Pocomos\PocomosRecruitContract;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\Recruitement\PocomosRecruit;
use App\Models\Pocomos\PocomosLeadQuoteSpecialtyPest;

class OfficeExportController extends Controller
{
    use Functions;

    /**
     * API for export Account of company
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function exportAccount(Request $request, $id)
    {
        $PocomosCompanyOffice = PocomosCompanyOffice::find($id);

        // if (!$PocomosCompanyOffice) {
        //     return $this->sendResponse(false, 'Unable to find the Office.');
        // }

        $update_data = DB::select(DB::raw("SELECT
        pocomos_customers.external_account_id as external_id,
        orkestra_accounts.id as account_id,
        orkestra_accounts.name as name,
        orkestra_accounts.address as address,
        orkestra_accounts.city as city,
        orkestra_accounts.region as region,
        orkestra_accounts.postal_code as postal_code,
        orkestra_accounts.type as type,
        orkestra_accounts.account_number as account_number,
        orkestra_accounts.ach_routing_number as ach_routing_number,
        orkestra_accounts.card_exp_month as card_exp_month,
        orkestra_accounts.card_exp_year as card_exp_year,
        orkestra_accounts.account_token as account_token
        FROM orkestra_accounts
        LEFT JOIN pocomos_customers_accounts ON orkestra_accounts.id = pocomos_customers_accounts.account_id
        LEFT JOIN pocomos_customer_sales_profiles ON pocomos_customers_accounts.profile_id = pocomos_customer_sales_profiles.id
        LEFT JOIN pocomos_customers ON pocomos_customer_sales_profiles.customer_id = pocomos_customers.id
        WHERE pocomos_customer_sales_profiles.office_id = '$id'
        AND orkestra_accounts.type IN ('CardAccount','BankAccount')
        AND orkestra_accounts.active = 1
        GROUP BY orkestra_accounts.id
        ORDER BY pocomos_customer_sales_profiles.id ASC"));

        if (!empty($_GET['is_export'])) {
            // return Excel::download(new ExportAccount($update_data->toArray()), 'ExportAccount.csv');
            return Excel::download(new ExportAccount($update_data), 'ExportAccount.csv');
        }

        return $this->sendResponse(true, 'List of Accounts.', $update_data);
    }


    public function exportNote(Request $request, $id)
    {
        $PocomosCompanyOffice = PocomosCompanyOffice::find($id);

        // if (!$PocomosCompanyOffice) {
        //     return $this->sendResponse(false, 'Unable to find the Office.');
        // }

        $update_data = DB::select(DB::raw("SELECT pocomos_customers.external_account_id as ext_id,
        CONCAT(pocomos_customers.first_name,' ',pocomos_customers.last_name) as name,
        CONCAT(pocomos_addresses.street, ' ', pocomos_addresses.suite, ' ', pocomos_addresses.city,' ', pocomos_addresses.postal_code) as address,
        pocomos_phone_numbers.number as phoneNumb,
        pocomos_notes.summary as note,
        DATE_FORMAT(pocomos_notes.date_created,'%c/%d/%Y %h:%i %p') as note_time
        FROM pocomos_customers_notes
        JOIN pocomos_customers ON pocomos_customers_notes.customer_id = pocomos_customers.id
        JOIN pocomos_notes ON pocomos_customers_notes.note_id = pocomos_notes.id
        JOIN pocomos_customer_sales_profiles ON pocomos_customers_notes.customer_id = pocomos_customer_sales_profiles.customer_id
        JOIN pocomos_addresses ON pocomos_customers.contact_address_id = pocomos_addresses.id
        JOIN pocomos_phone_numbers ON pocomos_addresses.phone_id = pocomos_phone_numbers.id
        JOIN pocomos_company_offices ON pocomos_customer_sales_profiles.office_id = pocomos_company_offices.id
        WHERE (pocomos_company_offices.id ='$id'
          OR pocomos_company_offices.parent_id ='$id')
        AND pocomos_customers.active = 1
        GROUP BY pocomos_notes.id
        ORDER BY pocomos_customers.id ASC"));

        if (!empty($_GET['is_export'])) {
            return Excel::download(new ExportNote($update_data), 'ExportNote.csv');
            //  return Excel::download(new ExportNote($update_data->toArray()), 'ExportNote.csv');
        }

        return $this->sendResponse(true, 'List of Note.', $update_data);
    }


    public function exportContract(Request $request, $id)
    {
        $PocomosCompanyOffice = PocomosCompanyOffice::find($id);

        // if (!$PocomosCompanyOffice) {
        //     return $this->sendResponse(false, 'Unable to find the Office.');
        // }

        $update_data = DB::select(DB::raw("SELECT
        pocomos_customers.external_account_id as 'Customer ID',
        CONCAT(pocomos_customers.first_name,' ',pocomos_customers.last_name) as 'Customer Name',
        pocomos_agreements.name as agreement,
        pocomos_contracts.status as status,
        pocomos_pest_contracts.service_frequency as 'Service Frequency',
        pocomos_contracts.billing_frequency as 'Billing Frequency',
        pocomos_pest_contracts.id AS 'Contract ID',
        pocomos_pest_contracts.initial_price as 'Initial Price',
        pocomos_pest_contracts.regular_initial_price as 'Regular Intial Price',
        pocomos_pest_contracts.initial_discount as 'Initial Discount',
        pocomos_pest_contracts.week_of_the_month as 'Pref. Week of the Month',
        pocomos_pest_contracts.day_of_the_week as 'Pref. Day of the Week',
        pocomos_pest_contracts.preferred_time as 'Preferred time',
        pocomos_pest_contracts.map_code as 'Map Code',
        pocomos_pest_contracts.preferred_time as 'Preferred time',
        pocomos_pest_contracts.recurring_price as 'Recurring Price',
        pocomos_pest_contract_service_types.name as 'Service Type',
        pocomos_counties.name as 'County',
        CONCAT(tech_users.first_name,' ',tech_users.last_name) as 'Preferred Technician',
        CONCAT(sales_users.first_name,' ',sales_users.last_name) as 'Salesperson',
        pocomos_contracts.auto_renew as 'Auto Renew',
        pocomos_marketing_types.name as 'Marketing Type',
        pocomos_sales_status.name as 'Sales Status',
        pocomos_contracts.sales_status_modified as 'Sales Status Modified',
        pocomos_contracts.date_start as 'Contract Start Date',
        pocomos_contracts.sales_tax as 'Sales Tax',
        GROUP_CONCAT(pocomos_custom_field_configuration.label,':',pocomos_custom_fields.value) as 'Custom Contract Fields',
        MIN(pocomos_jobs.date_scheduled) AS 'Initial Service Date',
        pocomos_customer_state.last_service_date AS 'Last Service Date',
        pocomos_customer_state.next_service_date AS 'Next Service Date'

        FROM pocomos_pest_contracts
        LEFT JOIN pocomos_contracts ON pocomos_pest_contracts.contract_id = pocomos_contracts.id
        LEFT JOIN pocomos_agreements ON pocomos_contracts.agreement_id = pocomos_agreements.id
        LEFT JOIN pocomos_customer_sales_profiles ON pocomos_contracts.profile_id = pocomos_customer_sales_profiles.id
        LEFT JOIN pocomos_customers ON pocomos_customer_sales_profiles.customer_id = pocomos_customers.id
        LEFT JOIN pocomos_addresses ON pocomos_customers.contact_address_id = pocomos_addresses.id
        LEFT JOIN pocomos_company_offices ON pocomos_customer_sales_profiles.office_id = pocomos_company_offices.id
        LEFT JOIN pocomos_pest_contract_service_types ON pocomos_pest_contracts.service_type_id = pocomos_pest_contract_service_types.id
        LEFT JOIN pocomos_counties ON pocomos_pest_contracts.county_id = pocomos_counties.id
        LEFT JOIN pocomos_marketing_types ON pocomos_contracts.found_by_type_id = pocomos_marketing_types.id
        LEFT JOIN pocomos_sales_status ON pocomos_contracts.sales_status_id = pocomos_sales_status.id
        LEFT JOIN pocomos_technicians ON pocomos_pest_contracts.technician_id = pocomos_technicians.id
        LEFT JOIN pocomos_company_office_users tech_office_users ON pocomos_technicians.user_id = tech_office_users.id
        LEFT JOIN orkestra_users tech_users ON tech_office_users.user_id = tech_users.id
        LEFT JOIN pocomos_salespeople ON pocomos_contracts.salesperson_id = pocomos_salespeople.id
        LEFT JOIN pocomos_company_office_users sales_office_users ON pocomos_salespeople.user_id = sales_office_users.id
        LEFT JOIN orkestra_users sales_users ON sales_office_users.user_id = sales_users.id
        LEFT JOIN pocomos_custom_fields ON pocomos_custom_fields.pest_control_contract_id = pocomos_pest_contracts.id
        LEFT JOIN pocomos_custom_field_configuration ON pocomos_custom_fields.custom_field_configuration_id = pocomos_custom_field_configuration.id
        LEFT JOIN pocomos_jobs ON pocomos_pest_contracts.id = pocomos_jobs.contract_id
        LEFT JOIN pocomos_customer_state ON pocomos_customers.id = pocomos_customer_state.customer_id

        WHERE pocomos_company_offices.id = '$id'
        GROUP BY pocomos_pest_contracts.id
        ORDER BY pocomos_customers.external_account_id"));

        if (!empty($_GET['is_export'])) {
            return Excel::download(new ExportContract($update_data), 'ExportContract.csv');
            //  return Excel::download(new ExportContract($update_data->toArray()), 'ExportContract.csv');
        }

        return $this->sendResponse(true, 'List of Note.', $update_data);
    }


    public function exportPhone(Request $request, $id)
    {
        $PocomosCompanyOffice = PocomosCompanyOffice::find($id);

        // if (!$PocomosCompanyOffice) {
        //     return $this->sendResponse(false, 'Unable to find the Office.');
        // }

        $update_data = DB::select(DB::raw("SELECT c.id, p.alias, p.type, p.number
            FROM pocomos_customers_phones AS cp
            JOIN pocomos_customer_sales_profiles AS csp ON cp.profile_id = csp.id
            JOIN pocomos_phone_numbers AS p ON cp.phone_id = p.id
            JOIN pocomos_customers AS c ON csp.customer_id = c.id
            JOIN pocomos_company_offices AS o ON csp.office_id = o.id
            WHERE (o.id = '$id' OR o.parent_id = '$id') AND c.active = 1
            GROUP BY p.id
            ORDER BY c.id ASC"));

        if (!empty($_GET['is_export'])) {
            // return Excel::download(new ExportPhone($update_data->toArray()), 'ExportPhone.csv');
            return Excel::download(new ExportPhone($update_data), 'ExportPhone.csv');
        }

        return $this->sendResponse(true, 'List of Note.', $update_data);
    }

    public function exportRecruitement(Request $request, $id)
    {
        // return 11;
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'converted_to_employee' => 'nullable|in:not converted,converted',
            'recruit_status' => 'nullable|array',
            'contract_start' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // filterAllViewableByOfficeUserAndRecruitSearchTerms
        $query = PocomosRecruit::select(
            '*',
            'pocomos_recruits.email',
            'pocomos_recruits.first_name',
            'pocomos_recruits.last_name',
            // 'ppn.number', 'ppan.number as alt_number',
            'pro.name as recruiting_office_name',
            'ocr.name as region'
        )
            ->leftJoin('pocomos_recruit_contracts as prc', 'pocomos_recruits.recruit_contract_id', 'prc.id');

        if ($request->office_id) {
            // return 11;
            $query->join('pocomos_recruit_status as prs', 'pocomos_recruits.recruit_status_id', 'prs.id')
                ->join('pocomos_recruiting_office_configurations as proc', 'prs.recruiting_office_configuration_id', 'proc.id')
                ->join('pocomos_company_offices as pco', 'proc.office_id', 'pco.id')
                ->where('pco.id', $request->office_id);
        }

        if ($request->contract_start) {
            $query->whereYear('prc.date_start', $request->contract_start);
        }

        if ($request->recruit_status) {
            $query->whereIn('pocomos_recruits.recruit_status_id', $request->recruit_status);
        }

        // contractStatus
        if ($request->converted_to_employee) {
            if ($request->converted_to_employee == 'converted') {
                $query->where('prc.status', 'Signed, Converted');
            } elseif ($request->converted_to_employee == 'not converted') {
                $query->whereNull('prc.id')
                        ->where('prc.status', '!=', 'Signed, Converted');
            }
        }

        $query->where('pocomos_recruits.active', 1);

        $officeUser = auth()->user()->pocomos_company_office_user;

        // if ($this->privilegedUser($officeUser)) {
        //     $query->join('pocomos_recruiters as pr', 'pocomos_recruits.recruiter_id', 'pr.id')
        //         ->join('pocomos_company_office_users as pcou', 'pocomos_recruits.recruiter_id', 'pcou.id')
        //         ->join('pocomos_company_office_user_profiles as pcoup', 'pcou.profile_id', 'pcoup.id')
        //         ->where('pocomos_recruits.active', 1)
        //         ->where('pcoup.id', $officeUser->profile_id);
        // }

        $data = $query->leftJoin('orkestra_users as ou', 'pocomos_recruits.user_id', 'ou.id')
            ->leftJoin('pocomos_recruiting_offices as pro', 'pocomos_recruits.recruiting_office_id', 'pro.id')
            ->leftJoin('pocomos_recruit_agreements as pra', 'prc.agreement_id', 'pra.id')
            ->leftJoin('pocomos_addresses as pa', 'pocomos_recruits.current_address_id', 'pa.id')
            ->leftJoin('orkestra_countries_regions as ocr', 'pa.region_id', 'ocr.id')

        // added
        // ->leftJoin('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
        // ->leftJoin('pocomos_phone_numbers as ppan', 'pa.alt_phone_id', 'ppan.id')
        // ->leftJoin('pocomos_addresses as ppa', 'pocomos_recruits.primary_address_id', 'pa.id')

        ->get();

        if (!empty($_GET['is_export'])) {
            return Excel::download(new ExportRecruitement($data), 'ExportPhone.csv');
        }

        return $this->sendResponse(true, 'List of Recruitement.', $data);
    }

    /**Export customer details download */
    public function exportCustomersDownload(Request $request, $slug)
    {
        // $decodeStr = Crypt::decryptString($slug);
        $decodedData = json_decode(json_decode(base64_decode($slug), true), true);
        $exported_columns = $decodedData['columns'] ?? array();
        $customerIds = $decodedData['customer_ids'] ?? array();

        $heading = array();
        $whereSql = '';
        $str = "pc.id, ppc.id as 'pest_contract_id', csp.id as 'sales_profile_id'";

        if (in_array('name', $exported_columns)) {
            $heading[] = 'Name';
            $str .= ", CONCAT(pc.first_name, ' ' , pc.last_name ) as 'customer_name'";
        }
        if (in_array('office', $exported_columns)) {
            $heading[] = 'Office';
            $str .= ", co.name as 'office_name'";
        }
        if (in_array('office_fax', $exported_columns)) {
            $heading[] = 'Office Fax';
            $str .= ", co.fax as 'office_fax'";
        }
        if (in_array('email', $exported_columns)) {
            $heading[] = 'Email';
            $str .= ", pc.email as 'customer_email'";
        }
        if (in_array('company_name', $exported_columns)) {
            $heading[] = 'Company Name';
            $str .= ", pc.company_name";
        }
        if (in_array('billing_name', $exported_columns)) {
            $heading[] = 'Billing Name';
            $str .= ", pc.billing_name";
        }
        if (in_array('secondary_emails', $exported_columns)) {
            $heading[] = 'Secondary Emails';
            $str .= ", pc.secondary_emails";
        }
        if (in_array('street', $exported_columns)) {
            $heading[] = 'Street';
            $str .= ", pad.street";
        }
        if (in_array('city', $exported_columns)) {
            $heading[] = 'City';
            $str .= ", pad.city";
        }
        if (in_array('postal_code', $exported_columns)) {
            $heading[] = 'Postal Code';
            $str .= ", pad.postal_code";
        }
        if (in_array('address', $exported_columns)) {
            $heading[] = 'Address';
            $str .= ", CONCAT(pad.suite, ', ' , pad.street, ', ' , pad.postal_code ) as 'contact_address'";
        }
        if (in_array('phone', $exported_columns)) {
            $heading[] = 'Phone';
            $str .= ", padn.number as phone";
        }
        if (in_array('billing_street', $exported_columns)) {
            $heading[] = 'Billing Address';
            $str .= ", CONCAT(pbd.suite, ', ' , pbd.street ) as 'billing_street'";
        }
        if (in_array('billing_postal', $exported_columns)) {
            $heading[] = 'Billing Postal';
            $str .= ", pbd.postal_code as 'billing_postal'";
        }
        if (in_array('billing_city', $exported_columns)) {
            $heading[] = 'Billing City';
            $str .= ", pbd.city as 'billing_city'";
        }
        if (in_array('sales_status', $exported_columns)) {
            $heading[] = 'Sales Status';
            $str .= ", pss.name as 'sales_status'";
        }
        if (in_array('contract_start_date', $exported_columns)) {
            $heading[] = 'Contract Start Date';
            $str .= ", pcd.date_start as 'contract_start_date'";
        }
        if (in_array('salesperson', $exported_columns)) {
            $heading[] = 'Salesperson';
            $str .= ", CONCAT(ou.first_name, ' ',ou.last_name) as 'salesperson'";
        }
        if (in_array('map_code', $exported_columns)) {
            $heading[] = 'Map Code';
            $str .= ", ppc.map_code";
        }
        if (in_array('service_type', $exported_columns)) {
            $heading[] = 'Service Type';
            $str .= ", pcst.name as 'service_type'";
        }
        if (in_array('autopay', $exported_columns)) {
            $heading[] = 'Autopay(Y/N)';
            $str .= ", csp.autopay";
        }
        if (in_array('service_frequency', $exported_columns)) {
            $heading[] = 'Service Frequency';
            $str .= ", ppc.service_frequency";
        }
        if (in_array('date_created', $exported_columns)) {
            $heading[] = 'Date Created';
            $str .= ", pc.date_created";
        }
        if (in_array('initial_price', $exported_columns)) {
            $heading[] = 'Initial Price';
            $str .= ", ppc.initial_price";
        }
        if (in_array('recurring_price', $exported_columns)) {
            $heading[] = 'Recurring Price';
            $str .= ", ppc.recurring_price";
        }
        if (in_array('regular_initial_price', $exported_columns)) {
            $heading[] = 'Regular Initial Price';
            $str .= ", ppc.regular_initial_price";
        }
        if (in_array('last_service_date', $exported_columns)) {
            $heading[] = 'Last Service Date';
            $str .= ", pcs.last_service_date";
        }
        if (in_array('balance', $exported_columns)) {
            $heading[] = 'Balance';
            $str .= ", csp.balance";
        }
        if (in_array('first_name', $exported_columns)) {
            $heading[] = 'First Name';
            $str .= ", pc.first_name";
        }
        if (in_array('last_name', $exported_columns)) {
            $heading[] = 'Last Name';
            $str .= ", pc.last_name";
        }
        if (in_array('account_type', $exported_columns)) {
            $heading[] = 'Account Type';
            $str .= ", pc.account_type";
        }
        if (in_array('status', $exported_columns)) {
            $heading[] = 'Status';
            $str .= ", pc.status";
        }
        if (in_array('next_service_date', $exported_columns)) {
            $heading[] = 'Next Service Date';
            $str .= ", pcs.next_service_date";
        }

        if (count($customerIds)) {
            $customerIds = $this->convertArrayInStrings($customerIds);
            $whereSql .= " AND pc.id IN($customerIds) ";
        }

        $data = DB::select(DB::raw("SELECT $str
        FROM pocomos_customers AS pc
        JOIN pocomos_customer_sales_profiles AS csp ON pc.id = csp.customer_id
        JOIN pocomos_company_offices AS co ON csp.office_id = co.id
        JOIN pocomos_contracts AS pcd ON csp.id = pcd.profile_id
        JOIN pocomos_agreements AS pa ON pcd.agreement_id = pa.id
        JOIN pocomos_addresses AS pad ON pc.contact_address_id = pad.id
        JOIN pocomos_addresses AS pbd ON pc.billing_address_id = pbd.id
        LEFT JOIN pocomos_phone_numbers AS padn ON pad.phone_id = padn.id
        LEFT JOIN pocomos_sales_status AS pss ON pcd.sales_status_id = pss.id
        LEFT JOIN pocomos_salespeople AS psp ON csp.salesperson_id = psp.id
        LEFT JOIN pocomos_company_office_users AS pcou ON psp.user_id = pcou.id
        LEFT JOIN orkestra_users AS ou ON pcou.user_id = ou.id
        LEFT JOIN pocomos_pest_contracts AS ppc ON pcd.id = ppc.contract_id
        LEFT JOIN pocomos_pest_contract_service_types AS pcst ON ppc.service_type_id = pcst.id
        LEFT JOIN pocomos_pest_agreements AS ppa ON pcd.id = ppa.id
        LEFT JOIN pocomos_customer_state AS pcs ON pc.id = pcs.customer_id

        WHERE pc.active = 1 $whereSql
        GROUP BY pc.id ORDER BY co.id ASC"));
        foreach ($data as $value) {
            $pest_tags_ids = PocomosPestContractsTag::where('contract_id', $value->pest_contract_id)->pluck('tag_id')->toArray();

            $tags = PocomosTag::whereIn('id', $pest_tags_ids)->pluck('name')->toArray();

            $is_account_linked = PocomosCustomersAccount::where('profile_id', $value->sales_profile_id)->count();
            $value->tags = implode(', ', $tags);
            $value->cc_or_ach_file = $is_account_linked ? true : false;
            $value->is_parent = $this->is_cutomer_parent($value->id);
            $value->is_child = $this->is_cutomer_child($value->id);
        }
        return Excel::download(new ExportCustomer($heading, $data, $exported_columns), 'Customers.csv');
    }

    /**Export the transactions details */
    public function exportTransactions(Request $request, $id)
    {
        $office = PocomosCompanyOffice::findOrFail($id);
        $officeUserId = Session::get(config('constants.ACTIVE_OFFICE_USER_ID')) ?? null;

        if (!$officeUserId) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        $officeUser = PocomosCompanyOfficeUser::findOrFail($officeUserId);

        $hash = md5('transactionsExport' . $office->id . $officeUser->id . date('m-d-Y H:i:s'));
        $args = array(
            'officeId' => $id,
            'alertReceivingUsers' => array($officeUser->id),
            'filters' => serialize(array('accrual' => 'accrual')),
            'salespeople' => serialize(array()),
            'hash' => $hash,
        );
        $job = TransactionExportJob::dispatch($args);

        return $this->sendResponse(true, __('strings.message', ['message' => "Transactions export job has started. You will find the download link on your message board when it's complete. This could take a few minutes"]));
    }

    /**Export the unpaid jobs/ invoices details */
    public function exportUnpaidJobsOrInvoices(Request $request, $id)
    {
        $office = PocomosCompanyOffice::findOrFail($id);
        $officeUserId = Session::get(config('constants.ACTIVE_OFFICE_USER_ID')) ?? null;

        if (!$officeUserId) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        $officeUser = PocomosCompanyOfficeUser::findOrFail($officeUserId);
        $hash = md5('unpaidExport' . $office->id . $officeUser->id . date('m-d-Y H:i:s'));

        // $terms = new EnhancedRouteSearchTerms();
        $terms = $this->createDefaultUnpaidSearchTerms();
        // $terms['includeMiscInvoices'] = true;

        $args = array(
            'officeId' => $id,
            'alertReceivingUsers' => array($officeUser->id),
            'terms' => serialize($terms),
            'hash' => $hash,
        );
        $job = UnpaidInvoicesExportJob::dispatch($args);

        return $this->sendResponse(true, __('strings.message', ['message' => "Unpaid export job has started. You will find the download link on your message board when it's complete. This could take a few minutes"]));
    }

    /**Export pdf agreements */
    public function exportAgreements(Request $request, $id)
    {
        $office = PocomosCompanyOffice::findOrFail($id);
        $officeUserId = Session::get(config('constants.ACTIVE_OFFICE_USER_ID')) ?? null;

        if (!$officeUserId) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        $officeUser = PocomosCompanyOfficeUser::findOrFail($officeUserId);
        $hash = md5('agreementExport' . $office->id . $officeUser->id . date('m-d-Y H:i:s'));
        $exportYear = $request->year;

        $args = array(
            'officeId' => $id,
            'alertReceivingUsers' => array($officeUser->id),
            'exportYear' => $exportYear,
            'hash' => $hash,
        );
        $job = AgreementExportJob::dispatch($args);
        return $this->sendResponse(true, __('strings.message', ['message' => "Agreement export has started. You will find the download link on your message board when it's complete. This could take a few minutes"]));
    }

    /**Deactive customers for the office */
    public function deactivateAllCustomers($id)
    {
        $office = PocomosCompanyOffice::findOrFail($id);

        $results = DB::select(DB::raw("SELECT c.*
            FROM pocomos_customers AS c
            LEFT JOIN pocomos_customer_sales_profiles AS csp ON c.id = csp.customer_id
            WHERE csp.office_id = $id ANd c.status = '".config('constants.INACTIVE')."'
            GROUP BY c.id LIMIT 1500"));

        $i = 1;
        foreach ($results as $result) {
            $customer = PocomosCustomer::findOrFail($result->id);
            if (!$customer) {
                throw new \Exception("Shit's broke, yo\n");
                continue;
            }
            $this->deactivateCustomer($result->id, /* status reason*/ null, /* deactivateChildren */ true);
        }
        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Customer deactivated']));
    }

    /**Export leads details download */
    public function exportLeadsDownload($slug)
    {
        $officeId = Crypt::decryptString($slug);

        $PocomosLead = PocomosLead::query();
        $PocomosLead = $PocomosLead->where('active', 1);

        $PocomosLeadIds = $PocomosLead->whereHas('quote_id_detail.sales_person_detail.office_user_details', function ($q) use ($officeId) {
            $q->whereOfficeId($officeId);
        })->with(
            'addresses',
            'quote_id_detail.service_type',
            'quote_id_detail.found_by_type_detail',
            'quote_id_detail.county_detail',
            'quote_id_detail.sales_person_detail.office_user_details.user_details',
            'quote_id_detail.pest_agreement_detail.agreement_detail:id,name',
            'quote_id_detail.tags.tag_detail',
            'quote_id_detail.pests.pest_detail',
            'quote_id_detail.specialty_pests.specialty_pest_detail',
            'permanent_note.note_detail',
            'quote_id_detail.technician_detail.user_detail.user_details:id,username,first_name,last_name',
            'not_interested_reason',
            'initial_job',
            'lead_reminder'
        )->orderBy('id', 'desc')->pluck('pocomos_leads.id')->toArray();

        $PocomosLeadIdsStr = $this->convertArrayInStrings($PocomosLeadIds);

        $data =  DB::select(DB::raw("SELECT l.id, l.first_name, l.last_name, l.date_created, l.email, ppn.number as 'primary_phone', pn.summary as 'intial_job_note', l.status, ca.street, ca.suite, ca.city, ca.postal_code, plq.map_code, ou.first_name as 'sales_people', pcst.name as 'service_type', plq.service_frequency, pa.name as 'contract_type', plq.regular_initial_price as 'normal_initial', plq.initial_discount, plq.initial_price, plq.recurring_price, ptu.first_name as 'technician', ppno.summary as 'permanent_note', plq.id as 'lead_quote_id'
        FROM pocomos_leads as l
        LEFT JOIN pocomos_addresses as ca ON l.contact_address_id = ca.id
        LEFT JOIN pocomos_phone_numbers as ppn ON ca.phone_id = ppn.id
        LEFT JOIN pocomos_notes as pn ON l.initial_job_note_id = pn.id
        LEFT JOIN pocomos_lead_quotes as plq ON l.quote_id = plq.id
        LEFT JOIN pocomos_salespeople as sp ON plq.salesperson_id = sp.id
        LEFT JOIN pocomos_company_office_users as pcou ON sp.user_id = pcou.id
        LEFT JOIN orkestra_users as ou ON pcou.user_id = ou.id
        LEFT JOIN pocomos_pest_contract_service_types as pcst ON plq.service_type_id = pcst.id
        LEFT JOIN pocomos_pest_agreements as ppa ON plq.pest_agreement_id = ppa.id
        LEFT JOIN pocomos_agreements as pa ON ppa.agreement_id = pa.id
        LEFT JOIN pocomos_technicians as pt ON plq.technician_id = pt.id
        LEFT JOIN pocomos_company_office_users as ptou ON pt.user_id = ptou.id
        LEFT JOIN orkestra_users as ptu ON ptou.user_id = ptu.id
        LEFT JOIN pocomos_leads_notes as pln ON l.id = pln.lead_id
        LEFT JOIN pocomos_notes as ppno ON pln.note_id = ppno.id
        WHERE l.id IN ($PocomosLeadIdsStr) GROUP BY l.id ORDER BY l.id  DESC
        "));

        $i = 0;
        foreach ($data as $val) {
            /**Pests */
            $quotePestsIds = PocomosLeadQuotPest::where('lead_quote_id', $val->lead_quote_id)->pluck('pest_id')->toArray();
            $pests = PocomosPest::whereIn('id', $quotePestsIds)->pluck('name')->toArray();
            $data[$i]->pests = implode(', ', $pests);

            /**Special Pests */
            $quoteSpestsIds = PocomosLeadQuoteSpecialtyPest::where('lead_quote_id', $val->lead_quote_id)->pluck('pest_id')->toArray();
            $specialPests = PocomosPest::whereIn('id', $quoteSpestsIds)->pluck('name')->toArray();
            $data[$i]->special_pests = implode(', ', $specialPests);

            /**Tags */
            $quoteTagIds = PocomosLeadQuoteTag::where('lead_quote_id', $val->lead_quote_id)->pluck('tag_id')->toArray();
            $tags = PocomosTag::whereIn('id', $quoteTagIds)->pluck('name')->toArray();
            $data[$i]->tags = implode(', ', $tags);

            $i = $i + 1;
        }
        return Excel::download(new ExportLead($data), 'ExportLead.csv');
    }

    /**Export leds details download */
    public function exportLeadsDownloadProcess($slug)
    {
        // $decodeStr = Crypt::decryptString($slug);
        $decodedData = json_decode(json_decode(base64_decode($slug), true), true);
        $exported_columns = $decodedData['columns'] ?? array();
        $customerIds = $decodedData['customer_ids'] ?? array();

        $heading = array();
        $whereSql = '';
        $heading[] = 'Lead Id';
        $str = "pl.id as lead_id";

        if (in_array('name', $exported_columns)) {
            $heading[] = 'Name';
            $str .= ", CONCAT(pl.first_name, ' ' , pl.last_name) as 'lead_name'";
        }
        if (in_array('phone', $exported_columns)) {
            $heading[] = 'Phone';
            $str .= ", padn.number as phone";
        }
        if (in_array('email', $exported_columns)) {
            $heading[] = 'Email';
            $str .= ", pc.email";
        }
        if (in_array('address', $exported_columns)) {
            $heading[] = 'Address';
            $str .= ", CONCAT(pad.suite, ', ' , pad.street, ', ' , pad.postal_code ) as 'contact_address'";
        }
        if (in_array('postal_code', $exported_columns)) {
            $heading[] = 'Postal Code';
            $str .= ", pad.postal_code";
        }
        if (in_array('status', $exported_columns)) {
            $heading[] = 'Status';
            $str .= ", pl.status";
        }
        if (in_array('date_created', $exported_columns)) {
            $heading[] = 'Date Created';
            $str .= ", pl.date_created";
        }
        if (in_array('first_name', $exported_columns)) {
            $heading[] = 'First Name';
            $str .= ", pl.first_name";
        }
        if (in_array('last_name', $exported_columns)) {
            $heading[] = 'Last Name';
            $str .= ", pl.last_name";
        }
        if (in_array('office', $exported_columns)) {
            $heading[] = 'Office';
            $str .= ", co.name as 'office_name'";
        }
        if (in_array('company_name', $exported_columns)) {
            $heading[] = 'Company Name';
            $str .= ", pc.company_name";
        }
        if (in_array('secondary_emails', $exported_columns)) {
            $heading[] = 'Secondary Emails';
            $str .= ", pc.secondary_emails";
        }
        if (in_array('street', $exported_columns)) {
            $heading[] = 'Street';
            $str .= ", pad.street";
        }
        if (in_array('city', $exported_columns)) {
            $heading[] = 'City';
            $str .= ", pad.city";
        }
        if (in_array('region', $exported_columns)) {
            $heading[] = 'State';
            $str .= ", ocr.name as 'region_name'";
        }
        if (in_array('lead_status', $exported_columns)) {
            $heading[] = 'Lead Status';
            $str .= ", pl.status as 'lead_status'";
        }
        if (in_array('agreement', $exported_columns)) {
            $heading[] = 'Agreement';
            $str .= ", pa.name as 'agreement_name'";
        }
        if (in_array('service_type', $exported_columns)) {
            $heading[] = 'Service Type';
            $str .= ", pcst.name as 'service_type'";
        }
        if (in_array('service_frequency', $exported_columns)) {
            $heading[] = 'Service Frequency';
            $str .= ", ppa.service_frequencies";
        }
        if (in_array('salesperson', $exported_columns)) {
            $heading[] = 'Salesperson';
            $str .= ", sou.first_name as 'salesperson'";
        }
        if (in_array('found_by_type', $exported_columns)) {
            $heading[] = 'Map Code';
            $str .= ", ppc.map_code";
        }
        if (in_array('map_code', $exported_columns)) {
            $heading[] = 'Service Type';
            $str .= ", pcst.name as 'service_type'";
        }
        if (in_array('autopay', $exported_columns)) {
            $heading[] = 'Autopay(Y/N)';
            $str .= ", csp.autopay";
        }
        if (in_array('date_signed_up', $exported_columns)) {
            $heading[] = 'Date signed up';
            $str .= ", pl.date_created";
        }
        if (in_array('initial_price', $exported_columns)) {
            $heading[] = 'Initial price';
            $str .= ", pa.initial_price";
        }
        if (in_array('recurring_price', $exported_columns)) {
            $heading[] = 'Recurring price';
            $str .= ", pa.regular_initial_price";
        }
        if (in_array('regular_initial_price', $exported_columns)) {
            $heading[] = 'Regular initial price';
            $str .= ", pa.regular_initial_price";
        }
        if (in_array('technician', $exported_columns)) {
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

        return Excel::download(new ExportLeadsProcess($heading, $data, $exported_columns), 'Leads.csv');
    }
}
