<?php

namespace App\Http\Controllers\API\Pocomos\Recruitement;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\Recruitement\OfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosRecruitOffice;
use App\Models\Pocomos\PocomosRecruitingOfficeConfiguration;

class OfficeController extends Controller
{
    use Functions;

    /**
     * API for list of Recruiting Office
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

        $query = PocomosRecruitOffice::with('office_configuration')->where('active', true);
        if ($PocomosRecruitStatus) {
            $query = $query->where('office_configuration_id', $PocomosRecruitStatus->id);
        }

        $PocomosRecruitOffice = $query->orderBy('id', 'desc');

        if ($request->search) {
            $search = $request->search;
            $PocomosRecruitOffice->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $PocomosRecruitOffice->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosRecruitOffice->skip($perPage * ($page - 1))->take($perPage);
        }

        $PocomosRecruitOffice = $PocomosRecruitOffice->get();

        return $this->sendResponse(true, 'List', [
            'PocomosRecruitOffice' => $PocomosRecruitOffice,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Recruiting Office
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosRecruitOffice = PocomosRecruitOffice::with('office_configuration')->find($id);
        if (!$PocomosRecruitOffice) {
            return $this->sendResponse(false, 'Recruiting Office Not Found');
        }
        return $this->sendResponse(true, 'Recruiting Office details.', $PocomosRecruitOffice);
    }

    /**
     * API for create of Recruiting Office
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

        $PocomosRecruitOffice =  PocomosRecruitOffice::create($input_details);


        return $this->sendResponse(true, 'Recruiting Office created successfully.', $PocomosRecruitOffice);
    }

    /**
     * API for update of Recruiting Office
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'recruiting_office_id' => 'required|exists:pocomos_recruiting_offices,id',
            'name' => 'required',
            'description' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruitOffice = PocomosRecruitOffice::find($request->recruiting_office_id);

        if (!$PocomosRecruitOffice) {
            return $this->sendResponse(false, 'Recruiting Office not found.');
        }

        $PocomosRecruitOffice->update(
            $request->only('name', 'description')
        );

        return $this->sendResponse(true, 'Recruiting Office updated successfully.', $PocomosRecruitOffice);
    }

    /**
     * API for delete of Recruiting Office
     .
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosRecruitOffice = PocomosRecruitOffice::find($id);
        if (!$PocomosRecruitOffice) {
            return $this->sendResponse(false, 'Recruiting Office not found.');
        }

        $PocomosRecruitOffice->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Recruiting Office deleted successfully.');
    }
}
