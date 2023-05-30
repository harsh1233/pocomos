<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosSmsFormLetter;

class SmsFormLetterController extends Controller
{
    use Functions;

    /**
     * API for list of Form Letter
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

        $PocomosSmsFormLetter = PocomosSmsFormLetter::where('office_id', $request->office_id)->where('active', 1);

        if ($request->search) {
            $search = $request->search;
            $PocomosSmsFormLetter->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('message', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $PocomosSmsFormLetter->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosSmsFormLetter->skip($perPage * ($page - 1))->take($perPage);
        }
        $PocomosSmsFormLetter = $PocomosSmsFormLetter->orderBy('id', 'desc')->get();

        return $this->sendResponse(true, 'List', [
            'Form_Letter' => $PocomosSmsFormLetter,
            'count' => $count,
        ]);
    }

    /**
     * API for get details of Form Letter
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosSmsFormLetter = PocomosSmsFormLetter::find($id);
        if (!$PocomosSmsFormLetter) {
            return $this->sendResponse(false, 'Form Letter Not Found');
        }
        return $this->sendResponse(true, 'Form Letter details.', $PocomosSmsFormLetter);
    }

    /**
     * API for add  Form Letter
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'title' => 'required',
            'message' => 'required',
            'description' => 'required',
            'confirm_job' => 'nullable',
            'require_job' => 'nullable',
            'active' => 'required',
            'category' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('office_id', 'title', 'require_job', 'message', 'description', 'confirm_job', 'active', 'category');

        if (!$request->confirm_job) {
            $input_details['confirm_job'] = false;
        }

        $PocomosSmsFormLetter =  PocomosSmsFormLetter::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Form Letter created successfully.', $PocomosSmsFormLetter);
    }

    /**
     * API for update Form Letter
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'sms_form_letter_id' => 'required|exists:pocomos_sms_form_letters,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'title' => 'required',
            'message' => 'required',
            'description' => 'required',
            'confirm_job' => 'nullable',
            'require_job' => 'nullable',
            'active' => 'required',
            'category' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosSmsFormLetter = PocomosSmsFormLetter::where('office_id', $request->office_id)->where('id', $request->sms_form_letter_id)->where('active', 1)->first();

        if (!$PocomosSmsFormLetter) {
            return $this->sendResponse(false, 'Unable to find the Form Letter.');
        }

        $update_details = $request->only('title', 'require_job', 'message', 'description', 'confirm_job', 'active', 'category');

        if (!$request->confirm_job) {
            $update_details['confirm_job'] = false;
        }

        $PocomosSmsFormLetter->update(
            $update_details
        );

        return $this->sendResponse(true, 'Form Letter updated successfully.', $PocomosSmsFormLetter);
    }

    /**
     * API for delete Form Letter
     .
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosSmsFormLetter = PocomosSmsFormLetter::find($id);
        if (!$PocomosSmsFormLetter) {
            return $this->sendResponse(false, 'Unable to find the Form Letter.');
        }

        $PocomosSmsFormLetter->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Form Letter deleted successfully.');
    }
}
