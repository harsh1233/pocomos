<?php

namespace App\Http\Controllers\API\Pocomos\Recruitement;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\Recruitement\OfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosRecruitAgreement;
use App\Models\Pocomos\PocomosRecruitingOfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosCustomFieldConfiguration;
use App\Models\Pocomos\PocomosRecruitAgreementTerm;

class RecruitAgreementController extends Controller
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
        $v = validator($request->all(), [
            'status' => 'required|in:Active,Inactive,All', //status
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruitStatus = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)->first();

        if ($PocomosRecruitStatus) {
            $PocomosRecruitAgreement = PocomosRecruitAgreement::where('recruiting_office_configuration_id', $PocomosRecruitStatus->id)->orderBy('id', 'desc');
        } else {
            $PocomosRecruitAgreement = PocomosRecruitAgreement::orderBy('id', 'desc');
        }

        if ($request->status == 'Active') {
            $PocomosRecruitAgreement = $PocomosRecruitAgreement->whereActive(true);
        } elseif ($request->status == 'Inactive') {
            $PocomosRecruitAgreement = $PocomosRecruitAgreement->whereActive(false);
        }

        if ($request->search) {
            $search = $request->search;
            $PocomosRecruitAgreement->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $PocomosRecruitAgreement->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosRecruitAgreement->skip($perPage * ($page - 1))->take($perPage);
        }

        $PocomosRecruitAgreement = $PocomosRecruitAgreement->get();

        if (!$PocomosRecruitAgreement) {
            return $this->sendResponse(false, 'RecruitAgreement Not Found');
        }

        $PocomosRecruitAgreement->map(function ($status) {
            $status1 = PocomosRecruitAgreementTerm::where('agreement_id', $status->id)->pluck('term_id')->toArray();

            $status->terms  = PocomosCustomFieldConfiguration::whereIn('id', $status1)->select('id', 'label', 'name', 'description', 'required')->get();
        });

        return $this->sendResponse(true, 'List', [
            'RecruitAgreement' => $PocomosRecruitAgreement,
            'count' => $count,
        ]);
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
        $PocomosRecruitAgreement = PocomosRecruitAgreement::find($id);
        if (!$PocomosRecruitAgreement) {
            return $this->sendResponse(false, 'Recruiting Agreement Not Found');
        }
        $termsIds = PocomosRecruitAgreementTerm::where('agreement_id', $PocomosRecruitAgreement->id)->pluck('term_id')->toArray();
        $PocomosRecruitAgreement->terms  = PocomosCustomFieldConfiguration::whereIn('id', $termsIds)->select('id', 'label', 'name', 'description', 'required')->get();
        return $this->sendResponse(true, 'Recruiting Agreement details.', $PocomosRecruitAgreement);
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
            'name' => 'required',
            'active' => 'required|boolean',
            'default_agreement' => 'required|boolean',
            'initials' =>  'required|boolean',
            'email_pdf' => 'required|boolean',
            'address_for_pdf' => 'nullable|email',
            'description' => 'required',
            'terms' => 'array',
            'terms.*.required' => 'required|boolean',
            'agreement_body' => 'nullable',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if ($request->office_id) {
            $number_data = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)->select('id')->first();
            $input_details['recruiting_office_configuration_id'] =  $number_data->id;
        }

        $input_details['description'] = $request->description ?? '';
        $input_details['name'] = $request->name ?? '';
        $input_details['agreement_body'] = $request->agreement_body ?? '';
        $input_details['initials'] = $request->initials ?? false;
        $input_details['default_agreement'] = $request->default_agreement ?? false;
        $input_details['active'] = $request->active ?? false;
        $input_details['email_pdf'] = $request->email_pdf ?? false;
        $input_details['address_for_pdf'] = $request->address_for_pdf ?? '';

        $PocomosRecruitAgreement =  PocomosRecruitAgreement::create($input_details);

        if ($request->terms) {
            foreach ($request->terms as $values) {
                $input['label'] = $values['label'];
                $input['required'] = $values['required'];
                $input['name'] = 'Required Term';
                $input['description'] = 'Required Term';
                $input['type'] = 'Term';
                $input['options'] = '';
                $input['active'] = 1;
                $alert = PocomosCustomFieldConfiguration::create($input);

                if (isset($PocomosRecruitAgreement) && isset($alert)) {
                    $input['term_id'] = $alert->id;
                    $input['agreement_id'] = $PocomosRecruitAgreement->id;
                    $term_id = PocomosRecruitAgreementTerm::create($input);
                }
            }
        }

        return $this->sendResponse(true, 'Recruiting Agreement created successfully.', $PocomosRecruitAgreement);
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
            'recruiting_agreement_id' => 'required|exists:pocomos_recruit_agreements,id',
            'name' => 'required',
            'active' => 'required|boolean',
            'default_agreement' => 'required|boolean',
            'initials' =>  'required|boolean',
            'email_pdf' => 'required|boolean',
            'address_for_pdf' => 'nullable|email',
            'description' => 'required',
            'terms' => 'array',
            'terms.*.required' => 'required|boolean',
            'agreement_body' => 'nullable',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruitAgreement = PocomosRecruitAgreement::find($request->recruiting_agreement_id);

        if (!$PocomosRecruitAgreement) {
            return $this->sendResponse(false, 'Recruiting Agreement not found.');
        }

        $update_details['description'] = $request->description ?? '';
        $update_details['name'] = $request->name ?? '';
        $update_details['agreement_body'] = $request->agreement_body ?? '';
        $update_details['initials'] = $request->initials ?? false;
        $update_details['default_agreement'] = $request->default_agreement ?? false;
        $update_details['active'] = $request->active ?? false;
        $update_details['email_pdf'] = $request->email_pdf ?? false;
        $update_details['address_for_pdf'] = $request->address_for_pdf ?? '';
        $PocomosRecruitAgreement->update($update_details);

        if ($request->terms) {
            $status1 = PocomosRecruitAgreementTerm::where('agreement_id', $request->recruiting_agreement_id)->delete();

            foreach ($request->terms as $values) {
                $input['label'] = $values['label'];
                $input['required'] = $values['required'];
                $input['name'] = 'Required Term';
                $input['description'] = 'Required Term';
                $input['type'] = 'Term';
                $input['options'] = '';
                $input['active'] = 1;
                $alert = PocomosCustomFieldConfiguration::create($input);

                if (isset($alert)) {
                    $input['term_id'] = $alert->id;
                    $input['agreement_id'] = $request->recruiting_agreement_id;
                    $term_id = PocomosRecruitAgreementTerm::create($input);
                }
            }
        }

        return $this->sendResponse(true, 'Recruiting Agreement updated successfully.', $PocomosRecruitAgreement);
    }
}
