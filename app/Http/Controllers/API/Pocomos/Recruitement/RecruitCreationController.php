<?php

namespace App\Http\Controllers\API\Pocomos\Recruitement;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\Recruitement\OfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosRecruit;

class RecruitCreationController extends Controller
{
    use Functions;

    /**
     * API for list of Recruiting Agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $PocomosRecruit = PocomosRecruit::orderBy('id', 'desc')
            ->get();

        return $this->sendResponse(true, 'List of Recruiting Agreement.', $PocomosRecruit);
    }

    /**
     * API for details of Recruiting Agreement
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosRecruit = PocomosRecruit::find($id);
        if (!$PocomosRecruit) {
            return $this->sendResponse(false, 'Recruiting Agreement Not Found');
        }
        return $this->sendResponse(true, 'Recruiting Agreement details.', $PocomosRecruit);
    }

    /**
     * API for create of Recruiting Agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
             'firstName' => 'nullable',
            'lastName' => 'nullable',
            'email' => 'nullable',
            'recruitAgreement' =>  'required|boolean',
            'dateStart' => 'nullable',
            'dateEnd' => 'nullable',
            'notes' =>'nullable',
            'address_for_pdf' =>'nullable|email',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('recruiting_office_configuration_id ', 'description', 'name', 'agreement_body', 'initials', 'default_agreement', 'active', 'email_pdf', 'address_for_pdf');

        $PocomosRecruit =  PocomosRecruit::create($input_details);


        return $this->sendResponse(true, 'Recruiting Agreement created successfully.', $PocomosRecruit);
    }

    /**
     * API for update of Recruiting Agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'recruiting_agreement_id' => 'required|integer|min:1',
             'name' => 'required',
            'description' => 'required',
            'agreement_body' => 'required',
            'initials' =>  'required|boolean',
            'default_agreement' => 'boolean',
            'active' => 'required|boolean',
            'email_pdf' =>'required|boolean',
            'address_for_pdf' =>'nullable|email',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruit = PocomosRecruit::find($request->recruiting_agreement_id);

        if (!$PocomosRecruit) {
            return $this->sendResponse(false, 'Recruiting Agreement not found.');
        }

        $PocomosRecruit->update(
            $request->only('recruiting_office_configuration_id ', 'description', 'name', 'agreement_body', 'initials', 'default_agreement', 'active', 'email_pdf', 'address_for_pdf')
        );

        return $this->sendResponse(true, 'Recruiting Agreement updated successfully.', $PocomosRecruit);
    }
}
