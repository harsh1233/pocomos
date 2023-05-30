<?php

namespace App\Http\Controllers\API\Pocomos\Vtp;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosVtpCertificationLevel;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class CertificationLevelController extends Controller
{
    use Functions;

    /**
     * API for list of Certification Levels
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = PocomosVtpCertificationLevel::whereActive(true)->whereOfficeId($request->office_id);

        /**For search functionality*/
        if ($request->search) {
            // $query
            // ->where('name', 'like', '%' . $request->search . '%')
            // ->orwhere('description', 'like', '%' . $request->search . '%');

            $search = '%'.$request->search.'%';

            $defaultStatus = 10;
            if ($request->search == 'yes') {
                $defaultStatus = 1;
            } elseif ($request->search == 'no') {
                $defaultStatus = 0;
            }

            $query->where(function ($query) use ($search, $defaultStatus) {
                $query->where('name', 'like', $search)
                    ->orwhere('description', 'like', $search)
                    ->orwhere('default_status', 'like', $defaultStatus);
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $query->count();
        $query->skip($perPage * ($page - 1))->take($perPage);

        $PocomosVtpCertificationLevel = $query->get();

        return $this->sendResponse(true, 'List of VTP Certification Levels.', [
            'certi_levels' => $PocomosVtpCertificationLevel,
            'count' => $count,
        ]);
    }


    public function listAll(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosVtpCertificationLevel = PocomosVtpCertificationLevel::whereActive(true)->whereOfficeId($request->office_id)->get();

        return $this->sendResponse(true, 'List of VTP Certification Levels.', [
            'certi_levels' => $PocomosVtpCertificationLevel
        ]);
    }

    /**
     * API for create of Certification Level
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id'         => 'required',
            'name'              => 'required',
            'description'       => 'required',
            'default_status'    => 'required|in:0,1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $input = $request->only('office_id', 'name', 'description', 'default_status')+['active' => true];

        $PocomosVtpCertificationLevel =  PocomosVtpCertificationLevel::create($input);

        return $this->sendResponse(true, 'Certification Level created successfully.', $PocomosVtpCertificationLevel);
    }

    /**
     * API for details of Certification Level
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosVtpCertificationLevel = PocomosVtpCertificationLevel::find($id);
        if (!$PocomosVtpCertificationLevel) {
            return $this->sendResponse(false, 'Certification Level Not Found');
        }
        return $this->sendResponse(true, 'Certification Level details.', $PocomosVtpCertificationLevel);
    }

    /**
     * API for update of Certification Level
     .
     *
     * @param  \Illuminate\Http\Request  $request, integer $id
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id'         => 'required',
            'name'              => 'required',
            'description'       => 'required',
            'default_status'    => 'required|in:0,1'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $PocomosVtpCertificationLevel = PocomosVtpCertificationLevel::whereId($id)->whereActive(true)->whereOfficeId($request->office_id)->first();
        if (!$PocomosVtpCertificationLevel) {
            return $this->sendResponse(false, 'Certification Level Not Found');
        }

        $PocomosVtpCertificationLevel->office_id       = $request->office_id;
        $PocomosVtpCertificationLevel->name            = $request->name;
        $PocomosVtpCertificationLevel->description     = $request->description;
        $PocomosVtpCertificationLevel->default_status  = $request->default_status;

        $PocomosVtpCertificationLevel->save();

        return $this->sendResponse(true, 'Certification Level updated successfully.');
    }

    /**
     * API for delete of Certification Level
     .
     *
     * @param  integer $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request, $id)
    {
        $PocomosVtpCertificationLevel = PocomosVtpCertificationLevel::whereId($id)->whereActive(true)->first();

        if (!$PocomosVtpCertificationLevel) {
            return $this->sendResponse(false, 'Certification Level Not Found');
        }
        $PocomosVtpCertificationLevel->active = false;
        $PocomosVtpCertificationLevel->save();
        return $this->sendResponse(true, 'Certification Level deleted successfully');
    }
}
