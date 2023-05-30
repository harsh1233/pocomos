<?php

namespace App\Http\Controllers\API\Pocomos\Inventory;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosVehicle;

class VehicleController extends Controller
{
    use Functions;

    /**
     * API for list of Vehicle
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

        $PocomosVehicle = PocomosVehicle::where('office_id', $request->office_id)->where('active', 1);

        if ($request->search) {
            $search = $request->search;
            $PocomosVehicle->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('year', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%')
                    ->orWhere('make', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosVehicle->count();
        $PocomosVehicle->skip($perPage * ($page - 1))->take($perPage);

        $PocomosVehicle = $PocomosVehicle->get();

        return $this->sendResponse(true, 'List', [
            'Vehicles' => $PocomosVehicle,
            'count' => $count,
        ]);
    }

    /**
     * API for get details of Vehicle.
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $Vehicle = PocomosVehicle::find($id);
        if (!$Vehicle) {
            return $this->sendResponse(false, 'Vehicle Not Found');
        }
        return $this->sendResponse(true, 'Vehicle details.', $Vehicle);
    }

    /**
     * API for create Vehicle.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'make' => 'required',
            'model' => 'required',
            'price' => 'required',
            'year' => 'required|integer|min:1|max:2099',
            'odometer' => 'required',
            'vin' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('office_id', 'name', 'vin', 'year', 'make', 'model', 'price', 'odometer') + ['active' => true];

        $Vehicle =  PocomosVehicle::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Vehicle created successfully.', $Vehicle);
    }

    /**
     * API for update Vehicle.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'vehicle_id' => 'required|exists:pocomos_vehicles,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'make' => 'required',
            'model' => 'required',
            'price' => 'required',
            'year' => 'required|integer|min:1|max:2099',
            'odometer' => 'required',
            'vin' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosVehicle = PocomosVehicle::find($request->vehicle_id);

        if (!$PocomosVehicle) {
            return $this->sendResponse(false, 'Vehicle not found.');
        }

        $PocomosVehicle->update(
            $request->only('office_id', 'name', 'vin', 'year', 'make', 'model', 'price', 'odometer')
        );

        return $this->sendResponse(true, 'Vehicle updated successfully.', $PocomosVehicle);
    }

    /**
     * API for delete Vehicle.
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosVehicle = PocomosVehicle::find($id);
        if (!$PocomosVehicle) {
            return $this->sendResponse(false, 'Vehicle not found.');
        }

        $PocomosVehicle->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Vehicle deleted successfully.');
    }
}
