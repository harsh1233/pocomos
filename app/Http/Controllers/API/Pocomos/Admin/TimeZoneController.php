<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTimezone;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class TimeZoneController extends Controller
{
    use Functions;

    /**
     * API for list of TimeZone
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

        $PocomosTimezone = PocomosTimezone::orderBy('id', 'desc');

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosTimezone->count();
        $PocomosTimezone->skip($perPage * ($page - 1))->take($perPage);

        $PocomosTimezone = $PocomosTimezone->get();

        return $this->sendResponse(true, 'List of TimeZone.', [
            'timezones' => $PocomosTimezone,
            'count' => $count,
        ]);
    }

    /**
     * API for details of TimeZone
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosTimezone = PocomosTimezone::find($id);
        if (!$PocomosTimezone) {
            return $this->sendResponse(false, 'TimeZone Not Found');
        }
        return $this->sendResponse(true, 'TimeZone details.', $PocomosTimezone);
    }

    /**
     * API for create of TimeZone
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'visible_name' => 'required',
            'php_name' => 'required',
            'daylight_savings' => 'required|boolean',
            'offset' => 'required',
            'active' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('visible_name', 'php_name', 'daylight_savings', 'offset', 'active');

        $PocomosTimezone =  PocomosTimezone::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'TimeZone created successfully.', $PocomosTimezone);
    }

    /**
     * API for update of TimeZone
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'timezone_id' => 'required|exists:pocomos_timezones,id',
            'visible_name' => 'required',
            'php_name' => 'required',
            'daylight_savings' => 'required|boolean',
            'offset' => 'required',
            'active' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosTimezone = PocomosTimezone::find($request->timezone_id);

        if (!$PocomosTimezone) {
            return $this->sendResponse(false, 'TimeZone not found.');
        }

        $PocomosTimezone->update(
            $request->only('visible_name', 'php_name', 'daylight_savings', 'offset', 'active')
        );

        return $this->sendResponse(true, 'TimeZone updated successfully.', $PocomosTimezone);
    }

    /* API for changeStatus of  TimeZone */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'timezone_id' => 'required|exists:pocomos_timezones,id',
            'active' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosTimezone = PocomosTimezone::find($request->timezone_id);

        if (!$PocomosTimezone) {
            return $this->sendResponse(false, 'Unable to find TimeZone');
        }

        $PocomosTimezone->update([
            'active' => $request->active
        ]);

        return $this->sendResponse(true, 'Status changed successfully.', $PocomosTimezone);
    }
}
