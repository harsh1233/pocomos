<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SearchStateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
        Log::info("SearchStateJob Job Started");

        $ids = $this->args['ids'];
        $currentDate = date('Y-m-d H:i:s');

        Log::info("SearchStateJob Job customer ids ".json_encode($ids));

        $chunkSize = 150;
        while (count($ids) > 0) {
            $chunk = array_splice($ids, 0, $chunkSize);
            $sqlIds = implode(',', $chunk);
            $mainDataSql = <<<END
            SELECT
                rss.id 										AS 'id',
                co.id                                       AS 'contract_id',
                c.id                                        AS 'customer_id',
                c.external_account_id                       AS 'external_account_id',
                pcc.id                                      AS 'pest_contract_id',
                o.name                                      AS 'office',
                c.status                                    AS 'status',
                CONCAT(c.first_name, ' ', c.last_name)      AS 'name',
                c.billing_name                              AS 'billing_name',
                c.company_name                              AS 'company_name',
                c.email                                     AS 'email',
                c.account_type                              AS 'account_type',
                IFNULL(c.secondary_emails, '')              AS 'secondary_email',
                TRIM(CONCAT(ca.street, ' ', ca.suite))      AS 'street',
                ca.postal_code                              AS 'postal_code',
                ca.city                                     AS 'city',
                car.name                                    AS 'region',
                IFNULL(pn.number, '')                       AS 'phone',
                CONCAT(TRIM(CONCAT(ca.street, ' ', ca.suite)), ', ', ca.city, ', ', car.code, ' ', ca.postal_code) AS 'address',
                TRIM(CONCAT(ba.street, ' ', ba.suite))      AS 'billing_street',
                ba.postal_code                              AS 'billing_postal_code',
                ba.city                                     AS 'billing_city',
                bar.name                                    AS 'billing_region',
                IFNULL(ss.name, '')                         AS 'sales_status',
                CONCAT(DATE_FORMAT(co.date_start, "%c/%d/%y"), ' - ', DATE_FORMAT(co.date_end, "%c/%d/%y")) AS 'contract_dates',
                CONCAT(su.first_name, ' ', su.last_name)    AS 'salesperson',
                fbt.name                                    AS 'found_by_type',
                IFNULL(tc.code, '')                         AS 'tax_code',
                a.name                                      AS 'agreement_type',
                csp.autopay                                 AS 'autopay',
                st.name                                     AS 'service_type',
                pcc.service_frequency                       AS 'service_frequency',
                DATE_FORMAT(pcc.date_created, "%Y/%m/%d")   AS 'pcc_date_created',
                pcc.initial_price                           AS 'initial_price',
                pcc.recurring_price                         AS 'recurring_price',
                pcc.regular_initial_price                   AS 'regular_initial_price',
                IFNULL(CONCAT(tu.first_name, ' ', tu.last_name), '')    AS 'preferred_technician',
                (SELECT IFNULL(GROUP_CONCAT(p.name SEPARATOR ', '), '') FROM pocomos_pests p JOIN pocomos_pest_contracts_pests pccp ON pccp.pest_id = p.id WHERE pccp.contract_id = pcc.id) AS 'pests',
                (SELECT IFNULL(GROUP_CONCAT(p.name SEPARATOR ', '), '') FROM pocomos_pests p JOIN pocomos_pest_contracts_specialty_pests pccp ON pccp.pest_id = p.id WHERE pccp.contract_id = pcc.id) AS 'specialty_pests',
                (SELECT IFNULL(GROUP_CONCAT(t.name SEPARATOR ', '), '') FROM pocomos_tags t JOIN pocomos_pest_contracts_tags pcct ON pcct.tag_id = t.id WHERE pcct.contract_id = pcc.id) AS 'tags',
                cs.last_service_date                        AS 'last_service_date',
                cs.last_regular_service_date                AS 'last_regular_service_date',
                cs.next_service_date                        AS 'next_service_date',
                cs.balance_overall                          AS 'balance',
                (csp.autopay_account_id IS NOT NULL)        AS 'card_or_ach_on_file',
                (SELECT MIN(j.date_scheduled) FROM pocomos_jobs j WHERE j.contract_id = pcc.id GROUP BY pcc.id) AS 'initial_job_date',
                IF(c.id = sc.parent_id, 1, 0)               AS is_parent,
                IF(c.id = sc.child_id, 1, 0)                AS is_child,
                pcc.map_code                                AS 'map_code',
                csp.date_signed_up
            FROM pocomos_customers c
            JOIN pocomos_customer_sales_profiles csp ON csp.customer_id = c.id
            JOIN pocomos_company_offices o ON csp.office_id = o.id
            JOIN pocomos_addresses ca ON c.contact_address_id = ca.id
            LEFT JOIN orkestra_countries_regions car ON ca.region_id = car.id
            LEFT JOIN pocomos_addresses ba ON c.billing_address_id = ba.id
            LEFT JOIN orkestra_countries_regions bar ON ba.region_id = bar.id
            JOIN pocomos_contracts co ON co.profile_id = csp.id
            LEFT JOIN pocomos_sales_status ss ON co.sales_status_id = ss.id
            JOIN pocomos_salespeople s ON co.salesperson_id = s.id
            JOIN pocomos_company_office_users sou ON s.user_id = sou.id
            JOIN orkestra_users su ON sou.user_id = su.id
            JOIN pocomos_pest_contracts pcc ON pcc.contract_id = co.id
            LEFT JOIN pocomos_reports_search_state rss ON pcc.id = rss.pest_contract_id
            JOIN pocomos_marketing_types fbt ON co.found_by_type_id = fbt.id
            JOIN pocomos_tax_codes tc ON co.tax_code_id = tc.id
            JOIN pocomos_agreements a ON co.agreement_id = a.id
            JOIN pocomos_pest_contract_service_types st ON pcc.service_type_id = st.id
            LEFT JOIN pocomos_customer_state cs ON cs.customer_id = c.id
            LEFT JOIN pocomos_technicians t ON pcc.technician_id = t.id
            LEFT JOIN pocomos_company_office_users tou ON t.user_id = tou.id
            LEFT JOIN orkestra_users tu ON tou.user_id = tu.id
            LEFT JOIN pocomos_sub_customers sc on (c.id = sc.parent_id OR c.id = sc.child_id)
            LEFT JOIN pocomos_phone_numbers pn 
                ON pn.id = (
                    SELECT
                        MIN(phone_id)
                    FROM pocomos_customers_phones cp
                    JOIN pocomos_phone_numbers n ON cp.phone_id = n.id and n.active = true
                    WHERE profile_id = csp.`id`
                )
            WHERE pcc.id IN ({$sqlIds})
            GROUP BY co.id,
                c.id,
                pcc.id
            END;

            $mainData = DB::select(DB::raw($mainDataSql));

            foreach ($mainData as $result) {
                foreach ((array)$result as $key => $data) {
                    if ($data === null) {
                        $result->$key = '';
                    }
                }

                $params = array(
                    'contract_id' => $result->contract_id ?? ``,
                    'external_account_id' => $result->external_account_id ?? `` ?? ``,
                    'customer_id' => $result->customer_id ?? ``,
                    'pest_contract_id' => $result->pest_contract_id ?? ``,
                    'office' => $result->office ?? ``,
                    'status' => $result->status ?? ``,
                    'name' => $result->name ?? ``,
                    'billing_name' => $result->billing_name ?? ``,
                    'company_name' => $result->company_name ?? ``,
                    'account_type' => $result->account_type ?? ``,
                    'email' => $result->email ?? ``,
                    'secondary_email' => $result->secondary_email ?? ``,
                    'street' => $result->street ?? ``,
                    'postal_code' => $result->postal_code ?? ``,
                    'city' => $result->city ?? ``,
                    'region' => $result->region ?? ``,
                    'phone' => $result->phone ?? ``,
                    'address' => $result->address ?? ``,
                    'billing_street' => $result->billing_street ?? ``,
                    'billing_postal_code' => $result->billing_postal_code ?? ``,
                    'billing_city' => $result->billing_city ?? ``,
                    'billing_region' => $result->billing_region ?? ``,
                    'sales_status' => $result->sales_status ?? ``,
                    'contract_dates' => $result->contract_dates ?? ``,
                    'salesperson' => $result->salesperson ?? ``,
                    'found_by_type' => $result->found_by_type ?? ``,
                    'tax_code' => $result->tax_code ?? ``,
                    'agreement_type' => $result->agreement_type ?? ``,
                    'autopay' => $result->autopay,
                    'service_type' => $result->service_type ?? ``,
                    'service_frequency' => $result->service_frequency ?? ``,
                    'pcc_date_created' => $result->pcc_date_created ?? ``,
                    'initial_price' => $result->initial_price ?? ``,
                    'recurring_price' => $result->recurring_price ?? ``,
                    'regular_initial_price' => $result->regular_initial_price ?? ``,
                    'preferred_technician' => $result->preferred_technician ?? ``,
                    'pests' => $result->pests ?? ``,
                    'specialty_pests' => $result->specialty_pests ?? ``,
                    'tags' => $result->tags ?? ``,
                    'last_service_date' => empty($result->last_service_date) ? null : $result->last_service_date,
                    'last_regular_service_date' => empty($result->last_regular_service_date) ? null : $result->last_regular_service_date,
                    'next_service_date' => empty($result->next_service_date) ? null : $result->next_service_date,
                    'balance' => empty($result->balance) ? 0 : $result->balance,
                    'card_or_ach_on_file' => $result->card_or_ach_on_file,
                    'initial_job_date' => $result->initial_job_date ?? ``,
                    'is_parent' => $result->is_parent,
                    'is_child' => $result->is_child,
                    'map_code' => $result->map_code ?? ``,
                    'date_signed_up' => $result->date_signed_up ?? ``,
                    'currentDate' => $currentDate ?? ``,
                );

                $last_technician = $this->getLastTechName($result->pest_contract_id);
                if ($last_technician != 'None') {
                    $last_technician = (array)$last_technician;
                    $params['last_technician'] = $last_technician['techName'] ?? 'None';
                } else {
                    $params['last_technician'] = $last_technician;
                }

                $params['current_contracts'] = $this->getAgreements($result->customer_id);

                $set = "
                    SET
                    `pest_contract_id` = ".$params['pest_contract_id'].",
                    `customer_id` = ".$params['customer_id'].",
                    `contract_id` = ".$params['contract_id'].",
                    `office` = '".$params['office']."',
                    `status` = '".$params['status']."',
                    `name` = '".$params['name']."',
                    `billing_name` = '".$params['billing_name']."',
                    `company_name` = '".$params['company_name']."',
                    `account_type` = '".$params['account_type']."',
                    `email` = '".$params['email']."',
                    `secondary_email` = '".$params['secondary_email']."',
                    `street` = '".$params['street']."',
                    `postal_code` = '".$params['postal_code']."',
                    `city` = '".$params['city']."',
                    `region` = '".$params['region']."',
                    `phone` = '".$params['phone']."',
                    `address` = '".$params['address']."',
                    `billing_street` = '".$params['billing_street']."',
                    `billing_postal_code` = ".$params['billing_postal_code'].",
                    `billing_city` = '".$params['billing_city']."',
                    `billing_region` = '".$params['billing_region']."',
                    `sales_status` = '".$params['sales_status']."',
                    `contract_dates` = '".$params['contract_dates']."',
                    `salesperson` = '".$params['salesperson']."',
                    `found_by_type` = '".$params['found_by_type']."',
                    `tax_code` = '".$params['tax_code']."',
                    `agreement_type` = '".$params['agreement_type']."',
                    `autopay` = ".$params['autopay'].",
                    `service_type` = '".$params['service_type']."',
                    `service_frequency` = '".$params['service_frequency']."',
                    `pcc_date_created` = '".$params['pcc_date_created']."',
                    `initial_price` = ".$params['initial_price'].",
                    `recurring_price` = ".$params['recurring_price'].",
                    `regular_initial_price` = ".$params['regular_initial_price'].",
                    `preferred_technician` = '".$params['preferred_technician']."',
                    `pests` = '".$params['pests']."',
                    `specialty_pests` = '".$params['specialty_pests']."',
                    `tags` = '".$params['tags']."',
                    `balance` = ".$params['balance'].",
                    `card_or_ach_on_file` = ".$params['card_or_ach_on_file'].",
                    `current_contracts` = '".$params['current_contracts']."',
                    `last_technician` = '".$params['last_technician']."',
                    `is_parent` = ".$params['is_parent'].",
                    `is_child` = ".$params['is_child'].",
                    `date_modified` = '".$params['currentDate']."',
                    `map_code` = '".$params['map_code']."',
                    `external_account_id` = '".$params['external_account_id']."',
                    `date_signed_up` = '".$params['date_signed_up']."'
                ";

                if ($params['initial_job_date']) {
                    $set .= ",`initial_job_date` = '".$params['initial_job_date']."'";
                }
                if ($params['last_service_date']) {
                    $set .= ",`last_service_date` = '".$params['last_service_date']."'";
                }
                if ($params['last_regular_service_date']) {
                    $set .= ",`last_regular_service_date` = '".$params['last_regular_service_date']."'";
                }
                if ($params['next_service_date']) {
                    $set .= ",`next_service_date` = '".$params['next_service_date']."'";
                }

                $query = "
                    INSERT INTO pocomos_reports_search_state
                    " . $set . ",
                        active = 1,
                        `date_created` = '".$params['currentDate']."'
                ";

                if ($result->id != '') {
                    $query = "
                        UPDATE pocomos_reports_search_state
                        " . $set . "
                        WHERE id = $result->id
                    ";
                }
                DB::select(DB::raw($query));
            }
        }

        Log::info("SearchStateJob Job End");
    }

    /**
     * @param $pest_contract_id
     * @return string
     */
    public function getLastTechName($pest_contract_id)
    {
        $query = "
            SELECT CONCAT(o.first_name,' ',o.last_name,' ||| ',o.username) as techName
                FROM pocomos_pest_contracts ppc
                JOIN pocomos_jobs pj ON ppc.id = pj.contract_id
                JOIN pocomos_technicians pt on pj.technician_id = pt.id
                JOIN pocomos_company_office_users u on pt.user_id = u.id
                JOIN orkestra_users o on u.user_id = o.id
                
                WHERE pj.status = '".config('constants.COMPLETE')."' AND ppc.id = $pest_contract_id
                ORDER BY pj.date_completed DESC
                LIMIT 1
        ";

        $data = DB::select(DB::raw($query));
        if (array_key_exists(0, $data)) {
            return $data[0];
        }
        return 'None';
    }

    /**
     * @param $customer_id
     * @return mixed
     */
    public function getAgreements($customer_id)
    {
        $query = "
            SELECT GROUP_CONCAT(pocomos_agreements.name SEPARATOR ',') as agreements
            FROM pocomos_customer_sales_profiles
            LEFT JOIN pocomos_contracts
                ON pocomos_customer_sales_profiles.id = pocomos_contracts.profile_id
            LEFT JOIN pocomos_agreements
                ON pocomos_contracts.agreement_id = pocomos_agreements.id
            WHERE pocomos_customer_sales_profiles.customer_id = $customer_id
        ";
        $data = DB::select(DB::raw($query));

        return $data[0]->agreements ?? null;
    }
}
