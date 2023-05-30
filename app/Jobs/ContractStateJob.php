<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ContractStateJob implements ShouldQueue
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
        Log::info("ContractStateJob Job Started");

        $ids = $this->args['ids'];
        $currentDate = date('Y-m-d H:i:s');

        $lastYearDate = date('Y-m-d H:i:s', strtotime('-1 year'));
        $lastSixMonthsDate = date('Y-m-d H:i:s', strtotime('-6 month'));

        Log::info("ContractStateJob Job customer ids ".json_encode($ids));

        $chunkSize = 100;
        while (count($ids) > 0) {
            //$chunk = array_splice($ids, 0, $chunkSize);
            //$sqlIds = implode(',', $chunk);
            $sqlIds = array_splice($ids, 0, $chunkSize);
            $sqlIds = $this->convertArrayInStrings($sqlIds);
            $query = "
            SELECT
                contracts.id,
                contracts.original_value,
                ppc.modifiable_original_value as contract_value,
                pastDue.past_due_count,
                completedJob.completed_jobs,
                completedJob.initial_service_date
            FROM pocomos_contracts contracts
            JOIN pocomos_pest_contracts ppc on contracts.id = ppc.contract_id
            LEFT JOIN (
                SELECT contracts.id,
                    COUNT(invoices.id) as past_due_count
                FROM pocomos_invoices invoices
                INNER JOIN pocomos_contracts contracts
                    ON invoices.contract_id = contracts.id
                INNER JOIN pocomos_customer_sales_profiles customer_sales_profiles
                    ON contracts.profile_id = customer_sales_profiles.id
                INNER JOIN pocomos_customers customers
                    ON customer_sales_profiles.customer_id = customers.id
                WHERE invoices.status IN ('Past Due', 'Collections', 'In collections')
                    AND customers.active = true
                    AND customers.status ='Active'
                    AND contracts.id IN ($sqlIds)
                GROUP BY contracts.id
            ) pastDue
                ON contracts.id = pastDue.id
            LEFT JOIN (
                SELECT
                    contracts.id,
                    COUNT(jobs.id) as completed_jobs,
                    MIN(jobs.date_scheduled) as initial_service_date
                FROM pocomos_jobs jobs
                INNER JOIN pocomos_pest_contracts pest_contracts
                    ON jobs.contract_id = pest_contracts.id
                INNER JOIN pocomos_contracts contracts
                    ON pest_contracts.contract_id = contracts.id
                WHERE contracts.id IN ($sqlIds)
                GROUP BY contracts.id
            ) completedJob
                ON contracts.id = completedJob.id
            WHERE contracts.id IN ($sqlIds)
            GROUP BY contracts.id
            ";
            // $params = array(
            //     'contractIds' => $sqlIds,
            //     'jobStatus' => JobStatus::COMPLETE,
            //     'currentDate' => $currentDate->format('Y-m-d'),
            //     'lastSixMonthsDate' => $lastSixMonthsDate->format('Y-m-d'),
            //     'lastYearDate' => $lastYearDate->format('Y-m-d'),
            // );

            // $types = array(
            //     'contractIds' => Connection::PARAM_INT_ARRAY,
            //     'jobStatus' => \PDO::PARAM_STR,
            //     'currentDate' => \PDO::PARAM_STR,
            //     'lastSixMonthsDate' => \PDO::PARAM_STR,
            //     'lastYearDate' => \PDO::PARAM_STR,
            // );

            $values = DB::select(DB::raw($query));

            // dump('SQL COUNT:'.count($sqlIds));
            // dump('VALUE COUNT:'.count($values));
            $mapped = array();
            foreach ($values as $value) {
                $value = (array)$value;
                if (!isset($mapped[$value['id']])) {
                    $mapped[$value['id']] = array();
                }

                $mapped[$value['id']]['value'] = $value['contract_value'];
                $mapped[$value['id']]['has_past_due'] = $value['past_due_count'] > 0;
                $mapped[$value['id']]['has_completed_job'] = $value['completed_jobs'] > 0;
                $mapped[$value['id']]['original_value'] = $value['original_value'];
                $mapped[$value['id']]['initial_service_date'] = new \DateTime($value['initial_service_date']);
            }

            $query = '
                SELECT 
                    contract_state.id,
                    pest_contracts.contract_id,
                    contracts.salesperson_id,
                    contract_state.value,
                    contract_state.manual_value,
                    contracts.status,
                    customers.status AS customer_status,
                    contracts.date_start,
                    contracts.date_end
                FROM pocomos_pest_contracts pest_contracts
                INNER JOIN pocomos_contracts	contracts
                    ON contracts.id = pest_contracts.contract_id
                INNER JOIN pocomos_customer_sales_profiles profile
                    ON contracts.profile_id = profile.id
                INNER JOIN pocomos_customers customers
                    ON customers.id = profile.customer_id
                LEFT JOIN pocomos_reports_contract_states contract_state
                    ON contract_state.contract_id = contracts.id
                LEFT JOIN pocomos_salespeople salespeople
                    ON salespeople.id = contracts.salesperson_id
                LEFT JOIN pocomos_salespeople salespeople_profile
                    ON salespeople_profile.id = profile.salesperson_id
                WHERE contracts.id IN ('.$sqlIds.')
                GROUP BY contracts.id
            ';

            $results = DB::select(DB::raw($query));
            //dump(count($results));
            //sleep(10);
            //die('I R DEAD');
            foreach ($results as $result) {
                $result = (array)$result;

                /**
                 * Get the data first
                 */
                $data = isset($mapped[$result['contract_id']]) ? $mapped[$result['contract_id']] : array();

                /* Cal Values do not use */
                $oldValue = $result['value'];
                $oldManualValue = $result['manual_value'];
                /* End Cal Values */

                $manual_value = $result['manual_value'];
                if ($oldManualValue === null || abs($oldValue - $oldManualValue) < 0.001) {
                    $manual_value = $data['value'];
                }
                $params = array(
                    'salesperson_id' => $result['salesperson_id'],
                    'contract_id' => $result['contract_id'],
                    //I know. It's not logical. But if this code is being executed - then it means no one fixed the issue.
                    // The whole job is FUBAR and needs to be rebuilt from the ground up. So please, forgive my shitty code. Igor 07.01.16 (Eurostyle)
                    'original_value' => ($data['value'] ?: 0),
                    'actual_original_value' => ($data['original_value'] ?: 0),
                    'account_status' => $this->getNewAccountState($result['status'], $result['customer_status'], $data),
                    'past_due' => isset($data['has_past_due']) && $data['has_past_due'] ? 1 : 0,
                    'initial_service_date' => isset($data['initial_service_date']) ? $data['initial_service_date']->format('Y-m-d H:i:s') : null,
                    'currentDate' => $currentDate,
                    'manual_value' => $manual_value
                );

                $set = "
                    SET 
                    `contract_id` = ".$params['contract_id'].",
                    `original_value` = ".$params['original_value'].",
                    `actual_original_value` = ".$params['actual_original_value'].",
                    `account_status` = '".$params['account_status']."',
                    `past_due` = ".$params['past_due'].",
                    `initial_service_date` = '".$params['initial_service_date']."',
                    `date_modified` = '".$params['currentDate']."',
                    `manual_value` = ".$params['manual_value']."
                ";

                // `salesperson_id` = ".$params['salesperson_id'].",

                $query = "
                    INSERT INTO pocomos_reports_contract_states
                    " . $set . ",
                        active = 1,
                        date_created = '".$params['currentDate']."'
                ";
                if ($result['id'] != '') {
                    $query = "
                        UPDATE pocomos_reports_contract_states
                        " . $set . "
                        WHERE id = ".$result['id']."
                    ";
                }
                // dd($query);
                DB::select(DB::raw($query));
            }
        }

        Log::info("ContractStateJob Job End");
    }

    /**
     * @param $contract_status
     * @param $customer_status
     * @param array $data
     * @return string
     */
    private function getNewAccountState($contract_status, $customer_status, array $data)
    {
        $status = 'Pending';

        if (isset($data['has_past_due']) && $data['has_past_due']) {
            $status = 'Past due';
        } elseif ($contract_status == 'Cancelled'
            || $customer_status == 'Inactive'
        ) {
            $status = 'Cancelled';
        } elseif (isset($data['has_completed_job']) && $data['has_completed_job']) {
            $status = 'Serviced';
        }

        return $status;
    }
}
