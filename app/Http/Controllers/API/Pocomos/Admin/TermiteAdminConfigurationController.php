<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTermiteStateForm;
use App\Models\Pocomos\PocomosTermiteStateFormFindingType;
use DB;

class TermiteAdminConfigurationController extends Controller
{
    use Functions;

    /**
     * API for list of The state form
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'search' => 'nullable',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosTermiteStateForm = PocomosTermiteStateForm::orderBy('id', 'desc');

        if ($request->search) {
            $PocomosTermiteStateForm->where(function ($PocomosTermiteStateForm) use ($request) {
                $PocomosTermiteStateForm->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosTermiteStateForm->count();
        $PocomosTermiteStateForm->skip($perPage * ($page - 1))->take($perPage);
        $PocomosTermiteStateForm = $PocomosTermiteStateForm->get();

        return $this->sendResponse(true, __('strings.list', ['name'=> 'The state form']), [
            'termite_state_forms' => $PocomosTermiteStateForm,
            'count' => $count,
        ]);
    }

    /**
     * API for details of The state form
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */
    public function get($id)
    {
        $PocomosTermiteStateForm = PocomosTermiteStateForm::findOrFail($id);
        return $this->sendResponse(true, __('strings.details', ['name' => 'State form']), $PocomosTermiteStateForm);
    }

    /**
     * API for create of The state form
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
            'form_body' => 'nullable',
            'active' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('name', 'description', 'form_body', 'active');

        $PocomosTermiteStateForm =  PocomosTermiteStateForm::create($input_details);

        /**End manage trail */
        return $this->sendResponse(true, __('strings.create', ['name' => 'State form']), $PocomosTermiteStateForm);
    }

    /**
     * API for update of The state form
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'state_form_id' => 'required|exists:pocomos_termite_state_forms,id',
            'name' => 'required',
            'description' => 'nullable',
            'form_body' => 'nullable',
            'active' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosTermiteStateForm = PocomosTermiteStateForm::findOrFail($request->state_form_id);
        $PocomosTermiteStateForm->update(
            $request->only('name', 'description', 'form_body', 'active')
        );

        return $this->sendResponse(true, __('strings.update', ['name' => 'State form']), $PocomosTermiteStateForm);
    }

    /**
     * API for delete of The state form
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        PocomosTermiteStateForm::findOrFail($id)->delete();
        return $this->sendResponse(true, __('strings.delete', ['name' => 'State form']));
    }

    /**
     * API for list of The state form
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function findingTypeList(Request $request)
    {
        $v = validator($request->all(), [
            'search' => 'nullable',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $res = DB::table('pocomos_termite_state_forms as ca')
        ->join('pocomos_termite_state_forms_finding_type as sa', 'ca.id', '=', 'sa.state_form_id');

        if ($request->search) {
            $search = $request->search;
            $res = $res->where(function ($q) use ($search) {
                $q->where('sa.name', 'LIKE', '%'.$search.'%');
                $q->orWhere('sa.description', 'LIKE', '%'.$search.'%');
                $q->orWhere('ca.name', 'LIKE', '%'.$search.'%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $res->count();
        $res->skip($perPage * ($page - 1))->take($perPage);
        $res = $res->orderBy('sa.id', 'desc')->select('sa.*', 'ca.name as state_form_name')->get();
        ;

        return $this->sendResponse(true, __('strings.list', ['name' => 'State form finding type']), [
            'finding_type_list' => $res,
            'count' => $count,
        ]);
    }

    /**
     * API for details of The state form
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */
    public function findingTypeGet($id)
    {
        $PocomosTermiteStateFormFindingType = PocomosTermiteStateFormFindingType::findOrFail($id);
        return $this->sendResponse(true, __('strings.details', ['name' => 'State form finding type']), $PocomosTermiteStateFormFindingType);
    }

    /**
     * API for create of The state form
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function findingTypeCreate(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'description' => 'nullable',
            'finding_id' => 'required',
            'state_form_id' => 'required',
            'active' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('name', 'description', 'finding_id', 'state_form_id', 'active');

        $PocomosTermiteStateFormFindingType =  PocomosTermiteStateFormFindingType::create($input_details);

        /**End manage trail */
        return $this->sendResponse(true, __('strings.create', ['name' => 'State form finding type']), $PocomosTermiteStateFormFindingType);
    }

    /**
     * API for update of The state form finding type
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function findingTypeUpdate(Request $request)
    {
        $v = validator($request->all(), [
            'finding_type_id' => 'required|exists:pocomos_termite_state_forms_finding_type,id',
            'state_form_id' => 'required|exists:pocomos_termite_state_forms,id',
            'name' => 'required',
            'description' => 'nullable',
            'finding_id' => 'nullable',
            'active' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $res = PocomosTermiteStateFormFindingType::findOrFail($request->finding_type_id);
        $res->update(
            $request->only('name', 'description', 'finding_id', 'active', 'state_form_id')
        );

        return $this->sendResponse(true, __('strings.update', ['name' => 'State form']), $res);
    }

    /**
     * API for delete of The state form finding type
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */
    public function findingTypedelete($id)
    {
        PocomosTermiteStateFormFindingType::findOrFail($id)->delete();
        return $this->sendResponse(true, __('strings.delete', ['name' => 'State form find type']));
    }
}
