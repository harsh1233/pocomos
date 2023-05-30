<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosZipCode;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use DB;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class ZipCodeController extends Controller
{
    use Functions;

    /**
     * API for list of Zipcode
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
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosZipCode = PocomosZipCode::where('office_id', $request->office_id)->where('deleted', 0);

        if ($request->search) {
            $search = $request->search;
        
            $sql = "SELECT pc.id
        FROM pocomos_zip_code AS pc
        LEFT JOIN pocomos_company_offices AS pa ON pa.id = pc.office_id
        LEFT JOIN pocomos_tax_codes AS tc ON tc.id = pc.tax_code_id
        LEFT JOIN orkestra_countries_regions AS oc ON oc.id = pc.state_id
        WHERE (pa.list_name like '%$search%' OR tc.code like '%$search%' OR tc.tax_rate like '%$search%' OR tc.description like '%$search%' OR oc.name like '%$search%' OR pc.zip_code like '%$search%' OR pc.city like '%$search%' OR pc.active like '%$search%'";

        if($search == 'Enabled' || $search == 'enabled'){
            $sql .= " OR pc.active = 1 ";
        }elseif($search == 'Disabled' || $search == 'disabled'){
            $sql .= " OR pc.active = 0 ";
        }
        $sql .= ")";

            $alertTeampIds = DB::select(DB::raw($sql));

            $alertIds = array_map(function ($value) {
                return $value->id;
            }, $alertTeampIds);

            $PocomosZipCode->WhereIn('id', $alertIds);
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosZipCode->count();
        $PocomosZipCode = $PocomosZipCode->skip($perPage * ($page - 1))->take($perPage);

        $PocomosZipCode = $PocomosZipCode->with(
            'office_details',
            'tax_code_details',
            'region_details',
        )->orderBy('id', 'DESC')->get();

        $data = [
            'Zipcode' => $PocomosZipCode,
            'count' => $count
        ];
        return $this->sendResponse(true, __('strings.list', ['name' => 'Zipcode']), $data);
    }

    /**
     * API for get  Zipcode details
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosZipCode = PocomosZipCode::find($id);
        if (!$PocomosZipCode) {
            return $this->sendResponse(false, 'Zipcode Not Found');
        }
        return $this->sendResponse(true, 'Zipcode details.', $PocomosZipCode);
    }

        
    /**
     * create zip code api
     *
     * @param  mixed $request
     * @return void
     */
    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id'   => 'required|exists:pocomos_company_offices,id',
            'zip_code'    => 'required',
            'city'        => 'nullable',
            'tax_code_id' => 'required|exists:pocomos_tax_codes,id',
            'state_id'    => 'required|exists:orkestra_countries_regions,id',
        ]);

        $PocomosZipCode = PocomosZipCode::where('zip_code', request('zip_code'))->where('office_id', request('office_id'))
        ->where('state_id', request('state_id'))
        // ->where('city', request('city'))
        ->first();
        if (!$PocomosZipCode) {
          
            $input = $request->only('office_id', 'zip_code', 'tax_code_id', 'state_id');
            $input['city'] = $request['city'] ?? '';
    
            $PocomosZipCode = PocomosZipCode::create($input);

            return $this->sendResponse(true, 'Zipcode created successfully.', $PocomosZipCode);
        }
        return $this->sendResponse(false, __('strings.message', ['message' => 'The code has already been taken.']));
    }

    public function getBranches(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = PocomosCompanyOffice::findorfail($request['office_id']);

        $officeId = $request['office_id'];

        $branches = PocomosCompanyOffice::whereActive(true)->where(function ($q) use ($officeId) {
            $q->whereId($officeId)
                ->orWhere('parent_id', $officeId)->orderBy('name')->select('id', 'parent_id', 'list_name');
        });
        $branches = $branches->get();

        return $this->sendResponse(true, 'List of branches', $branches);
    }

    /**
     * API for update of Zipcode
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'zip_code_id' => 'required|exists:pocomos_zip_code,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'zip_code' => 'required',
            'city' => 'nullable',
            'tax_code_id' => 'required|exists:pocomos_tax_codes,id',
            'state_id' => 'required|exists:orkestra_countries_regions,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosZipCode = PocomosZipCode::where('zip_code', request('zip_code'))->where('office_id', request('office_id'))
        ->where('state_id', request('state_id'))
        // ->where('city', request('city'))
        ->where('id', '!=', request('zip_code_id'))
        ->first();
        
        if ($PocomosZipCode) {
            return $this->sendResponse(false, __('strings.message', ['message' => 'The code has already been taken.']));
        }

        $PocomosZipCode = PocomosZipCode::where('id', $request->zip_code_id)->first();

        if (!$PocomosZipCode) {
            return $this->sendResponse(false, 'Zipcode not found.');
        }

        $input = $request->only('office_id', 'zip_code', 'city', 'tax_code_id', 'state_id');
        $input['city'] = $request['city'] ?? '';

        $result =  $PocomosZipCode->update($input);

        return $this->sendResponse(true, 'Zipcode updated successfully.', $PocomosZipCode);
    }

    /**
     * API for delete of Zipcode
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosZipCode = PocomosZipCode::find($id);
        if (!$PocomosZipCode) {
            return $this->sendResponse(false, 'Zipcode not found.');
        }

        $PocomosZipCode->update(['deleted' => 1]);

        return $this->sendResponse(true, 'Zipcode deleted successfully.');
    }


    /* API for changeStatus of  Zipcode */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'Zipcode_id' => 'required|exists:pocomos_zip_code,id',
            'active' => 'boolean|required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosZipCode = PocomosZipCode::find($request->Zipcode_id);
        if (!$PocomosZipCode) {
            return $this->sendResponse(false, 'Zipcode type not found');
        }

        $PocomosZipCode->update([
            'active' => $request->active
        ]);

        return $this->sendResponse(true, 'Status changed successfully.');
    }
}
