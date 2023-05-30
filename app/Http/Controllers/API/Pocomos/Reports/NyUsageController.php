<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosJobProduct;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class NyUsageController extends Controller
{
    use Functions;

    /**
     * API for update Form Letter
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function nyUsageReport(Request $request)
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

        $pocomosnote = PocomosJobProduct::join('booking_process_payment_details as bpd', 'bpd.booking_process_id', '=', 'booking_process_course_details.booking_process_id')
                ->where('j.dateCompleted BETWEEN :startDate AND :endDate')
                ->andWhere('csp.office = :office')
                ->groupBy('ap.product')
                ->setParameter(':startDate', $data['startDate'])
                ->setParameter(':endDate', $data['endDate']->modify('+23 hours, 59 minutes, 59 seconds'))
                ->setParameter('office', $office);

        if ($request->input('types')) {
            $pocomosnote = $pocomosnote->whereIn('interaction_type', $request->input('types'));
        }

        if ($request->input('officeUser')) {
            $pocomosnote = $pocomosnote->where('user_id', $request->input('officeUser'));
        }

        $pocomosnotecount = $pocomosnote->count();

        /**For pagination */
        if ($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $pocomosnote->skip($perPage * ($page - 1))->take($perPage);
        }

        $pocomosnote = $pocomosnote
            ->orderBy('id', 'DESC')
            ->get();

        $data = [
            'pocomosnote' => $pocomosnote,
            'count' => $pocomosnotecount
        ];

        return $this->sendResponse(true, 'Data of reports.', $data);
    }
}
