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
use App\Models\Pocomos\PocomosReportsOfficeState;

class OfficeSnapshotJob implements ShouldQueue
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
        Log::info("OfficeSnapshotJob Job Started");

        $ids = $this->args;
        $sqlIds = $this->convertArrayInStrings($ids);

        $sql = "SELECT oo.id, oo.name,
              COUNT(customers.custId) AS `customers`,
              (SELECT COUNT(DISTINCT ou.id) FROM pocomos_company_office_users ou
                JOIN pocomos_company_offices o ON ou.office_id = o.id
                JOIN orkestra_users u ON ou.user_id = u.id
                JOIN orkestra_user_groups ug ON ug.user_id = u.id
                JOIN orkestra_groups g ON ug.group_id = g.id
                WHERE ou.active = 1 AND u.active = 1 AND g.role <> 'ROLE_CUSTOMER' 
                AND o.id IN ($sqlIds) AND o.id = oo.id) AS `users`,
              (SELECT COUNT(DISTINCT ou.id) FROM pocomos_company_office_users ou
                JOIN pocomos_company_offices o ON ou.office_id = o.id
                JOIN orkestra_users u ON ou.user_id = u.id
                JOIN orkestra_user_groups ug ON ug.user_id = u.id
                JOIN orkestra_groups g ON ug.group_id = g.id
                WHERE o.id IN ($sqlIds) AND ou.active = 1 AND u.active = 1 AND g.role = 'ROLE_SALESPERSON' AND o.id = oo.id) AS `salespeople`
              FROM pocomos_company_offices oo
              LEFT JOIN (
                    SELECT c.id AS custId, o.id, o.parent_id
                    FROM pocomos_customers c
                JOIN pocomos_customer_sales_profiles csp ON csp.customer_id = c.id
                JOIN pocomos_company_offices o ON csp.office_id = o.id
                JOIN pocomos_contracts pc ON csp.id = pc.profile_id
                JOIN pocomos_pest_contracts pcc ON pc.id = pcc.contract_id
                JOIN pocomos_jobs j ON pcc.id = j.contract_id
                WHERE o.id IN ($sqlIds) AND c.status IN ('Active','On-Hold') AND j.status IN ('Pending','Re-scheduled')
                GROUP BY c.id
              ) AS customers
                    ON customers.id = oo.id
                OR customers.parent_id = oo.id
              WHERE oo.id IN ($sqlIds)
              GROUP BY oo.id";


        $values = DB::select(DB::raw($sql));

        $mapped = array();
        foreach ($values as $value) {
            $mapped[$value->id] = $value;
        }
        $i = 0;

        $chunkSize = 250;
        while (count($ids) > 0) {
            $chunk = array_splice($ids, 0, $chunkSize);
            $chunk = $this->convertArrayInStrings($chunk);

            $results = DB::select(DB::raw("SELECT o.*
            FROM pocomos_company_offices AS o
            WHERE o.id IN($chunk)"));

            foreach ($results as $result) {
                $office = $result;
                if (!$office) {
                    Log::info("Query hydrated invalid type");
                    continue;
                }

                $snapshot = new PocomosReportsOfficeState();
                $snapshot->office_id = $office->id;

                $officeId = $office->id;
                $data = isset($mapped[$officeId]) ? $mapped[$officeId] : array(
                    'customers' => 0,
                    'salespeople' => 0,
                    'users' => 0
                );
                $data = (array)$data;
                $snapshot->customers = $data['customers'];
                $snapshot->users = $data['users'];
                $snapshot->salespeople = $data['salespeople'];
                $snapshot->active = true;
                $snapshot->type = 'Snapshot';
                $snapshot->save();
            }
        }
        Log::info("OfficeSnapshotJob Job End");
    }
}
