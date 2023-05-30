<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosBestfitThreshold;
use App\Models\Pocomos\PocomosPestOfficeSetting;

class ThresholdController extends Controller
{
    use Functions;

    /**
     * API for list of Threshold
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();

        $bestfitThreshold = PocomosBestfitThreshold::where('office_configuration_id', $officeSetting->id)->where('active', 1);

        if ($request->search) {
            $search = $request->search;

            $bestfitThreshold = $bestfitThreshold->where(function ($q) use ($search) {
                $q->where('threshold', 'like', '%' . $search . '%');
                $q->orWhere('color', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $bestfitThreshold->count();
        $bestfitThreshold = $bestfitThreshold->skip($perPage * ($page - 1))->take($perPage)->orderBy('id', 'desc')->get();

        $data = [
            'thresholds' => $bestfitThreshold,
            'count' => $count
        ];
        return $this->sendResponse(true, __('strings.list', ['name' => 'Thresholds']), $data);
    }

    /**
     * API for details of Threshold
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosBestfitThreshold = PocomosBestfitThreshold::find($id);
        if (!$PocomosBestfitThreshold) {
            return $this->sendResponse(false, 'Threshold Not Found');
        }
        return $this->sendResponse(true, 'Threshold details.', $PocomosBestfitThreshold);
    }

    /**
     * API for create of Threshold
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'threshold' => 'nullable|integer|min:0',
            'color' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();

        $input_details = $request->only('color');
        $input_details['office_configuration_id'] = $officeSetting->id;
        $input_details['default'] = 0;
        $input_details['active'] = 1;
        $input_details['threshold'] = $request->threshold ?? 0;

        $PocomosBestfitThreshold =  PocomosBestfitThreshold::create($input_details);

        return $this->sendResponse(true, 'Threshold created successfully.', $PocomosBestfitThreshold);
    }

    /**
     * API for update of Threshold
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'threshold_id' => 'required|exists:pocomos_bestfit_thresholds,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'threshold' => 'nullable',
            'color' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();

        $PocomosBestfitThreshold = PocomosBestfitThreshold::find($request->threshold_id);

        if (!$PocomosBestfitThreshold) {
            return $this->sendResponse(false, 'Threshold not found.');
        }

        $update_details = $request->only('color');

        $update_details['threshold'] = $request->threshold ?? 0;

        $PocomosBestfitThreshold->update(
            $update_details
        );

        return $this->sendResponse(true, 'Threshold updated successfully.', $PocomosBestfitThreshold);
    }

    /**
     * API for delete of Threshold
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'threshold_id' => 'required|exists:pocomos_bestfit_thresholds,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();

        $PocomosBestfitThreshold = PocomosBestfitThreshold::find($request->threshold_id);

        if (!$PocomosBestfitThreshold) {
            return $this->sendResponse(false, 'Threshold not found.');
        }

        $PocomosBestfitThreshold->update(['active' => 0]);

        return $this->sendResponse(true, 'Threshold deleted successfully.');
    }
}
