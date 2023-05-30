<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class NoteController extends Controller
{
    use Functions;

    /**
     * API for update Form Letter
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function allnotes(Request $request)
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

        $pocomosnote = PocomosNote::query();

        if ($request->input('start_date')) {
            $pocomosnote = $pocomosnote->whereDate('date_created', '>=', $request->input('start_date'));
        }

        if ($request->input('end_date')) {
            $pocomosnote = $pocomosnote->whereDate('date_created', '<=', $request->input('end_date'));
        }

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
