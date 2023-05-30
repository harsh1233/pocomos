<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosPestContractServiceType;

class ServiceTypeController extends Controller
{
    use Functions;

    /* API for list of Service type*/

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

        $PocomosPestContractServiceType = PocomosPestContractServiceType::where('office_id', $request->office_id)->where('active', 1);

        $PocomosPestContractServiceType = $PocomosPestContractServiceType->orderBy('position', 'ASC');

        if ($request->search) {
            $search = $request->search;
            $PocomosPestContractServiceType->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('position', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $PocomosPestContractServiceType->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosPestContractServiceType->skip($perPage * ($page - 1))->take($perPage);
        }
        $PocomosPestContractServiceType = $PocomosPestContractServiceType->get();

        return $this->sendResponse(true, 'List', [
            'Service_type' => $PocomosPestContractServiceType,
            'count' => $count,
        ]);
    }

    /* API for get details of Service type*/

    public function get($id)
    {
        $PocomosPestContractServiceType = PocomosPestContractServiceType::find($id);
        if (!$PocomosPestContractServiceType) {
            return $this->sendResponse(false, 'Service type Not Found');
        }
        return $this->sendResponse(true, 'Service type details.', $PocomosPestContractServiceType);
    }

    /* API for create of Service  type */

    public function create(Request $request)
    {
        $v = validator(
            $request->all(),
            [
                'office_id' => 'required|exists:pocomos_company_offices,id',
                'name' => 'required',
                'description' => 'nullable',
                'color' => 'required',
                'shows_on_estimates' => 'required|boolean',
                'requires_license' => 'required',
                'default_cost' => 'required',
            ]
        );

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }


        $query = PocomosPestContractServiceType::query();

        $pest = $query->whereName($request->name)->whereOfficeId($request->office_id)->where('active', 1)->count();

        if ($pest) {
            throw new \Exception(__('strings.message', ['message' => 'Name already exists']));
        }

        $officeId = $request->office_id;

        $input_details['office_id'] = $officeId;
        $input_details['description'] = $request->description ?? '';
        $input_details['name'] = $request->name;
        $input_details['color'] = $request->color;
        $input_details['requires_license'] = $request->requires_license;
        $input_details['shows_on_estimates'] = $request->shows_on_estimates;
        $input_details['default_cost'] = $request->default_cost;
        $input_details['active'] = 1;
        $input_details['position'] = 1;
        $PocomosPestContractServiceType =  PocomosPestContractServiceType::create($input_details);

        $query = "SELECT id FROM pocomos_pest_contract_service_types WHERE office_id = $officeId AND active = 1 ORDER BY position";

        $serviceTypes = DB::select(DB::raw($query));

        $position = 1;
        foreach ($serviceTypes as $serviceType) {
            $sql = "UPDATE pocomos_pest_contract_service_types SET position = $position WHERE office_id = $officeId AND id = $serviceType->id ";

            DB::select(DB::raw($sql));

            $position++;
        }

        /**End manage trail */
        return $this->sendResponse(true, 'Service type created successfully.', $PocomosPestContractServiceType);
    }

    /* API for update of Service  type*/

    public function update(Request $request)
    {
        $v = validator(
            $request->all(),
            [
                'service_type_id' => 'required|exists:pocomos_pest_contract_service_types,id',
                'office_id' => 'required|exists:pocomos_company_offices,id',
                'name' => 'required',
                'description' => 'nullable',
                'active' => 'required',
                'color' => 'required',
                'shows_on_estimates' => 'required|boolean',
                'requires_license' => 'required',
                'default_cost' => 'required',
            ]
        );

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = PocomosPestContractServiceType::query();

        $pest = $query->where('id', '!=',  $request->service_type_id)->whereName($request->name)->whereOfficeId($request->office_id)->where('active', 1)->count();

        if ($pest) {
            throw new \Exception(__('strings.message', ['message' => 'Name already exists']));
        }


        $PocomosPestContractServiceType = PocomosPestContractServiceType::where('office_id', $request->office_id)
            ->where('id', $request->service_type_id)->first();

        if (!$PocomosPestContractServiceType) {
            return $this->sendResponse(false, 'Service type not found.');
        }

        $input_details = $request->only('office_id', 'name', 'active', 'color', 'requires_license', 'shows_on_estimates', 'default_cost');

        $input_details['description'] =   $request->description ?? '';

        $PocomosPestContractServiceType->update($input_details);

        return $this->sendResponse(true, 'Service type updated successfully.', $PocomosPestContractServiceType);
    }

    /* API for changeStatus of  Reason */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'service_type_id' => 'required|exists:pocomos_pest_contract_service_types,id',
            'active' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestContractServiceType = PocomosPestContractServiceType::find($request->service_type_id);
        if (!$PocomosPestContractServiceType) {
            return $this->sendResponse(false, 'Service type not found');
        }

        $PocomosPestContractServiceType->update([
            'active' => $request->active
        ]);

        return $this->sendResponse(true, 'Status changed successfully.');
    }

    /**
     * API for reorder of Service type
     .
     *
     * @param  \Illuminate\Http\Request  $request, integer $id
     * @return \Illuminate\Http\Response
     */

    public function reorder(Request $request, $id)
    {
        $v = validator($request->all(), [
            'pos' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestContractServiceType = PocomosPestContractServiceType::where('id', $id)->where('office_id', $request->office_id)->first();
        if (!$PocomosPestContractServiceType) {
            return $this->sendResponse(false, 'Service type Not Found');
        }

        $is_reordered = false;
        $newPosition = $request->pos;
        $originalPosition = $PocomosPestContractServiceType->position;

        if ($newPosition === $originalPosition) {
            $is_reordered = true;
        }

        if (!$is_reordered) {
            $movedDown = $newPosition > $originalPosition;
            $videos = PocomosPestContractServiceType::where('office_id', $request->office_id)->where('active', true)->orderBy('id', 'asc')->get();
            foreach ($videos as $value) {
                $detail = PocomosPestContractServiceType::find($value->id);
                if ($value->id == $id) {
                    $position = $newPosition;
                } else {
                    $position = $detail->position;
                    if ($movedDown) {
                        if ($position > $originalPosition && $position <= $newPosition) {
                            $position--;
                        }
                    } elseif ($position <= $originalPosition && $position >= $newPosition) {
                        $position++;
                    }
                }
                $detail->position = $position;
                $detail->save();
            }
        }

        return $this->sendResponse(true, 'Service type reordered successfully.');
    }
}
