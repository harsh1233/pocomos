<?php

namespace App\Http\Controllers\API\Pocomos\Recruitement;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\Recruitement\OfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosRegion;
use App\Models\Pocomos\PocomosRecruitingOfficeConfiguration;

class RegionController extends Controller
{
    use Functions;

    /**
     * API for list of Recruiting Region
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruitStatus = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)->first();

        if ($PocomosRecruitStatus) {
            $PocomosRegion = PocomosRegion::where('active', 1)->where('office_configuration_id', $PocomosRecruitStatus->id)->orderBy('id', 'desc');
        } else {
            $PocomosRegion = PocomosRegion::where('active', 1)->orderBy('id', 'desc');
        }

        if ($request->search) {
            $search = $request->search;
            $PocomosRegion->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $PocomosRegion->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosRegion->skip($perPage * ($page - 1))->take($perPage);
        }

        $PocomosRegion = $PocomosRegion->get();

        return $this->sendResponse(true, 'List', [
            'PocomosRegion' => $PocomosRegion,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Recruiting Region
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosRegion = PocomosRegion::find($id);
        if (!$PocomosRegion) {
            return $this->sendResponse(false, 'Recruiting Region Not Found');
        }
        return $this->sendResponse(true, 'Recruiting Region details.', $PocomosRegion);
    }

    /**
     * API for create of Recruiting Region
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'description' => 'nullable',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruitStatus = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)->first();

        $input_details = $request->only('name', 'description');

        if ($PocomosRecruitStatus) {
            $input_details['office_configuration_id'] =  $PocomosRecruitStatus->id;
        }

        $input_details['active'] =  1;


        $PocomosRegion =  PocomosRegion::create($input_details);


        return $this->sendResponse(true, 'Recruiting Region created successfully.', $PocomosRegion);
    }

    /**
     * API for update of Recruiting Region
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'recruiting_region_id' => 'required|exists:pocomos_recruiting_region,id',
            'name' => 'required',
            'description' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRegion = PocomosRegion::find($request->recruiting_region_id);

        if (!$PocomosRegion) {
            return $this->sendResponse(false, 'Recruiting Region not found.');
        }

        $PocomosRegion->update(
            $request->only('name', 'description')
        );

        return $this->sendResponse(true, 'Recruiting Region updated successfully.', $PocomosRegion);
    }

    /**
     * API for delete of Recruiting Region
     .
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosRegion = PocomosRegion::find($id);
        if (!$PocomosRegion) {
            return $this->sendResponse(false, 'Recruiting Region not found.');
        }

        $PocomosRegion->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Recruiting Region deleted successfully.');
    }
}
