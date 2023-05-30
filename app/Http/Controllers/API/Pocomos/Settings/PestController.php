<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosPest;
use App\Http\Controllers\Controller;

class PestController extends Controller
{
    use Functions;

    /**
     * API for list of Pest
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

        $pocomosPests = PocomosPest::where('office_id', $request->office_id)->where('active', 1);

        // if (isset($request->status)) {
        //     $pocomosPests = $pocomosPests->where('active', $request->status);
        // }

        if (isset($request->type)) {
            $pocomosPests = $pocomosPests->where('type', $request->type);
        }

        if ($request->search) {
            $search = $request->search;
            $pocomosPests = $pocomosPests->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('description', 'like', '%' . $search . '%');
                $q->orWhere('type', 'like', '%' . $search . '%');
                $q->orWhere('position', 'like', $search);
            });
        }

        /**For pagination */
        $count = $pocomosPests->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $pocomosPests = $pocomosPests->skip($perPage * ($page - 1))->take($perPage);
        }

        $pocomosPests = $pocomosPests->orderBy('position', 'asc')->get();

        $data = [
            'pests' => $pocomosPests,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Pests']), $data);
    }

    /**
     * API for details of Pest
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosPest = PocomosPest::find($id);
        if (!$PocomosPest) {
            return $this->sendResponse(false, 'Pest Not Found');
        }
        return $this->sendResponse(true, 'Pest details.', $PocomosPest);
    }

    /**
     * API for create of Pest
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
            'type' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = PocomosPest::query();

        $pest = $query->whereType($request->type)->whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->count();

        if ($pest) {
            throw new \Exception(__('strings.message', ['message' => 'Pest name under same category already exists']));
        }

        //change position of others by 1
        $pests = PocomosPest::where('office_id', $request->office_id)->where('active', 1)->get();

        if ($pests) {
            foreach ($pests as $pest) {
                $pest->update(['position' => $pest->position + 1]);
            }
        }

        $input = $request->only('office_id', 'name', 'type') + ['active' => true, 'position' => 1];

        $input['description'] = $request->description ?? '';

        $PocomosPest =  (clone ($query))->create($input);

        /**End manage trail */
        return $this->sendResponse(true, 'Pest created successfully.', $PocomosPest);
    }

    /**
     * API for update of Pest
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'pest_id' => 'required|exists:pocomos_pests,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'description' => 'nullable',
            'type' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPest = PocomosPest::where('id', $request->pest_id)->where('office_id', $request->office_id)->first();

        if (!$PocomosPest) {
            return $this->sendResponse(false, 'Pest not found.');
        }

        $pest = PocomosPest::where('id', '!=', $request->pest_id)
            ->whereType($request->type)->whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->first();

        if ($pest) {
            return $this->sendResponse(true, 'Pest name under same category already exists!');
        }

        $input = $request->only('office_id', 'name', 'type');

        $input['description'] = $request->description ?? '';

        $input['active'] = 1;

        $PocomosPest->update($input);

        return $this->sendResponse(true, 'Pest updated successfully.', $PocomosPest);
    }

    /**
     * API for delete of Pest
     .
     * @param  \Integer  $id
     * @param  \Illuminate\Http\Request  $request
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

        $PocomosPest = PocomosPest::find($id);
        if (!$PocomosPest) {
            return $this->sendResponse(false, 'Pest not found.');
        }

        $PocomosPest->active = false;
        $PocomosPest->save();

        $officeId = $request->office_id;

        $this->updatePestsPositions($officeId);

        return $this->sendResponse(true, 'Pest deleted successfully.');
    }

    /**
     * API for reorder of pest
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

        $PocomosPest = PocomosPest::where('id', $id)->where('office_id', $request->office_id)->first();
        if (!$PocomosPest) {
            return $this->sendResponse(false, 'Pest Not Found');
        }

        $is_reordered = false;
        $newPosition = $request->pos;
        $originalPosition = $PocomosPest->position;

        if ($newPosition === $originalPosition) {
            $is_reordered = true;
        }

        if (!$is_reordered) {
            $movedDown = $newPosition > $originalPosition;
            $videos = PocomosPest::where('office_id', $request->office_id)->where('active', true)->orderBy('id', 'asc')->get();
            foreach ($videos as $value) {
                $detail = PocomosPest::find($value->id);
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

        return $this->sendResponse(true, 'Pest reordered successfully.');
    }

    public function updatePestsPositions($officeId)
    {
        // return $officeId;

        $query = "SELECT id FROM pocomos_pests WHERE office_id = $officeId AND active = 1 ORDER BY position";

        $pests = DB::select(DB::raw($query));

        $position = 1;
        foreach ($pests as $pest) {
            $sql = "UPDATE pocomos_pests SET position = $position WHERE office_id = $officeId AND id = $pest->id ";

            DB::select(DB::raw($sql));

            $position++;
        }
    }
}
