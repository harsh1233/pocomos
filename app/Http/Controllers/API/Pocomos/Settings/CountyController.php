<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosCounty;

class CountyController extends Controller
{
    use Functions;

    /**
     * API for list of County
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable',
            'status' => 'nullable|boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $status = $request->status ?? 1;
        $pocomosCounty = PocomosCounty::where('office_id', $request->office_id)->where('active', $status);

        // if (isset($request->status)) {
        //     $pocomosCounty = $pocomosCounty->where('active', $request->status);
        // }

        if ($request->search) {
            $search = $request->search;

            $pocomosCounty = $pocomosCounty->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $pocomosCounty->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $pocomosCounty = $pocomosCounty->skip($perPage * ($page - 1))->take($perPage);
        }
        $pocomosCounty = $pocomosCounty->orderBy('id', 'desc')->get();

        $data = [
            'counties' => $pocomosCounty,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'County']), $data);
    }

    /**
     * API for details of County
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosCounty = PocomosCounty::find($id);
        if (!$PocomosCounty) {
            return $this->sendResponse(false, 'County Not Found');
        }
        return $this->sendResponse(true, 'County details.', $PocomosCounty);
    }

    /**
     * API for create of County
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'active' => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosCounty::whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'County already exists.']));
        }

        $input_details = $request->only('office_id', 'name')  + ['active' => true];

        $PocomosCounty =  PocomosCounty::create($input_details);

        return $this->sendResponse(true, 'County created successfully.', $PocomosCounty);
    }

    /**
     * API for update of County
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'county_id' => 'required|exists:pocomos_counties,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'active' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosCounty::whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->where('id', '!=', $request->county_id)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'County already exists.']));
        }

        $PocomosCounty = PocomosCounty::where('id', $request->county_id)->where('office_id', $request->office_id)->first();

        if (!$PocomosCounty) {
            return $this->sendResponse(false, 'County not found.');
        }

        $PocomosCounty->update(
            $request->only('office_id', 'name', 'active')
        );

        return $this->sendResponse(true, 'County updated successfully.', $PocomosCounty);
    }

    /**
     * API for delete of County
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosCounty = PocomosCounty::find($id);
        if (!$PocomosCounty) {
            return $this->sendResponse(false, 'County not found.');
        }

        $PocomosCounty->active = 0;
        $PocomosCounty->save();

        return $this->sendResponse(true, 'County deleted successfully.');
    }
}
