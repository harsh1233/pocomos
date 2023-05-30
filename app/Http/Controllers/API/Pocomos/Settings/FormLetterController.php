<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosFormLetter;

class FormLetterController extends Controller
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

        $PocomosFormLetter = PocomosFormLetter::where('office_id', $request->office_id)->where('active', 1);

        if ($request->search) {
            $search = $request->search;
            $PocomosFormLetter->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('subject', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $PocomosFormLetter->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosFormLetter->skip($perPage * ($page - 1))->take($perPage);
        }
        $PocomosFormLetter = $PocomosFormLetter->orderBy('id', 'desc')->get();

        return $this->sendResponse(true, 'List', [
            'Form_Letter' => $PocomosFormLetter,
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
        $PocomosFormLetter = PocomosFormLetter::find($id);
        if (!$PocomosFormLetter) {
            return $this->sendResponse(false, 'Form Letter Not Found');
        }
        return $this->sendResponse(true, 'Form Letter details.', $PocomosFormLetter);
    }

    /**
     * API for create Form Letter
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
            'require_job' => 'nullable',
            'subject' => 'required',
            'description' => 'required',
            'body' => 'nullable',
            'active' => 'required',
            'confirm_job' => 'nullable',
            'category' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('office_id', 'title', 'require_job', 'subject', 'description', 'body', 'active', 'confirm_job', 'category');
        if (!$request->confirm_job) {
            $input_details['confirm_job'] = false;
        }

        $PocomosFormLetter =  PocomosFormLetter::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Form Letter created successfully.', $PocomosFormLetter);
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
            'form_letter_id' => 'required|exists:pocomos_form_letters,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'title' => 'required',
            'require_job' => 'nullable',
            'subject' => 'required',
            'description' => 'required',
            'body' => 'nullable',
            'active' => 'required',
            'confirm_job' => 'nullable',
            'category' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosFormLetter = PocomosFormLetter::where('office_id', $request->office_id)->where('id', $request->form_letter_id)->where('active', 1)->first();

        if (!$PocomosFormLetter) {
            return $this->sendResponse(false, 'Form Letter not found.');
        }

        $update_details = $request->only('office_id', 'title', 'require_job', 'subject', 'description', 'body', 'active', 'confirm_job', 'category');
        if (!$request->confirm_job) {
            $update_details['confirm_job'] = false;
        }

        $PocomosFormLetter->update(
            $update_details
        );

        return $this->sendResponse(true, 'Form Letter updated successfully.', $PocomosFormLetter);
    }

    /**
     * API for delete Form Letter
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosFormLetter = PocomosFormLetter::find($id);
        if (!$PocomosFormLetter) {
            return $this->sendResponse(false, 'Form Letter not found.');
        }

        $PocomosFormLetter->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Form Letter deleted successfully.');
    }
}
