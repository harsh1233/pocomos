<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosArea;
use Illuminate\Support\Facades\DB;

class AreaController extends Controller
{
    use Functions;

    /**
     * API for list of Area
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

        $pocomosArea = PocomosArea::where('office_id', $request->office_id)->whereActive(true);

        if ($request->search) {
            $search = $request->search;
            $pocomosArea = $pocomosArea->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('description', 'like', '%' . $search . '%');
                $q->orWhere('position', $search);
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $pocomosArea->count();
        $pocomosArea = $pocomosArea->skip($perPage * ($page - 1))->take($perPage)->orderBy('position')->get();

        $data = [
            'areas' => $pocomosArea,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Areas']), $data);
    }

    /**
     * API for get  Area details
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosArea = PocomosArea::find($id);
        if (!$PocomosArea) {
            return $this->sendResponse(false, 'Area Not Found');
        }
        return $this->sendResponse(true, 'Area details.', $PocomosArea);
    }

    /**
     * API for create of Area
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'description' => 'nullable',
            'showOnInvoice' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = PocomosArea::query();

        if (PocomosArea::whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'The name has already been taken']));
        }

        $areas = PocomosArea::where('office_id', $request->office_id)->where('active', 1)->get();

        if ($areas) {
            foreach ($areas as $pest) {
                $pest->update(['position' => $pest->position + 1]);
            }
        }

        $input = $request->only('office_id', 'name', 'showOnInvoice') + ['active' => true, 'position' => 1];

        $input['description'] = $request->description ?? '';

        $PocomosArea =  (clone ($query))->create($input);


        /**End manage trail */
        return $this->sendResponse(true, 'Area created successfully.', $PocomosArea);
    }

    /**
     * API for update of Area
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'area_id' => 'required|exists:pocomos_areas,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'description' => 'nullable',
            'showOnInvoice' => 'required',
            'position' => 'nullable',
            'active' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosArea::whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->where('id', '!=', $request->area_id)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'The name has already been taken']));
        }

        $PocomosArea = PocomosArea::where('id', $request->area_id)->where('office_id', $request->office_id)->first();

        if (!$PocomosArea) {
            return $this->sendResponse(false, 'Area not found.');
        }

        $input = $request->only('office_id', 'name', 'showOnInvoice');

        $input['description'] = $request->description ?? '';

        $input['active'] = 1;

        $PocomosArea->update($input);

        return $this->sendResponse(true, 'Area updated successfully.', $PocomosArea);
    }

    /**
     * API for delete of Area
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        // return $request->office_id;

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosArea = PocomosArea::find($id);
        if (!$PocomosArea) {
            return $this->sendResponse(false, 'Area not found.');
        }

        $PocomosArea->active = false;
        $PocomosArea->save();

        $officeId = $request->office_id;

        $this->updateareaPositions($officeId);

        return $this->sendResponse(true, 'Area deleted successfully.');
    }

    public function updateareaPositions($officeId)
    {
        // return $officeId;

        $query = "SELECT id FROM pocomos_areas WHERE office_id = $officeId AND active = 1 ORDER BY position";

        $pests = DB::select(DB::raw($query));

        $position = 1;
        foreach ($pests as $pest) {
            $sql = "UPDATE pocomos_areas SET position = $position WHERE office_id = $officeId AND id = $pest->id ";

            DB::select(DB::raw($sql));

            $position++;
        }
    }

    /**
     * API for reorder of area
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

        $PocomosArea = PocomosArea::where('id', $id)->where('office_id', $request->office_id)->first();
        if (!$PocomosArea) {
            return $this->sendResponse(false, 'Area Not Found');
        }

        $is_reordered = false;
        $newPosition = $request->pos;
        $originalPosition = $PocomosArea->position;

        if ($newPosition === $originalPosition) {
            $is_reordered = true;
        }

        if (!$is_reordered) {
            $movedDown = $newPosition > $originalPosition;
            $videos = PocomosArea::where('office_id', $request->office_id)->where('active', true)->orderBy('id', 'asc')->get();
            foreach ($videos as $value) {
                $detail = PocomosArea::find($value->id);
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

        return $this->sendResponse(true, 'Area reordered successfully.');
    }
}
