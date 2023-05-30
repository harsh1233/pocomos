<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTaxCode;
use DB;

class TaxCodeController extends Controller
{
    use Functions;

    /* API for list of Tax code company wise*/

    public function list(Request $request, $id)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $taxCodes = PocomosTaxCode::where('office_id', $id)->where('active', true);

        if ($request->search) {
            $search = $request->search;
            if ($search == 'Yes' || $search == 'yes') {
                $search = 1;
            } elseif ($search == 'No' || $search == 'no') {
                $search = 0;
            }

            $taxCodes = $taxCodes->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%');
                $q->orWhere('description', 'like', '%' . $search . '%');
                $q->orWhere('tax_rate', 'like', '%' . $search . '%');
                $q->orWhere('default_taxcode', 'like', '%' . $search . '%');
                $q->orWhere('enabled', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $taxCodes->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $taxCodes = $taxCodes->skip($perPage * ($page - 1))->take($perPage);
        }
        $taxCodes = $taxCodes->orderBy('id', 'desc')->get();

        $data = [
            'tax_codes' => $taxCodes,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Tax Codes']), $data);
    }

    /* API for list of Tax code company wise*/
    public function replaceTaxCodelist(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosVehicle = PocomosTaxCode::where('office_id', $request->office_id)->where('enabled', 1)->where('active', 1)->orderBy('default_taxcode', 'DESC')->get();

        return $this->sendResponse(true, 'List of Tax code.', $PocomosVehicle);
    }

    /* API for create of tax code */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'code' => 'required',
            'description' => 'nullable',
            'tax_rate' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $taxcode = PocomosTaxCode::where('code', request('code'))->where('office_id', request('office_id'))->first();

        if($taxcode){
            return $this->sendResponse(false, __('strings.message', ['message' => 'The code has already been taken.']));
        }

        $input_details = $request->only('office_id', 'code', 'tax_rate') + ['active' => true];
        $input_details['description'] = $request['description'] ?? '';

        $PocomosTaxCode =  PocomosTaxCode::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Tax code created successfully.', $PocomosTaxCode);
    }

    /* API for update of Service  type*/

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'tax_code_id' => 'required|exists:pocomos_tax_codes,id',
            'code' => 'required',
            'description' => 'nullable',
            'tax_rate' => 'required',
            'enabled' => 'required|in:0,1',
            'default_taxcode' => 'required|in:0,1',
            'new_tax_code_id' => 'exists:pocomos_tax_codes,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $taxcode = PocomosTaxCode::where('code', request('code'))->where('office_id', request('office_id'))->where('id', '!=', $request->tax_code_id)->first();

        if($taxcode){
            return $this->sendResponse(false, __('strings.message', ['message' => 'The code has already been taken.']));
        }

        $PocomosTaxCode = PocomosTaxCode::where('office_id', $request->office_id)->where('id', $request->tax_code_id)->first();

        if (!$PocomosTaxCode) {
            return $this->sendResponse(false, 'Tax Code not found.');
        }

        if (($request->enabled == 0) && ($PocomosTaxCode->default_taxcode == 1)) {
            return $this->sendResponse(false, 'Default Tax Codes cannot be disabled.');
        }

        if ($request->new_tax_code_id == $request->tax_code_id) {
            return $this->sendResponse(false, 'Cannot migrate to the same Tax Code.');
        }

        if ($request->default_taxcode == 1) {
            $data = DB::select(DB::raw("UPDATE pocomos_tax_codes
                SET default_taxcode = 0
                WHERE office_id = '$request->office_id'"));
        }

        if (($request->new_tax_code_id != $request->tax_code_id) && ($request->enabled == 1)) {
            $this->createTaxRecalculationJob($PocomosTaxCode, $PocomosTaxCode);
        }

        $input_details = $request->only('code', 'tax_rate', 'enabled', 'default_taxcode');
        $input_details['description'] = $request['description'] ?? '';

        $PocomosTaxCode->update($input_details);

        return $this->sendResponse(true, 'Tax code updated successfully.', $PocomosTaxCode);
    }

    /**Recalculate the tax code regarding pricing */
    public function recalculate(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'tax_code_id' => 'required|exists:pocomos_tax_codes,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $tax_code = PocomosTaxCode::where('office_id', $request->office_id)->where('id', $request->tax_code_id)->whereActive(true)->firstOrFail();

        if ($tax_code && $tax_code->enabled) {
            $this->createTaxRecalculationJob($tax_code, $tax_code);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Tax Code recalculation has started. Processing: From: ' . $tax_code->code . '  To : ' . $tax_code->code]));
    }

    /**
     * API for delete of tax code
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request)
    {
        $v = validator($request->all(), [
            'tax_code_id' => 'required|exists:pocomos_tax_codes,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCounty = PocomosTaxCode::find($request->tax_code_id);
        if (!$PocomosCounty) {
            return $this->sendResponse(false, 'Tax Code not found.');
        }

        $PocomosCounty->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Tax Code deleted successfully.');
    }
}
