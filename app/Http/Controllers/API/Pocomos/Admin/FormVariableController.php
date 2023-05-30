<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosFormVariable;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class FormVariableController extends Controller
{
    use Functions;

    /**
     * API for list of Form Variable
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

        $PocomosFormVariable = PocomosFormVariable::orderBy('id', 'desc')->where('active', 1);

        $status = 10;
        if (stripos('enabled', $request->search)  !== false) {
            $status = 1;
        } elseif (stripos('disabled', $request->search) !== false) {
            $status = 0;
        }


        if ($request->search) {
            $PocomosFormVariable->where('variable_name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%')
                ->orWhere('long_description', 'like', '%' . $request->search . '%')
                ->orWhere('type', 'like', '%' . $request->search . '%')
                ->orWhere('enabled', 'like', '%' . $status . '%');
        }

        /**For pagination */
        $count = $PocomosFormVariable->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosFormVariable->skip($perPage * ($page - 1))->take($perPage);
        }
        $PocomosFormVariable = $PocomosFormVariable->get();

        return $this->sendResponse(true, 'List of Form Variable.', [
            'form_variables' => $PocomosFormVariable,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Form Variable
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosFormVariable = PocomosFormVariable::find($id);
        if (!$PocomosFormVariable) {
            return $this->sendResponse(false, 'Form Variable Not Found');
        }
        return $this->sendResponse(true, 'Form Variable details.', $PocomosFormVariable);
    }

    /**
     * API for create of Form Variable
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'description' => 'required',
            'long_description' => 'nullable',
            'variable_name' => 'required',
            'require_job' => 'required|boolean',
            'type' => 'required|array|in:Form Letter,Sms Form Letter,Pest Agreement,Pocomos Lead',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('description', 'long_description', 'variable_name', 'require_job', 'enabled') + ['active' => 1];

        $input_details['type'] =  serialize($request->input('type'));

        $PocomosFormVariable =  PocomosFormVariable::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Form Variable created successfully.', $PocomosFormVariable);
    }

    /**
     * API for update of Form Variable
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'formvariable_id' => 'required|exists:pocomos_form_variables,id',
            'description' => 'required',
            'long_description' => 'nullable',
            'variable_name' => 'required',
            'require_job' => 'required|boolean',
            'type' => 'required|array|in:Form Letter,Sms Form Letter,Pest Agreement,Pocomos Lead',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosFormVariable = PocomosFormVariable::find($request->formvariable_id);

        if (!$PocomosFormVariable) {
            return $this->sendResponse(false, 'Form Variable not found.');
        }

        $update_data['type'] =  serialize($request->input('type'));
        $update_data['description'] = $request->description;
        $update_data['long_description'] = $request->long_description;
        $update_data['variable_name'] = $request->variable_name;
        $update_data['require_job'] = $request->require_job;
        $update_data['enabled'] = $request->enabled;

        $PocomosFormVariable->update($update_data);

        return $this->sendResponse(true, 'Form Variable updated successfully.', $PocomosFormVariable);
    }

    /* API for changeStatus of  Form Variable */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'formvariable_id' => 'required|exists:pocomos_form_variables,id',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosFormVariable = PocomosFormVariable::find($request->formvariable_id);

        if (!$PocomosFormVariable) {
            return $this->sendResponse(false, 'Unable to find Form Variable Templates');
        }

        $PocomosFormVariable->update([
            'enabled' => $request->enabled
        ]);

        return $this->sendResponse(true, 'Status changed successfully.', $PocomosFormVariable);
    }

    /**
     * API for list of Form Variable based on type
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

     public function variableList(Request $request)
     {
        $v = validator($request->all(), [
            'type' => 'required|in:Form Letter,Sms Form Letter,Pest Agreement,Pocomos Lead'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $type = $request->type;
        $variables = PocomosFormVariable::where('active', true)->get()->toArray();

        foreach($variables as $key => $val){
            if(!in_array($type, $val['type_data'])){
                unset($variables[$key]);
            }
        }
        $variables = array_values($variables);
        return $this->sendResponse(true, __('strings.list', ['name' => 'Form variables']), [
            'variables' => $variables
        ]);
     }
}
