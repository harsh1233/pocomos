<?php

namespace App\Http\Controllers\API\Pocomos\SalesTracker;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosOfficeBonuse;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class OfficeBonusController extends Controller
{
    use Functions;

    /**
     * API for list of Office Bonuse
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
            'search' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosOfficeBonuse = PocomosOfficeBonuse::whereActive(true)->whereOfficeId($request->office_id);

        if ($request->search) {
            $search = $request->search;
            $PocomosOfficeBonuse = $PocomosOfficeBonuse->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('accounts_needed', 'like', '%' . $search . '%');
                $q->orWhere('bonus_value', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosOfficeBonuse->count();
        $PocomosOfficeBonuse = $PocomosOfficeBonuse->skip($perPage * ($page - 1))->take($perPage)->orderBy('id', 'asc')->get();

        $data = [
            'bonus' => $PocomosOfficeBonuse,
            'count' => $count
        ];

        return $this->sendResponse(true, 'List of Office Bonus', $data);
    }

    /**
     * API for create of Office Bonus
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
            'bonus_value'       => 'required|numeric',
            'accounts_needed'   => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $input = $request->only('office_id', 'name', 'bonus_value', 'accounts_needed') + ['active' => true];

        $PocomosOfficeBonuse =  PocomosOfficeBonuse::create($input);

        return $this->sendResponse(true, 'Bonus added successfully.', $PocomosOfficeBonuse);
    }

    /**
     * API for details of Office Bonus
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosOfficeBonuse = PocomosOfficeBonuse::find($id);
        if (!$PocomosOfficeBonuse) {
            return $this->sendResponse(false, 'Office Bonus Not Found');
        }
        return $this->sendResponse(true, 'Office Bonus details.', $PocomosOfficeBonuse);
    }

    /**
     * API for update of Office Bonus
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
            'bonus_value'       => 'required',
            'accounts_needed'   => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $PocomosOfficeBonuse = PocomosOfficeBonuse::whereId($id)->whereActive(true)->first();
        if (!$PocomosOfficeBonuse) {
            return $this->sendResponse(false, 'Office Bonus Not Found');
        }

        $PocomosOfficeBonuse->office_id         = $request->office_id;
        $PocomosOfficeBonuse->name              = $request->name;
        $PocomosOfficeBonuse->bonus_value       = $request->bonus_value;
        $PocomosOfficeBonuse->accounts_needed   = $request->accounts_needed;

        $PocomosOfficeBonuse->save();

        return $this->sendResponse(true, 'Bonus updated successfully.');
    }

    /**
     * API for delete of Office Bonus
     .
     *
     * @param  integer $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request, $id)
    {
        $PocomosOfficeBonuse = PocomosOfficeBonuse::whereId($id)->whereActive(true)->first();

        if (!$PocomosOfficeBonuse) {
            return $this->sendResponse(false, 'Office Bonus Not Found');
        }
        $PocomosOfficeBonuse->active = false;
        $PocomosOfficeBonuse->save();
        return $this->sendResponse(true, 'Bonus removed successfully');
    }
}
