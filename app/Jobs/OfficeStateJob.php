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

class OfficeStateJob implements ShouldQueue
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
        Log::info("OfficeStateJob Job Started");
        $ids = $this->args;
        $sqlIds = $this->convertArrayInStrings($ids);
        $currentDate = date('Y-m-d H:i:s');

        $sql = "SELECT os.office_id AS `id`, ROUND(AVG(os.customers)) AS `customers`, ROUND(AVG(os.users)) AS `users`, ROUND(AVG(os.salespeople)) AS `salespeople`
        FROM pocomos_reports_office_states os
        WHERE os.type = 'Snapshot' AND os.date_created >= DATE_SUB('$currentDate', INTERVAL 30 DAY)
        GROUP BY os.office_id";

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
            LEFT JOIN pocomos_reports_office_states AS os ON o.id = os.office_id
            WHERE o.id IN ($chunk)"));

            foreach ($results as $result) {
                $office = $result;
                if (!$office) {
                    Log::info("Query hydrated invalid type");
                    continue;
                }

                $state = new PocomosReportsOfficeState();
                $state->office_id = $office->id;

                $officeId = $office->id;
                $data = isset($mapped[$officeId]) ? $mapped[$officeId] : array(
                    'customers' => 0,
                    'salespeople' => 0,
                    'users' => 0
                );

                $data = (array)$data;
                $state->customers = $data['customers'];
                $state->users = $data['users'];
                $state->salespeople = $data['salespeople'];
                $state->active = true;
                $state->type = 'State';
                $state->save();
            }
        }

        Log::info("OfficeStateJob Job End");
    }
}
