<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosReportSalespersonSnapshot;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class PersonalReportController extends Controller
{
    use Functions;

    /**
     * API for update Form Letter
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function search(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;

        $query = PocomosReportSalespersonSnapshot::join('pocomos_salespeople as ps', 'pocomos_reports_salesperson_snapshots.salesperson_id', 'ps.id')
            ->whereBetween('pocomos_reports_salesperson_snapshots.date_created', [$request->start_date, $request->end_date]);

        $count = $query->count();

        /**For pagination */
        if ($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $query->skip($perPage * ($page - 1))->take($perPage);
        }

        $reportSalespersonSnapshots = $query->get();

        $data = [
            'personal_reports' => $reportSalespersonSnapshots,
            'count' => $count
        ];

        return $this->sendResponse(true, 'Personal reports.', $data);
    }
}
