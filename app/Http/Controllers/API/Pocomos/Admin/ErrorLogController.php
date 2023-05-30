<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosErrorLog;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class ErrorLogController extends Controller
{
    use Functions;

    /**
     * API for list of Error Log
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosErrorLog = PocomosErrorLog::where('active', 1)->orderBy('id', 'desc');

        if ($request->search) {
            $PocomosErrorLog->where(function ($PocomosErrorLog) use ($request) {
                $PocomosErrorLog->where('level', 'like', '%' . $request->search . '%')
                    ->orWhere('channel', 'like', '%' . $request->search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosErrorLog->count();
        $PocomosErrorLog->skip($perPage * ($page - 1))->take($perPage);

        $PocomosErrorLog = $PocomosErrorLog->get();

        return $this->sendResponse(true, 'List of Error Log.', [
            'error_logs' => $PocomosErrorLog,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Error Log
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosErrorLog = PocomosErrorLog::find($id);
        if (!$PocomosErrorLog) {
            return $this->sendResponse(false, 'Error Log Not Found');
        }
        return $this->sendResponse(true, 'Error Log details.', $PocomosErrorLog);
    }
}
