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
use App\Models\Pocomos\PocomosCustomerState;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class CustomerStateJob implements ShouldQueue
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
        Log::info("CustomerStateJob Job Started");

        $customerIds = $this->args['ids'];
        $lastJobs = $this->getCustomerJobDetails(
            $customerIds,
            /* selector */
            'date_completed',
            /* alias */
            'last_job',
            /* aggregateFunction */
            'MAX',
            /* statuses */
            array(config('constants.COMPLETE'))
        );

        $lastRegularJobs = $this->getCustomerJobDetails(
            $customerIds,
            /* selector */
            'date_completed',
            /* alias */
            'last_regular_job',
            /* aggregateFunction */
            'MAX',
            /* statuses */
            array(config('constants.COMPLETE')),
            /* type */
            config('constants.REGULAR')
        );

        $nextJobs = $this->getCustomerJobDetails(
            $customerIds,
            /* selector */
            'date_scheduled',
            /* alias */
            'next_job',
            /* aggregateFunction */
            'MIN',
            /* statuses */
            array(config('constants.PENDING'), config('constants.RESCHEDULED'))
        );

        $currentDate = date('Y-m-d H:i:s');

        $sqlIds = $this->convertArrayInStrings($customerIds);
        $daysPastDueSql = 'SELECT csp.customer_id AS id, MAX(DATEDIFF("' . $currentDate . '", i.date_due)-' . config('constants.PAST_DUE_AFTER_DAYS') . ') AS days_past_due
                            FROM pocomos_invoices i
                              JOIN pocomos_contracts c ON i.contract_id = c.id
                              JOIN pocomos_customer_sales_profiles csp ON c.profile_id = csp.id AND csp.customer_id IN (' . $sqlIds . ')
                              WHERE i.status IN (\'Due\', \'Past due\', \'Collections\', \'In collections\')
                              GROUP BY csp.customer_id';

        $cardOnFileSql = 'SELECT csp.customer_id AS id, COUNT(a.id) > 0 AS card_on_file
                            FROM orkestra_accounts a
                              JOIN pocomos_customers_accounts ca ON ca.account_id = a.id AND a.type = \'CardAccount\' AND a.active = true
                              JOIN pocomos_customer_sales_profiles csp ON ca.profile_id = csp.id AND csp.customer_id IN (' . $sqlIds . ')
                              GROUP BY csp.customer_id';


        $daysPastDues = DB::select(DB::raw($daysPastDueSql));
        $cardOnFiles = DB::select(DB::raw($cardOnFileSql));

        $mapped = array();
        foreach (array_merge(
            $lastJobs,
            $lastRegularJobs,
            $nextJobs,
            $daysPastDues,
            $cardOnFiles
        ) as $value) {
            $value = (array)$value;
            if (!isset($mapped[$value['id']])) {
                $mapped[$value['id']] = array();
            }

            $mapped[$value['id']] = array_merge($mapped[$value['id']], $value);
        }

        $balanceResult = $this->queryBalance($sqlIds);

        $i = 0;

        $chunkSize = 250;
        $clear = count($customerIds) > $chunkSize;
        while (count($customerIds) > 0) {
            $chunk = array_splice($customerIds, 0, $chunkSize);

            $results = DB::select(DB::raw("SELECT c.*, cs.*
            FROM pocomos_customers AS c
            LEFT JOIN pocomos_customer_state AS cs ON c.id = cs.customer_id
            WHERE c.id IN ($sqlIds)"));

            foreach ($results as $result) {
                $customer = $result;
                $state = PocomosCustomerState::find($customer->id);

                if (!$state) {
                    $state = new PocomosCustomerState();
                }

                $state->customer_id = $customer->customer_id;

                $customerId = $customer->customer_id;
                $data = array_merge(
                    array(
                        'next_job' => null,
                        'last_job' => null,
                        'last_regular_job' => null,
                        'card_on_file' => false,
                        'days_past_due' => 0,
                        'overall' => 0,
                        'outstanding' => 0,
                        'credit' => 0,
                    ),
                    isset($mapped[$customerId]) ? (array)$mapped[$customerId] : array(),
                    isset($balanceResult[$customerId]) ? (array)$balanceResult[$customerId] : array()
                );

                Log::info("CustomerStateJob Saving Customer State Data " . json_encode($data));

                $state->next_service_date = $data['next_job'] ? date('Y-m-d H:i:s', strtotime($data['next_job'])) : $data['next_job'];
                $state->last_service_date = $data['last_job'] ? date('Y-m-d H:i:s', strtotime($data['last_job'])) : $data['last_job'];
                $state->last_regular_service_date = $data['last_regular_job'] ? date('Y-m-d H:i:s', strtotime($data['last_regular_job'])) : $data['last_regular_job'];
                $state->card_on_file = $data['card_on_file'];
                $state->days_past_due = $data['days_past_due'] < 0 ? 0 : $data['days_past_due'];
                $state->balance_overall = $data['overall'];
                $state->balance_outstanding = $data['outstanding'];
                $state->balance_credit = $data['credit'];
                $state->active = true;
                $state->save();
            }
        }

        Log::info("CustomerStateJob Job End");
    }

    /**
     * @param $customerIds
     * @param $selector
     * @param $alias
     * @param string $aggregateFunction
     * @param array $statuses
     * @param null $type
     * @return array
     */
    private function getCustomerJobDetails($customerIds, $selector, $alias, $aggregateFunction = 'MAX', $statuses = array(), $type = null)
    {
        if ($aggregateFunction !== 'MAX') {
            $aggregateFunction = 'MIN';
        }
        $customerIds = $this->convertArrayInStrings($customerIds);

        $sql = 'SELECT csp.customer_id as id, ' . $aggregateFunction . '(j.' . $selector . ') AS ' . $alias . '
                FROM pocomos_jobs j
                JOIN pocomos_pest_contracts pcc ON j.contract_id = pcc.id
                JOIN pocomos_contracts c ON pcc.contract_id = c.id
                JOIN pocomos_customer_sales_profiles csp ON c.profile_id = csp.id AND csp.customer_id IN (' . $customerIds . ')';

        $whereConditions = '';
        if (count($statuses)) {
            $statuses = $this->convertArrayInStrings($statuses);
            $whereConditions .= ' j.status IN (' . $statuses . ') ';
        }

        if ($type) {
            $whereConditions .= " AND j.type = '$type' ";
        }

        if ($whereConditions != '') {
            $sql .= ' WHERE ' . $whereConditions;
        }
        $sql .= ' GROUP BY csp.customer_id';

        $data = DB::select(DB::raw($sql));

        return $data;
    }

    /**
     * @param $ids
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function queryBalance($ids)
    {
        // outstanding is the sum of balances on all due or worse invoices
        // credit is account credit + sum of prepayments (total amount due - balance) on all not due invoices
        $cancelledStatus = config('constants.CANCELLED');
        $outstandingStatuses = $this->convertArrayInStrings(array(config('constants.DUE'), config('constants.PAST_DUE'), config('constants.COLLECTIONS'), config('constants.IN_COLLECTIONS')));
        $jobStatus = config('constants.CANCELLED');

        $sql = '
            SELECT id, IFNULL(outstanding, 0) AS outstanding, (credit) AS credit
            FROM (
                SELECT cu.id,
                    SUM((
                      SELECT SUM(i.balance)
                      FROM pocomos_invoices i
                      LEFT JOIN pocomos_jobs j ON j.invoice_id = i.id
                      WHERE i.contract_id = sco.id
                        AND (i.status IN (' . $outstandingStatuses . '))
                        AND CASE
                            WHEN j.id IS NULL THEN 1
                            WHEN j.status <> "' . $jobStatus . '" THEN 1
                            ELSE 0
                        END = 1
                    )) as outstanding,
                    IFNULL((a.balance/100), 0) as credit
                FROM pocomos_customers cu
                    JOIN pocomos_customer_sales_profiles csp on csp.customer_id = cu.id
                    JOIN orkestra_accounts a on csp.points_account_id = a.id
                    JOIN pocomos_contracts sco on (sco.profile_id = csp.id AND sco.status <> "' . $cancelledStatus . '")
                WHERE cu.id IN (' . $ids . ')
                GROUP BY cu.id
            ) t
        ';

        $results = DB::select(DB::raw($sql));

        $mapped = array();
        foreach ($results as $result) {
            $result->overall = $result->outstanding - $result->credit;
            $mapped[$result->id] = $result;
        }

        return $mapped;
    }
}
