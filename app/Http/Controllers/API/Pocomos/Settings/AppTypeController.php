<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosPestInvoiceSetting;

class AppTypeController extends Controller
{
    use Functions;

    /* API for list of Service */

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

        $PocomosPestContractServiceType = PocomosService::where('office_id', $request->office_id)->where('active', 1);

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
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosPestContractServiceType->count();
        $PocomosPestContractServiceType->skip($perPage * ($page - 1))->take($perPage);

        $PocomosPestContractServiceType = $PocomosPestContractServiceType->get();

        $pestInvoiceSetting = PocomosPestInvoiceSetting::whereOfficeId($request->office_id)
            ->firstorfail(['id', 'rename_application_type', 'application_type_text']);

        return $this->sendResponse(true, 'List', [
            'Service' => $PocomosPestContractServiceType,
            'invoice_setting' => $pestInvoiceSetting,
            'count' => $count,
        ]);
    }

    /* API for get details of Service */

    public function get($id)
    {
        $PocomosService = PocomosService::find($id);
        if (!$PocomosService) {
            return $this->sendResponse(false, 'Service Not Found');
        }
        return $this->sendResponse(true, 'Service details.', $PocomosService);
    }

    /* API for create of Service */

    public function create(Request $request)
    {
        $v = validator(
            $request->all(),
            [
                'office_id' => 'required|exists:pocomos_company_offices,id',
                'name' => 'required',
                'description' => 'nullable',
            ]
        );

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }


        $query = PocomosService::query();

        $pest = $query->whereName($request->name)->whereOfficeId($request->office_id)->where('active', 1)->count();

        if ($pest) {
            throw new \Exception(__('strings.message', ['message' => 'Name already exists']));
        }

        //change position of others by 1
        $services = PocomosService::where('office_id', $request->office_id)
            ->where('active', 1)->get();

        if ($services) {
            foreach ($services as $service) {
                // return $serviceType;
                $service->update(['position' => $service->position + 1]);
            }
        }

        $input_details = $request->only('office_id', 'name') + ['active' => true, 'position' => 1, 'description' => $request->description ?? ''];

        $PocomosService =  PocomosService::create($input_details);

        /**End manage trail */
        return $this->sendResponse(true, 'Application Type created successfully.', $PocomosService);
    }

    /* API for update of Service */

    public function update(Request $request)
    {
        $v = validator(
            $request->all(),
            [
                'service_id' => 'required|exists:pocomos_services,id',
                'office_id' => 'required|exists:pocomos_company_offices,id',
                'name' => 'required',
                'description' => 'nullable',
            ]
        );

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = PocomosService::query();

        $pest = $query->where('id', '!=',  $request->service_id)->whereName($request->name)->whereOfficeId($request->office_id)->where('active', 1)->count();

        if ($pest) {
            throw new \Exception(__('strings.message', ['message' => 'Name already exists']));
        }

        $PocomosService = PocomosService::where('office_id', $request->office_id)->where('id', $request->service_id)->first();

        if (!$PocomosService) {
            return $this->sendResponse(false, 'Application Type not found.');
        }

        $input_details = $request->only('name');

        $input_details['description'] =   $request->description ?? '';

        $PocomosService->update($input_details);

        return $this->sendResponse(true, 'Application Type updated successfully.', $PocomosService);
    }

    /* API for delete of Service */

    public function delete(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;

        $PocomosService = PocomosService::find($id);
        if (!$PocomosService) {
            return $this->sendResponse(false, 'Application Type not found.');
        }

        $PocomosService->update([
            'active' => 0
        ]);

        $query = "SELECT id FROM pocomos_services WHERE office_id = $officeId AND active = 1 ORDER BY position";

        $appTypes = DB::select(DB::raw($query));

        $position = 1;
        foreach ($appTypes as $appType) {
            $sql = "UPDATE pocomos_services SET position = $position WHERE office_id = $officeId AND id = $appType->id ";

            DB::select(DB::raw($sql));

            $position++;
        }

        return $this->sendResponse(true, 'Application Type deleted successfully.');
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

        $PocomosService = PocomosService::where('id', $id)->where('office_id', $request->office_id)->first();
        if (!$PocomosService) {
            return $this->sendResponse(false, 'Service Not Found');
        }

        $is_reordered = false;
        $newPosition = $request->pos;
        $originalPosition = $PocomosService->position;

        if ($newPosition === $originalPosition) {
            $is_reordered = true;
        }

        if (!$is_reordered) {
            $movedDown = $newPosition > $originalPosition;
            $videos = PocomosService::where('active', true)->where('office_id', $request->office_id)->orderBy('id', 'asc')->get();
            foreach ($videos as $value) {
                $detail = PocomosService::find($value->id);
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

        return $this->sendResponse(true, 'Application Type reordered successfully.');
    }

    public function updateInvoiceSetting(Request $request, $id)
    {
        $v = validator($request->all(), [
            'rename_application_type' => 'required|in:0,1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pestInvoiceSetting = PocomosPestInvoiceSetting::whereId($id)->first()->update([
            'rename_application_type' => $request->rename_application_type,
            'application_type_text'   => $request->application_type_text
        ]);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Application type configuration']));
    }
}
