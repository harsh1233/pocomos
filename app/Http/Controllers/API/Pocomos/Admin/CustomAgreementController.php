<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosCustomAgreementTemplate;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class CustomAgreementController extends Controller
{
    use Functions;

    /**
     * API for list of The agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomAgreementTemplate = PocomosCustomAgreementTemplate::orderBy('id', 'desc');

        if ($request->search) {
            $PocomosCustomAgreementTemplate->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        /**For pagination */
        $count = $PocomosCustomAgreementTemplate->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosCustomAgreementTemplate->skip($perPage * ($page - 1))->take($perPage);
        }
        $PocomosCustomAgreementTemplate = $PocomosCustomAgreementTemplate->get();

        return $this->sendResponse(true, 'List of The agreement.', [
            'custom_agreements' => $PocomosCustomAgreementTemplate,
            'count' => $count,
        ]);
    }

    /**
     * API for details of The agreement
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosCustomAgreementTemplate = PocomosCustomAgreementTemplate::find($id);
        if (!$PocomosCustomAgreementTemplate) {
            return $this->sendResponse(false, 'The agreement Not Found');
        }
        return $this->sendResponse(true, 'The agreement details.', $PocomosCustomAgreementTemplate);
    }

    /**
     * API for create of The agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'description' => 'nullable',
            'agreement_body' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('name', 'description', 'agreement_body') + ['active' => 1];

        $PocomosCustomAgreementTemplate =  PocomosCustomAgreementTemplate::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'The agreement created successfully.', $PocomosCustomAgreementTemplate);
    }

    /**
     * API for update of The agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'agreement_id' => 'required|exists:pocomos_custom_agreement_templates,id',
            'name' => 'required',
            'description' => 'nullable',
            'agreement_body' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomAgreementTemplate = PocomosCustomAgreementTemplate::find($request->agreement_id);

        if (!$PocomosCustomAgreementTemplate) {
            return $this->sendResponse(false, 'The agreement not found.');
        }

        $PocomosCustomAgreementTemplate->update(
            $request->only('name', 'description', 'agreement_body')
        );

        return $this->sendResponse(true, 'The agreement updated successfully.', $PocomosCustomAgreementTemplate);
    }

    /* API for changeStatus of  The agreement */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'agreement_id' => 'required|exists:pocomos_custom_agreement_templates,id',
            'active' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomAgreementTemplate = PocomosCustomAgreementTemplate::find($request->agreement_id);

        if (!$PocomosCustomAgreementTemplate) {
            return $this->sendResponse(false, 'Unable to find the Agreement Templates');
        }

        $PocomosCustomAgreementTemplate->update([
            'active' => $request->active
        ]);

        return $this->sendResponse(true, 'Status changed successfully.', $PocomosCustomAgreementTemplate);
    }
}
