<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosPestContractsTag;

class TagController extends Controller
{
    use Functions;

    /**
     * API for list of Tag
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
            'status' => 'nullable|boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $status = $request->status ?? 1;
        $pocomosTags = PocomosTag::where('office_id', $request->office_id)->where('active', $status);

        if ($request->search) {
            $search = $request->search;

            // if ($search == 'Visible' || $search == 'visible') {
            //     $search = 1;
            // } elseif ($search == 'Hidden' || $search == 'hidden') {
            //     $search = 0;
            // }

            $status = 10;
            if (stripos('visible', $request->search)  !== false) {
                $status = 1;
            } elseif (stripos('hidden', $request->search) !== false) {
                $status = 0;
            }

            $pocomosTags = $pocomosTags->where(function ($q) use ($search, $status) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('description', 'like', '%' . $search . '%');
                $q->orWhere('customer_visible', 'like', '%' . $status . '%');
            });
        }

        /**For pagination */
        $count = $pocomosTags->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $pocomosTags = $pocomosTags->skip($perPage * ($page - 1))->take($perPage);
        }
        $pocomosTags = $pocomosTags->orderBy('id', 'desc')->get();

        $data = [
            'tags' => $pocomosTags,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Tags']), $data);
    }

    /**
     * API for details of Tag
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosTag = PocomosTag::find($id);
        if (!$PocomosTag) {
            return $this->sendResponse(false, 'Tag Not Found');
        }
        return $this->sendResponse(true, 'Tag details.', $PocomosTag);
    }

    /**
     * API for create of Tag
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
            'customer_visible' => 'required',
            'active' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosTag::whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'Tag already exists.']));
        }

        $input_details = $request->only('office_id', 'name', 'description', 'customer_visible') + ['active' => true];

        if (!isset($request->description)) {
            $input_details['description'] = '';
        }

        $PocomosTag =  PocomosTag::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Tag created successfully.', $PocomosTag);
    }

    /**
     * API for update of Tag
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'tag_id' => 'required|exists:pocomos_tags,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'description' => 'nullable',
            'customer_visible' => 'required',
            'active' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if (PocomosTag::whereName($request->name)->where('active', 1)->whereOfficeId($request->office_id)->where('id', '!=', $request->tag_id)->count()) {
            throw new \Exception(__('strings.message', ['message' => 'Tag already exists.']));
        }

        $PocomosTag = PocomosTag::where('id', $request->tag_id)->where('office_id', $request->office_id)->first();

        if (!$PocomosTag) {
            return $this->sendResponse(false, 'Tag not found.');
        }

        $update_details = $request->only('office_id', 'name', 'description', 'customer_visible', 'active');

        if (!isset($update_details['description'])) {
            $update_details['description'] = '';
        }
        $PocomosTag->update(
            $update_details
        );

        return $this->sendResponse(true, 'Tag updated successfully.', $PocomosTag);
    }

    /**
     * API for delete of Tag
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosTag = PocomosTag::find($id);
        if (!$PocomosTag) {
            return $this->sendResponse(false, 'Tag not found.');
        }

        PocomosPestContractsTag::where('tag_id', $id)->delete();

        $PocomosTag->active = 0;
        $PocomosTag->save();

        return $this->sendResponse(true, 'Tag deleted successfully.');
    }
}
