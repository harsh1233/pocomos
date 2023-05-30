<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\PocomosContract;
use DB;

class FoundByTypeController extends Controller
{
    use Functions;

    /* API for list of Marketing Type */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'enabled' => 'boolean|nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosMarketingType = PocomosMarketingType::where('office_id', $request->office_id)->where('active', 1);

        if (isset($request->enabled)) {
            $PocomosMarketingType = $PocomosMarketingType->where('enabled', $request->enabled);
        }

        if ($request->search) {
            $search = $request->search;
            $PocomosMarketingType->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $PocomosMarketingType->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosMarketingType->skip($perPage * ($page - 1))->take($perPage);
        }
        $PocomosMarketingType = $PocomosMarketingType->get();

        return $this->sendResponse(true, 'List', [
            'Marketing_Type' => $PocomosMarketingType,
            'count' => $count,
        ]);
    }

    /* API for list other Marketing Type */

    public function getOtherMarketingTypes(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'marketing_type_id' => 'required|exists:pocomos_marketing_types,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosMarketingType = PocomosMarketingType::orderBy('id', 'desc');

        $PocomosMarketingType = $PocomosMarketingType->where('active', 1)->where('enabled', 1)->where('id', '!=', $request->marketing_type_id);

        if (isset($request->office_id)) {
            $PocomosMarketingType = $PocomosMarketingType->where('office_id', $request->office_id);
        }

        $PocomosMarketingType = $PocomosMarketingType->get();

        return $this->sendResponse(true, 'List of Marketing Type.', $PocomosMarketingType);
    }

    /* API for get details of Marketing Type */

    public function get($id)
    {
        $PocomosMarketingType = PocomosMarketingType::find($id);
        if (!$PocomosMarketingType) {
            return $this->sendResponse(false, 'Marketing Type Not Found');
        }
        return $this->sendResponse(true, 'Marketing Type details.', $PocomosMarketingType);
    }

    /* API for create of Marketing Type */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'enabled' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = PocomosMarketingType::query();

        $pest = $query->whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->count();

        if ($pest) {
            throw new \Exception(__('strings.message', ['message' => 'Marketing type name already exists']));
        }

        $input_details['office_id'] = $request->office_id;
        $input_details['description'] = $request->description ?? '';
        $input_details['name'] = $request->name;
        $input_details['enabled'] = $request->enabled;
        $input_details['active'] = 1;
        $PocomosMarketingType =  PocomosMarketingType::create($input_details);

        /**End manage trail */
        return $this->sendResponse(true, 'Marketing Type created successfully.', $PocomosMarketingType);
    }

    /* API for update of Marketing Type */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'marketing_type_id' => 'required|exists:pocomos_marketing_types,id',
            'new_marketing_type_id' => 'required_if:enabled,0|exists:pocomos_marketing_types,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pest = PocomosMarketingType::where('id', '!=', $request->marketing_type_id)->whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->first();

        if ($pest) {
            return $this->sendResponse(true, 'Marketing type name already exists!');
        }

        $PocomosMarketingType = PocomosMarketingType::where('office_id', $request->office_id)
            ->where('id', $request->marketing_type_id)->first();

        if (!$PocomosMarketingType) {
            return $this->sendResponse(false, 'Marketing Type not found.');
        }

        $input_details = $request->only('office_id', 'name', 'enabled');

        $input_details['description'] =   $request->description ?? '';

        $PocomosMarketingType->update($input_details);

        if ($request->enabled == 0) {
            $data = DB::select(DB::raw("UPDATE pocomos_lead_quotes
                SET found_by_type_id = '$request->new_marketing_type_id'
                WHERE found_by_type_id = '$request->marketing_type_id'"));


            $data = DB::select(DB::raw("UPDATE pocomos_import_batches
                SET found_by_type_id = '$request->new_marketing_type_id'
                WHERE found_by_type_id = '$request->marketing_type_id'"));

            $data = DB::select(DB::raw("UPDATE pocomos_import_customers
                SET found_by_type_id = '$request->new_marketing_type_id'
                WHERE found_by_type_id = '$request->marketing_type_id'"));

            $data = DB::select(DB::raw("UPDATE pocomos_contracts
                SET found_by_type_id = '$request->new_marketing_type_id'
                WHERE found_by_type_id = '$request->marketing_type_id'"));
        }

        return $this->sendResponse(true, 'Marketing Type updated successfully.', $PocomosMarketingType);
    }

    /* API for delete of Marketing Type */

    public function delete($id)
    {
        $PocomosMarketingType = PocomosMarketingType::findorfail($id);

        $contracts = PocomosContract::where('found_by_type_id', $id)->get();

        if (count($contracts) > 0) {
            return $this->sendResponse(false, 'The Marketing Type is currently used on ' . count($contracts) . ' Contracts. Please migrate those contracts to another one before deleting this one');
        }

        $PocomosMarketingType->delete();

        return $this->sendResponse(true, 'Marketing type deleted successfully.');
    }
}
