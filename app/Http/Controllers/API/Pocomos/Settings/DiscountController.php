<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosDiscount;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use Illuminate\Support\Facades\DB;

class DiscountController extends Controller
{
    use Functions;

    /**
     * API for list of discounttype
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

        $PocomosDiscount = PocomosDiscount::where('office_id', $request->office_id)->where('deleted', 0);

        if ($request->search) {
            $search = $request->search;
            $PocomosDiscount = $PocomosDiscount->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('description', 'like', '%' . $search . '%');
                $q->orWhere('position', $search);
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosDiscount->count();
        $PocomosDiscount = $PocomosDiscount->skip($perPage * ($page - 1))->take($perPage)->orderBy('position', 'ASC')->get();

        $data = [
            'discounttype' => $PocomosDiscount,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Discount Type']), $data);
    }

    /**
     * API for get  discounttype details
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosDiscount = PocomosDiscount::find($id);
        if (!$PocomosDiscount) {
            return $this->sendResponse(false, 'discounttype Not Found');
        }
        return $this->sendResponse(true, 'discounttype details.', $PocomosDiscount);
    }

    /**
     * API for create of Discount Type
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator(
            $request->all(),
            [
                'office_id' => 'required|exists:pocomos_company_offices,id',
                'description' => 'required',
                'value_type' => 'required|in:static,percent',
                'amount' => 'required',
                'modify_description' => 'required|boolean',
                'modify_rate' => 'required|boolean',
                'auto_renew' => 'required|boolean',
                'is_available' => 'required|boolean',
            ]
        );



        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = PocomosDiscount::query();

        $pest = $query->whereName($request->name)->whereOfficeId($request->office_id)->where('deleted', 0)->count();

        if ($pest) {
            throw new \Exception(__('strings.message', ['message' => 'Name already exists']));
        }

        //change position of others by 1
        $pests = PocomosDiscount::where('office_id', $request->office_id)->where('deleted', 0)->get();

        if ($pests) {
            foreach ($pests as $pest) {
                $pest->update(['position' => $pest->position + 1]);
            }
        }

        $input = $request->only('office_id', 'name', 'description', 'value_type', 'amount', 'modify_description', 'modify_rate', 'auto_renew', 'is_available') + ['active' => true, 'position' => 1];

        $PocomosDiscount =  (clone ($query))->create($input);

        /**End manage trail */
        return $this->sendResponse(true, 'Discount Type created successfully.', $PocomosDiscount);
    }

    /**
     * API for update of discounttype
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator(
            $request->all(),
            [
                'discounttype_id' => 'required|exists:pocomos_discounts,id',
                'office_id' => 'required|exists:pocomos_company_offices,id',
                'description' => 'required',
                'value_type' => 'required|in:static,percent',
                'amount' => 'required',
                'modify_description' => 'required|boolean',
                'modify_rate' => 'required|boolean',
                'auto_renew' => 'required|boolean',
                'is_available' => 'required|boolean',
            ]
        );

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = PocomosDiscount::query();

        $pest = $query->where('id', '!=',  $request->discounttype_id)->whereName($request->name)->whereOfficeId($request->office_id)->where('deleted', 0)->count();

        if ($pest) {
            throw new \Exception(__('strings.message', ['message' => 'Name already exists']));
        }

        $PocomosDiscount = PocomosDiscount::where('id', $request->discounttype_id)->where('office_id', $request->office_id)->where('deleted', 0)->first();

        if (!$PocomosDiscount) {
            return $this->sendResponse(false, 'Discount Type not found.');
        }

        $PocomosDiscount->update(
            $request->only('office_id', 'name', 'description', 'value_type', 'amount', 'modify_description', 'modify_rate', 'auto_renew', 'is_available')
        );

        return $this->sendResponse(true, 'Discount Type updated successfully.', $PocomosDiscount);
    }

    /**
     * API for delete of Discount Type
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosDiscount = PocomosDiscount::find($id);
        if (!$PocomosDiscount) {
            return $this->sendResponse(false, 'Discount Type not found.');
        }

        $PocomosDiscount->update(['deleted' => 1]);

        $officeId = $request->office_id;

        $this->updateDiscountPositions($officeId);

        return $this->sendResponse(true, 'Discount Type deleted successfully.');
    }

    public function updateDiscountPositions($officeId)
    {
        // return $officeId;

        $query = "SELECT id FROM pocomos_discounts WHERE office_id = $officeId AND deleted = 0 ORDER BY position";

        $pests = DB::select(DB::raw($query));

        $position = 1;
        foreach ($pests as $pest) {
            $sql = "UPDATE pocomos_discounts SET position = $position WHERE office_id = $officeId AND id = $pest->id ";

            DB::select(DB::raw($sql));

            $position++;
        }
    }

    /**
     * API for reorder of Discount Type
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

        $PocomosDiscount = PocomosDiscount::where('id', $id)->where('office_id', $request->office_id)->first();
        if (!$PocomosDiscount) {
            return $this->sendResponse(false, 'Discount Type Not Found');
        }

        $is_reordered = false;
        $newPosition = $request->pos;
        $originalPosition = $PocomosDiscount->position;

        if ($newPosition === $originalPosition) {
            $is_reordered = true;
        }

        if (!$is_reordered) {
            $movedDown = $newPosition > $originalPosition;
            $videos = PocomosDiscount::where('deleted', 0)->where('office_id', $request->office_id)->orderBy('id', 'asc')->get();
            foreach ($videos as $value) {
                $detail = PocomosDiscount::find($value->id);
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

        return $this->sendResponse(true, 'Discount Type reordered successfully.');
    }


    /* API for changeStatus of  Discount Type */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'discounttype_id' => 'required|exists:pocomos_discounts,id',
            'active' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosDiscount = PocomosDiscount::find($request->discounttype_id);
        if (!$PocomosDiscount) {
            return $this->sendResponse(false, 'Discount Type type not found');
        }

        $PocomosDiscount->update([
            'active' => $request->active
        ]);

        return $this->sendResponse(true, 'Status changed successfully.');
    }


    /* Edits an existing OfficeConfiguration entity.*/
    public function updateDiscountTypeAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'enable_discount_type' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosPestOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        $PocomosPestOfficeSetting->update([
            'enable_discount_type' => $request->enable_discount_type
        ]);

        return $this->sendResponse(true, 'Discount type configuration updated successfully.');
    }

    /* Edits an existing OfficeConfiguration entity.*/
    public function getDiscountTypeAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->select('enable_discount_type')
            ->first();

        if (!$PocomosPestOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        return $this->sendResponse(true, 'Discount type configuration.', $PocomosPestOfficeSetting);
    }
}
