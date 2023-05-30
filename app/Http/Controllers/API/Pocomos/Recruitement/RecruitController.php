<?php

namespace App\Http\Controllers\API\Pocomos\Recruitement;

use PDF;
use Mail;
use Illuminate\Http\Request;
use App\Http\Requests\Recruitment;
use App\Mail\RecruitmentAgreement;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosNote;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Orkestra\OrkestraUser;
use Illuminate\Support\Facades\Crypt;
use App\Jobs\RecruitAgreementGenerate;
use App\Models\Orkestra\OrkestraGroup;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosRecruits;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\Pocomos\PocomosRecruiter;
use App\Mail\RemoteCompletionRecruitment;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Orkestra\OrkestraUserGroup;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosRecruitNote;
use App\Models\Pocomos\PocomosRecruitsFile;
use App\Models\Orkestra\OrkestraCountryRegion;
use App\Models\Pocomos\PocomosRecruitContract;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosSalespersonProfile;
use App\Models\Pocomos\PocomosTechnicianLicenses;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosRecruitCustomFields;
use App\Models\Pocomos\PocomosRecruitAgreementTerm;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Pocomos\Recruitement\PocomosRecruitOffice;
use App\Models\Pocomos\Recruitement\PocomosRecruitStatus;
use App\Models\Pocomos\PocomosRecruitingOfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosRecruitAgreement;
use App\Models\Pocomos\PocomosRecruitCustomFieldConfiguration;
use Excel;
use App\Exports\ExportRecruits;

class RecruitController extends Controller
{
    use Functions;

    /**
     * API for create of Recruitement
     .
     *
     * @param  \Illuminate\Http\Recruitment  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Recruitment $request)
    {
        DB::beginTransaction();
        $res = array();

        try {
            $basic_information = ($request->basic_information ?? array());
            $general_information = ($request->full_information ? $request->full_information['general_information'] ?? array() : array());
            $login_information = ($request->full_information ? $request->full_information['login_information'] ?? array() : array());
            $contact_information = ($request->full_information ? $request->full_information['contact_information'] ?? array() : array());
            $current_address = ($request->full_information ? $request->full_information['current_address'] ?? array() : array());
            $primary_same_as_current = ($request->full_information ? $request->full_information['primary_same_as_current'] ?? null : null);
            $primary_address = ($request->full_information ? $request->full_information['primary_address'] ?? array() : array());
            $initial = ($request->full_information ? $request->full_information['initial'] ?? null : null);
            $additional_information = ($request->full_information ? $request->full_information['additional_information'] ?? array() : array());

            $sales_contract = ($request->finalize_agreement ? $request->finalize_agreement['sales_contract'] ?? null : null);
            $acceptance_of_contract = ($request->finalize_agreement ? $request->finalize_agreement['acceptance_of_contract'] ?? array() : array());
            $contract_signature = ($request->finalize_agreement ? $request->finalize_agreement['contract_signature'] ?? null : null);

            if ($basic_information) {
                $input_details['first_name'] = $basic_information['first_name'] ?? null;
                $input_details['last_name'] = $basic_information['last_name'] ?? null;
                $input_details['date_of_birth'] = $general_information['dob'] ?? null;
                $input_details['email'] = $basic_information['email'] ?? null;
                $input_details['active'] = true;
                $input_details['desired_username'] = '';
                $input_details['desired_password'] = '';
                if (isset($login_information['desired_username']) && isset($login_information['desired_password'])) {
                    $input_details['desired_username'] = $login_information['desired_username'] ?? '';
                    $input_details['desired_password'] = Hash::make($login_information['desired_password']);
                }
                $input_details['legal_first_name'] = $general_information['legal_first_name'] ?? null;
                $input_details['legal_last_name'] = $general_information['legal_last_name'] ?? null;
                $input_details['legal_name'] = $input_details['legal_first_name'] ?? '' . ' ' . $input_details['legal_last_name'] ?? '';
                $input_details['recruiting_office_id'] = $basic_information['recruiting_office_id'] ?? null;
                $input_details['recruiting_region_id'] = $basic_information['recruiting_region_id'] ?? null;
                $input_details['recruiter_id'] = $basic_information['recruiter_id'] ?? null;

                $officeId = $basic_information['office_id'] ?? null;
                $configurationId = PocomosRecruitingOfficeConfiguration::where('office_id', $officeId)->first()->id ?? null;
                $officeDefaultStatus = PocomosRecruitStatus::where('recruiting_office_configuration_id', $configurationId)->where('default_status', true)->where('active', true)->first()->id ?? null;

                $input_details['recruit_status_id'] = $officeDefaultStatus;
            }

            if ($contact_information) {
                if (isset($contact_information['phone'])) {
                    $phone_number['alias'] = 'Primary';
                    $phone_number['number'] = $contact_information['phone'];
                    $phone_number['type'] = 'Home';
                    $phone_number['active'] = true;
                    $phone = PocomosPhoneNumber::create($phone_number);
                }

                if (isset($contact_information['alt_phone'])) {
                    $phone_number['alias'] = 'Alternate';
                    $phone_number['number'] = $contact_information['alt_phone'];
                    $phone_number['type'] = 'Home';
                    $phone_number['active'] = true;
                    $alt_phone = PocomosPhoneNumber::create($phone_number);
                }
            }

            if ($current_address) {
                $current_ad_details['region_id'] = $current_address['state'] ?? null;
                $current_ad_details['street'] = $current_address['street'] ?? '';
                $current_ad_details['suite'] = $current_address['suite'] ?? '';
                $current_ad_details['city'] = $current_address['city'] ?? '';
                $current_ad_details['postal_code'] = $current_address['postal'] ?? '';
                $current_ad_details['validated'] = 2;
                $current_ad_details['valid'] = 1;
                $current_ad_details['phone_id'] = $phone->id ?? null;
                $current_ad_details['alt_phone_id'] = $alt_phone->id ?? null;
                $current_ad_details['active'] = true;
                $cu_address = PocomosAddress::create($current_ad_details);
            }

            if (!$primary_same_as_current && $primary_same_as_current != null && $primary_address) {
                $primary_ad_details['region_id'] = $primary_address['state'] ?? null;
                $primary_ad_details['street'] = $primary_address['street'] ?? '';
                $primary_ad_details['suite'] = $primary_address['suite'] ?? '';
                $primary_ad_details['city'] = $primary_address['city'] ?? '';
                $primary_ad_details['postal_code'] = $primary_address['postal'] ?? '';
                $primary_ad_details['validated'] = 2;
                $primary_ad_details['valid'] = 1;
                $primary_ad_details['phone_id'] = $phone->id ?? null;
                $primary_ad_details['alt_phone_id'] = $alt_phone->id ?? null;
                $primary_ad_details['active'] = true;
                $pr_address = PocomosAddress::create($primary_ad_details);
            } elseif ($current_address) {
                $primary_ad_details['region_id'] = $current_address['state'] ?? null;
                $primary_ad_details['street'] = $current_address['street'] ?? '';
                $primary_ad_details['suite'] = $current_address['suite'] ?? '';
                $primary_ad_details['city'] = $current_address['city'] ?? '';
                $primary_ad_details['postal_code'] = $current_address['postal'] ?? '';
                $primary_ad_details['validated'] = 2;
                $primary_ad_details['valid'] = 1;
                $primary_ad_details['phone_id'] = $phone->id ?? null;
                $primary_ad_details['alt_phone_id'] = $alt_phone->id ?? null;
                $primary_ad_details['active'] = true;
                $pr_address = PocomosAddress::create($primary_ad_details);
            }

            $input_details['current_address_id'] = $cu_address->id ?? null;
            $input_details['primary_address_id'] = $pr_address->id ?? null;

            if ($login_information && $general_information) {
                $salt = '10';
                $orkestra_user['username'] = '';
                if (isset($login_information['desired_username'])) {
                    $orkestra_user['username'] = $login_information['desired_username'] ?? null;
                }
                if (isset($login_information['desired_password'])) {
                    $orkestra_user['salt'] = md5($salt . $login_information['desired_password']);
                    $orkestra_user['password'] = Hash::make($login_information['desired_password']);
                } else {
                    $orkestra_user['salt'] = '';
                    $orkestra_user['password'] = '';
                }
                $orkestra_user['first_name'] = $general_information['first_name'] ?? null;
                $orkestra_user['last_name'] = $general_information['last_name'] ?? null;
                $orkestra_user['email'] = $contact_information['email'] ?? null;
                $orkestra_user['expired'] = false;
                $orkestra_user['locked'] = false;
                $orkestra_user['active'] = true;

                $or_user = OrkestraUser::create($orkestra_user);

                $input_details['user_id'] = $or_user->id ?? null;
                $input_details['remote_user_id'] = $or_user->id ?? null;

                $profile = PocomosCompanyOfficeUserProfile::create([
                    'user_id' => $or_user->id, 'active' => true
                ]);

                $office_user_dettails['office_id'] = $officeId;
                $office_user_dettails['user_id'] = $or_user->id ?? null;
                $office_user_dettails['active'] = true;
                $office_user_dettails['profile_id'] = $profile->id;
                $office_user_dettails['deleted'] = false;
                $office_user = PocomosCompanyOfficeUser::create($office_user_dettails);
            }

            $recruiter_sign = $request['basic_information']['recruiter_sign'] ?? null;
            if ($recruiter_sign) {
                //store file into document folder
                $recruiter_detail['path'] = $recruiter_sign->store('public/files');

                $recruiter_detail['user_id'] = $or_user->id ?? null;
                //store your file into database
                $recruiter_detail['filename'] = $recruiter_sign->getClientOriginalName();
                $recruiter_detail['mime_type'] = $recruiter_sign->getMimeType();
                $recruiter_detail['file_size'] = $recruiter_sign->getSize();
                $recruiter_detail['active'] = 1;
                $recruiter_detail['md5_hash'] =  md5_file($recruiter_sign->getRealPath());
                $recruiter_sign =  OrkestraFile::create($recruiter_detail);
            }

            $initial = $request['full_information']['initial'] ?? null;
            if ($initial) {
                //store file into document folder
                $initial_detail['path'] = $initial->store('public/files');

                $initial_detail['user_id'] = $or_user->id ?? null;
                //store your file into database
                $initial_detail['filename'] = $initial->getClientOriginalName();
                $initial_detail['mime_type'] = $initial->getMimeType();
                $initial_detail['file_size'] = $initial->getSize();
                $initial_detail['active'] = 1;
                $initial_detail['md5_hash'] =  md5_file($initial->getRealPath());
                $initial =  OrkestraFile::create($initial_detail);
            }

            $contract_signature = $request['finalize_agreement']['contract_signature'] ?? null;
            if ($contract_signature) {
                //store file into document folder
                $contract_signature_details['path'] = $contract_signature->store('public/files');

                $contract_signature_details['user_id'] = $or_user->id ?? null;
                //store your file into database
                $contract_signature_details['filename'] = $contract_signature->getClientOriginalName();
                $contract_signature_details['mime_type'] = $contract_signature->getMimeType();
                $contract_signature_details['file_size'] = $contract_signature->getSize();
                $contract_signature_details['active'] = 1;
                $contract_signature_details['md5_hash'] =  md5_file($contract_signature->getRealPath());
                $contract_signature =  OrkestraFile::create($contract_signature_details);
            }

            $recruit_contract['agreement_id'] = $basic_information['recruit_agreement_id'] ?? null;
            $recruit_contract['signature_id'] = $contract_signature->id ?? null;
            $recruit_contract['initials_id'] = $initial->id ?? null;
            $recruit_contract['recruiter_signature_id'] = $recruiter_sign->id ?? null;
            $recruit_contract['pay_level'] = $basic_information['contract_value'] ?? null;
            $recruit_contract['addendum'] = $basic_information['agreement_addendum'] ?? null;
            $recruit_contract['date_start'] = $basic_information['date_start'] ?? null;
            $recruit_contract['date_end'] = $basic_information['date_end'] ?? null;

            if ($contract_signature && $initial && $recruiter_sign) {
                $contract_status = 'Signed';
            } else {
                $contract_status = 'Unsigned';
            }

            $recruit_contract['status'] = $contract_status;
            $recruit_contract['active'] = true;

            $recruit_contract = PocomosRecruitContract::create($recruit_contract);

            foreach ($additional_information as $value) {
                $custome_input['custom_field_configuration_id'] = $value['custom_field_configuration_id'] ?? null;
                $custome_input['recruit_contract_id'] = $recruit_contract->id ?? null;
                $custome_input['value'] = $value['value'] ?? null;
                $custome_input['active'] = true;

                PocomosRecruitCustomFields::create($custome_input);
            }

            $input_details['linked'] = false;
            $input_details['recruit_contract_id'] = $recruit_contract->id ?? null;

            if (isset($acceptance_of_contract['accept_terms'])) {
                foreach ($acceptance_of_contract['accept_terms'] as $value) {
                    $isExist = PocomosRecruitAgreementTerm::whereAgreementId($basic_information['recruit_agreement_id'])->whereTermId($value)->first();
                    if (!$isExist) {
                        PocomosRecruitAgreementTerm::create(['agreement_id' => $basic_information['recruit_agreement_id'] ?? null, 'term_id' => $value]);
                    }
                }
            }

            $recruit = PocomosRecruits::create($input_details);

            if (isset($basic_information['note'])) {
                $note_detail['user_id'] = $or_user->id ?? null;
                $note_detail['summary'] = $basic_information['note'];
                $note_detail['interaction_type'] = 'Other';
                $note_detail['active'] = true;
                $note_detail['body'] = '';
                $note = PocomosNote::create($note_detail);

                PocomosRecruitNote::create(['recruit_id' => $recruit->id, 'note_id' => $note->id]);
            }

            DB::commit();
            $status = true;
            $message = __('strings.create', ['name' => 'New Recruit']);
            $res['recruit_id'] = $recruit->id;
        } catch (\Exception $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }
        return $this->sendResponse($status, $message, $res);
    }

    /**
     * API for list of Recruitement
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
            'converted_to_employee' => 'nullable|in:both,not_converted,converted',
            'recruit_status' => 'nullable|array',
            'contract_start' => 'nullable',
            'office_id' => 'nullable|exists:pocomos_company_offices,id',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruits = PocomosRecruits::with(
            'status_detail',
            'contract_detail.agreement',
            'contract_detail.custome_fields',
            'office_detail',
            'current_address.primaryPhone',
            'primary_address.primaryPhone',
            'current_address.altPhone',
            'primary_address.altPhone',
            'profile_details',
            'current_address.region',
            'attachment_details.attachment',
            'note_details.note',
            'recruiter_detail.user.user_details',
            'contract_detail.signature',
            'contract_detail.recruiter_signature',
            'contract_detail.recruit_initial',
            'user_detail'
        )
            ->where('active', true);

        if ($request->office_id) {
            $office_ids = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)->pluck('id')->toArray();
            $config_ids = PocomosRecruitOffice::whereIn('office_configuration_id', $office_ids)->pluck('id')->toArray();
            $PocomosRecruits = $PocomosRecruits->whereIn('recruiting_office_id', $config_ids);
        }

        if ($request->recruit_status) {
            $PocomosRecruits = $PocomosRecruits->whereIn('recruit_status_id', $request->recruit_status);
        }

        if ($request->contract_start) {
            $contract_start = $request->contract_start;
            $contract_ids = PocomosRecruitContract::whereYear('date_start', $contract_start)->pluck('id')->toArray();

            $PocomosRecruits = $PocomosRecruits->whereIn('recruit_contract_id', $contract_ids);
        }

        if (in_array($request->converted_to_employee, ['not_converted', 'converted'])) {
            if ($request->converted_to_employee == 'converted') {
                $PocomosRecruits = $PocomosRecruits->whereNotNull('user_id');
            } else {
                $PocomosRecruits = $PocomosRecruits->whereNull('user_id');
            }
            $PocomosRecruits = $PocomosRecruits->whereNull('remote_user_id')->where('linked', false);
        }

        if ($request->search) {
            $search = $request->search;

            $PocomosRecruits = $PocomosRecruits->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%');
                $q->orWhere('last_name', 'like', '%' . $search . '%');
            });

            $recOfficeIds = PocomosRecruitOffice::where('name', 'like', '%' . $search . '%')->pluck('id')->toArray();
            $PocomosRecruits = $PocomosRecruits->orWhereIn('recruiting_office_id', $recOfficeIds);

            $recStatusIds = PocomosRecruitStatus::where('name', 'like', '%' . $search . '%')->pluck('id')->toArray();
            $PocomosRecruits = $PocomosRecruits->orWhereIn('recruit_status_id', $recStatusIds);

            $contrTmpIds = DB::select(DB::raw("SELECT prc.id
            FROM pocomos_recruit_contracts AS prc
            JOIN pocomos_recruit_agreements AS pra ON prc.agreement_id = pra.id
            WHERE pra.name like '%$search%'"));

            $contrIds = array_map(function ($value) {
                return $value->id;
            }, $contrTmpIds);

            $PocomosRecruits = $PocomosRecruits->orWhereIn('recruit_contract_id', $contrIds);

            if (!$contrIds) {
                $recContrStatusIds = PocomosRecruitContract::where('status', 'like', '%' . $search . '%')->pluck('id')->toArray();
                $PocomosRecruits = $PocomosRecruits->orWhereIn('recruit_contract_id', $recContrStatusIds);
            }

            $adrTmpIds = DB::select(DB::raw("SELECT pa.id
            FROM pocomos_addresses AS pa
            JOIN orkestra_countries_regions AS ocr ON pa.region_id = ocr.id
            WHERE pa.city like '%$search%' OR ocr.name like '%$search%'"));

            $adrIds = array_map(function ($value) {
                return $value->id;
            }, $adrTmpIds);
            $PocomosRecruits = $PocomosRecruits->orWhereIn('current_address_id', $adrIds);
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosRecruits->count();
        $PocomosRecruits->skip($perPage * ($page - 1))->take($perPage);
        /**End */

        $PocomosRecruits = $PocomosRecruits->orderBy('id', 'desc')->get();

        // return $PocomosRecruits;

        if ($request->download) {
            return Excel::download(new ExportRecruits($PocomosRecruits), 'ExportRecruits.csv');
        }

        $data = [
            'recruitements' => $PocomosRecruits,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Recruitements']), $data);
    }

    /**
     * API for update of Recruitement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update_status(Request $request)
    {
        $v = validator($request->all(), [
            'recruit_status_id' => 'required|exists:pocomos_recruit_status,id',
            'recruit_ids' => 'required|array',
            'recruit_ids.*' => 'required|exists:pocomos_recruits,id'
        ], [
            'recruit_ids.*.required' => __('validation.required', ['attribute' => 'recruit_id']),
            'recruit_ids.*.exists' => __('validation.exists', ['attribute' => 'recruit_id'])
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $ids = $request->recruit_ids;

        PocomosRecruits::whereIn('id', $ids)->update(array('recruit_status_id' => $request->recruit_status_id));

        return $this->sendResponse(true, __('strings.update', ['name' => 'Recruitement Status']));
    }

    /**
     * API for delete of Recruitement
     .
     *
     * @param  integer $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosRecruit = PocomosRecruits::findOrFail($id);
        if (!$PocomosRecruit) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Recruitement']));
        }
        $PocomosRecruit->active = false;
        $PocomosRecruit->save();
        return $this->sendResponse(true, __('strings.delete', ['name' => 'Recruitement']));
    }

    /**
     * API for quick edit of Recruitement
     .
     *
     * @param  integer $id
     * @return \Illuminate\Http\Response
     */

    public function quick_edit(Request $request, $id)
    {
        $v = validator($request->all(), [
            'recruiting_office_id' => 'required|exists:pocomos_recruiting_offices,id',
            'additional_information' => 'required|array'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruit = PocomosRecruits::findOrFail($id);

        if (!$PocomosRecruit) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Recruitement']));
        }

        if ($request->additional_information) {
            foreach ($request->additional_information as $value) {
                if ($value['custom_field_configuration_id']) {
                    $input_details['custom_field_configuration_id'] = $value['custom_field_configuration_id'] ?? null;
                    $input_details['value'] = $value['value'] ?? null;
                    $input_details['active'] = true;

                    PocomosRecruitCustomFields::updateOrCreate(
                        [
                            'recruit_contract_id' => $PocomosRecruit->recruit_contract_id,
                            'custom_field_configuration_id' => $value['custom_field_configuration_id'] ?? null
                        ],
                        $input_details
                    );
                }
            }
        }

        $PocomosRecruit->recruiting_office_id = $request->recruiting_office_id;
        $PocomosRecruit->save();

        return $this->sendResponse(true, __('strings.update', ['name' => 'Recruitement']));
    }

    /**
     * API for uplaod profile picture of Recruitement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function upload_profile(Request $request, $id)
    {
        $v = validator($request->all(), [
            'profile' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruit = PocomosRecruits::findOrFail($id);

        if (!$PocomosRecruit) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Recruitement']));
        }

        $profile_pic = $request['profile'];
        if ($profile_pic) {
            $profile_pic_id  = $this->uploadFile($profile_pic);

            $PocomosRecruit->profile_pic_id = $profile_pic_id ?? null;
            $PocomosRecruit->save();
        }

        return $this->sendResponse(true, __('strings.save', ['name' => 'Profile picture']));
    }

    /**
     * API for uplaod attachment of Recruitement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function upload_attachment(Request $request, $id)
    {
        $v = validator($request->all(), [
            'attachment' => 'required|mimes:pdf,doc,docx,xls,xlsx,jpeg,png,gif|max:20480'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosRecruit = PocomosRecruits::findOrFail($id);

        if (!$PocomosRecruit) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Recruitement']));
        }

        $attachment = $request['attachment'];
        if ($attachment) {
            $attachment_id  = $this->uploadFile($attachment);

            $input_details['recruit_id'] = $id;
            $input_details['file_id'] = $attachment_id ?? null;
            PocomosRecruitsFile::create($input_details);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'The file has been uploaded']));
    }

    /**
     * API for update of Recruitement
     .
     *
     * @param  \Illuminate\Http\Recruitment  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Recruitment $request, $id)
    {
        $recruit = PocomosRecruits::findOrFail($id);

        DB::beginTransaction();
        try {
            $basic_information = ($request->basic_information ?? array());
            $general_information = ($request->full_information ? $request->full_information['general_information'] ?? array() : array());
            $login_information = ($request->full_information ? $request->full_information['login_information'] ?? array() : array());
            $contact_information = ($request->full_information ? $request->full_information['contact_information'] ?? array() : array());
            $current_address = ($request->full_information ? $request->full_information['current_address'] ?? array() : array());
            $primary_same_as_current = ($request->full_information ? $request->full_information['primary_same_as_current'] ?? null : null);
            $primary_address = ($request->full_information ? $request->full_information['primary_address'] ?? array() : array());
            $initial = ($request->full_information ? $request->full_information['initial'] ?? null : null);
            $additional_information = ($request->full_information ? $request->full_information['additional_information'] ?? array() : array());

            $sales_contract = ($request->finalize_agreement ? $request->finalize_agreement['sales_contract'] ?? null : null);
            $acceptance_of_contract = ($request->finalize_agreement ? $request->finalize_agreement['acceptance_of_contract'] ?? array() : array());
            $contract_signature = ($request->finalize_agreement ? $request->finalize_agreement['contract_signature'] ?? null : null);
            $phone = $alt_phone = $cu_address = $pr_address = $or_user = $note = null;

            if ($basic_information) {
                $input_details['first_name'] = $basic_information['first_name'] ?? null;
                $input_details['last_name'] = $basic_information['last_name'] ?? null;
                $input_details['date_of_birth'] = $general_information['dob'] ?? null;
                $input_details['email'] = $basic_information['email'] ?? null;
                $input_details['active'] = true;
                $input_details['desired_username'] = $login_information['desired_username'] ?? '';
                $input_details['desired_password'] = isset($login_information['desired_password']) ? Hash::make($login_information['desired_password']) : '';
                $input_details['legal_first_name'] = $general_information['legal_first_name'] ?? null;
                $input_details['legal_last_name'] = $general_information['legal_last_name'] ?? null;
                $input_details['legal_name'] = $input_details['legal_first_name'] ?? '' . ' ' . $input_details['legal_last_name'] ?? '';
                $input_details['recruiting_office_id'] = $basic_information['recruiting_office_id'] ?? null;
                $input_details['recruiting_region_id'] = $basic_information['recruiting_region_id'] ?? null;
                $input_details['recruiter_id'] = $basic_information['recruiter_id'] ?? null;

                $officeId = $basic_information['office_id'] ?? null;
                $configurationId = PocomosRecruitingOfficeConfiguration::where('office_id', $officeId)->first()->id ?? null;
                $officeDefaultStatus = PocomosRecruitStatus::where('recruiting_office_configuration_id', $configurationId)->where('default_status', true)->where('active', true)->first()->id ?? null;

                $input_details['recruit_status_id'] = $officeDefaultStatus;
            }

            if ($contact_information) {
                if (isset($contact_information['phone'])) {
                    $phone_number['alias'] = 'Primary';
                    $phone_number['number'] = $contact_information['phone']['number'];
                    $phone_number['type'] = 'Home';
                    $phone_number['active'] = true;
                    if (isset($contact_information['phone']['id'])) {
                        $phone = PocomosPhoneNumber::findOrFail($contact_information['phone']['id']);
                        PocomosPhoneNumber::findOrFail($contact_information['phone']['id'])->update($phone_number);
                    } else {
                        $phone = PocomosPhoneNumber::create($phone_number);
                    }
                }

                if (isset($contact_information['alt_phone'])) {
                    $phone_number['alias'] = 'Alternate';
                    $phone_number['number'] = $contact_information['alt_phone']['number'];
                    $phone_number['type'] = 'Home';
                    $phone_number['active'] = true;
                    if (isset($contact_information['alt_phone']['id'])) {
                        $alt_phone = PocomosPhoneNumber::findOrFail($contact_information['alt_phone']['id']);
                        PocomosPhoneNumber::findOrFail($contact_information['alt_phone']['id'])->update($phone_number);
                    } else {
                        $alt_phone = PocomosPhoneNumber::create($phone_number);
                    }
                }
            }

            if ($current_address) {
                $current_ad_details['region_id'] = $current_address['state'] ?? null;
                $current_ad_details['street'] = $current_address['street'] ?? '';
                $current_ad_details['suite'] = $current_address['suite'] ?? '';
                $current_ad_details['city'] = $current_address['city'] ?? '';
                $current_ad_details['postal_code'] = $current_address['postal'] ?? '';
                $current_ad_details['validated'] = 2;
                $current_ad_details['valid'] = 1;
                $current_ad_details['phone_id'] = ($phone ? $phone->id ?? null : null);
                $current_ad_details['alt_phone_id'] = ($alt_phone ? $alt_phone->id ?? null : null);
                $current_ad_details['active'] = true;
                if (isset($current_address['id'])) {
                    $cu_address = PocomosAddress::findOrFail($current_address['id']);
                    PocomosAddress::findOrFail($current_address['id'])->update($current_ad_details);
                } else {
                    $cu_address = PocomosAddress::create($current_ad_details);
                }
            }

            if (!$primary_same_as_current && $primary_same_as_current != null && $primary_address) {
                $primary_ad_details['region_id'] = $primary_address['state'] ?? null;
                $primary_ad_details['street'] = $primary_address['street'] ?? '';
                $primary_ad_details['suite'] = $primary_address['suite'] ?? '';
                $primary_ad_details['city'] = $primary_address['city'] ?? '';
                $primary_ad_details['postal_code'] = $primary_address['postal'] ?? '';
                $primary_ad_details['validated'] = 2;
                $primary_ad_details['valid'] = 1;
                $primary_ad_details['phone_id'] = ($phone ? $phone->id ?? null : null);
                $primary_ad_details['alt_phone_id'] = ($alt_phone ? $alt_phone->id ?? null : null);
                $primary_ad_details['active'] = true;
                if (isset($primary_address['id'])) {
                    $pr_address = PocomosAddress::findOrFail($primary_address['id']);
                    PocomosAddress::findOrFail($primary_address['id'])->update($primary_ad_details);
                } else {
                    $pr_address = PocomosAddress::create($primary_ad_details);
                }
            } elseif ($current_address) {
                $primary_ad_details['region_id'] = $current_address['state'] ?? null;
                $primary_ad_details['street'] = $current_address['street'] ?? '';
                $primary_ad_details['suite'] = $current_address['suite'] ?? '';
                $primary_ad_details['city'] = $current_address['city'] ?? '';
                $primary_ad_details['postal_code'] = $current_address['postal'] ?? '';
                $primary_ad_details['validated'] = 2;
                $primary_ad_details['valid'] = 1;
                $primary_ad_details['phone_id'] = ($phone ? $phone->id ?? null : null);
                $primary_ad_details['alt_phone_id'] = ($alt_phone ? $alt_phone->id ?? null : null);
                $primary_ad_details['active'] = true;
                if (isset($primary_address['id'])) {
                    $pr_address = PocomosAddress::findOrFail($primary_address['id']);
                    PocomosAddress::findOrFail($primary_address['id'])->update($primary_ad_details);
                } else {
                    $pr_address = PocomosAddress::create($primary_ad_details);
                }
            }

            $input_details['current_address_id'] = $cu_address ? $cu_address->id ?? null : null;
            $input_details['primary_address_id'] = $pr_address ? $pr_address->id ?? null : null;

            if ($login_information && $general_information) {
                $salt = '10';
                $orkestra_user['username'] = $login_information['desired_username'] ?? null;
                $orkestra_user['salt'] = md5($salt . $login_information['desired_password']);
                $orkestra_user['password'] = $login_information['desired_password'] ? Hash::make($login_information['desired_password']) : '';
                $orkestra_user['first_name'] = $general_information['first_name'] ?? null;
                $orkestra_user['last_name'] = $general_information['last_name'] ?? null;
                $orkestra_user['email'] = $contact_information['email'] ?? null;
                $orkestra_user['expired'] = false;
                $orkestra_user['locked'] = false;
                $orkestra_user['active'] = true;

                if (isset($login_information['user_id'])) {
                    $or_user = OrkestraUser::findOrFail($login_information['user_id']);
                    OrkestraUser::where('id', $login_information['user_id'])->update($orkestra_user);
                } else {
                    $or_user = OrkestraUser::create($orkestra_user);
                }

                $input_details['user_id'] = $or_user->id ?? null;
                $input_details['remote_user_id'] = $or_user->id ?? null;
            }

            $recruiter_sign = $request['basic_information']['recruiter_sign'] ?? null;
            if ($recruiter_sign) {
                //store file into document folder
                $recruiter_detail['path'] = $recruiter_sign->store('public/files');

                $recruiter_detail['user_id'] = $or_user->id ?? null;
                //store your file into database
                $recruiter_detail['filename'] = $recruiter_sign->getClientOriginalName();
                $recruiter_detail['mime_type'] = $recruiter_sign->getMimeType();
                $recruiter_detail['file_size'] = $recruiter_sign->getSize();
                $recruiter_detail['active'] = 1;
                $recruiter_detail['md5_hash'] =  md5_file($recruiter_sign->getRealPath());
                if (isset($request['basic_information']['recruiter_sign_id'])) {
                    $recruiter_sign = OrkestraFile::findOrFail($request['basic_information']['recruiter_sign_id']);
                    OrkestraFile::findOrFail($request['basic_information']['recruiter_sign_id'])->update($recruiter_detail);
                } else {
                    $recruiter_sign = OrkestraFile::create($recruiter_detail);
                }
            }

            $initial = $request['full_information']['initial'] ?? null;
            if ($initial) {
                //store file into document folder
                $initial_detail['path'] = $initial->store('public/files');

                $initial_detail['user_id'] = $or_user->id ?? null;
                //store your file into database
                $initial_detail['filename'] = $initial->getClientOriginalName();
                $initial_detail['mime_type'] = $initial->getMimeType();
                $initial_detail['file_size'] = $initial->getSize();
                $initial_detail['active'] = 1;
                $initial_detail['md5_hash'] =  md5_file($initial->getRealPath());
                if (isset($request['full_information']['initial_id'])) {
                    $initial = OrkestraFile::findOrFail($request['full_information']['initial_id']);
                    OrkestraFile::findOrFail($request['full_information']['initial_id'])->update($initial_detail);
                } else {
                    $initial = OrkestraFile::create($initial_detail);
                }
            }

            $contract_signature = $request['finalize_agreement']['contract_signature'] ?? null;
            if ($contract_signature) {
                //store file into document folder
                $contract_signature_details['path'] = $contract_signature->store('public/files');

                $contract_signature_details['user_id'] = $or_user->id ?? null;
                //store your file into database
                $contract_signature_details['filename'] = $contract_signature->getClientOriginalName();
                $contract_signature_details['mime_type'] = $contract_signature->getMimeType();
                $contract_signature_details['file_size'] = $contract_signature->getSize();
                $contract_signature_details['active'] = 1;
                $contract_signature_details['md5_hash'] =  md5_file($contract_signature->getRealPath());

                if (isset($request['finalize_agreement']['contract_signature_id'])) {
                    $contract_signature = OrkestraFile::findOrFail($request['finalize_agreement']['contract_signature_id']);
                    OrkestraFile::findOrFail($request['finalize_agreement']['contract_signature_id'])->update($contract_signature_details);
                } else {
                    $contract_signature = OrkestraFile::create($contract_signature_details);
                }
            }

            $recruit_contract['agreement_id'] = $basic_information['recruit_agreement_id'] ?? null;
            $recruit_contract['signature_id'] = $contract_signature->id ?? null;
            $recruit_contract['initials_id'] = $initial->id ?? null;
            $recruit_contract['recruiter_signature_id'] = $recruiter_sign->id ?? null;
            $recruit_contract['pay_level'] = $basic_information['contract_value'] ?? null;
            $recruit_contract['addendum'] = $basic_information['agreement_addendum'] ?? null;
            $recruit_contract['date_start'] = $basic_information['date_start'] ?? null;
            $recruit_contract['date_end'] = $basic_information['date_end'] ?? null;

            if ($contract_signature && $initial && $recruiter_sign) {
                $contract_status = 'Signed';
            } else {
                $contract_status = 'Unsigned';
            }

            $recruit_contract['status'] = $contract_status;
            $recruit_contract['active'] = true;

            if (isset($basic_information['recruit_contract_id'])) {
                $recruit_contract_res = PocomosRecruitContract::findOrFail($basic_information['recruit_contract_id']);
                PocomosRecruitContract::findOrFail($basic_information['recruit_contract_id'])->update($recruit_contract);
            } else {
                $recruit_contract_res = PocomosRecruitContract::create($recruit_contract);
            }

            foreach ($additional_information as $value) {
                $custome_input['custom_field_configuration_id'] = $value['custom_field_configuration_id'] ?? null;
                $custome_input['recruit_contract_id'] = $recruit_contract_res->id ?? null;
                $custome_input['value'] = $value['value'] ?? null;
                $custome_input['active'] = true;

                if (isset($value['custom_field_id'])) {
                    PocomosRecruitCustomFields::findOrFail($value['custom_field_id']);
                    PocomosRecruitCustomFields::findOrFail($value['custom_field_id'])->update($custome_input);
                } else {
                    PocomosRecruitCustomFields::create($custome_input);
                }
            }

            $input_details['linked'] = false;
            $input_details['recruit_contract_id'] = $recruit_contract_res->id ?? null;

            if (isset($acceptance_of_contract['accept_terms'])) {
                foreach ($acceptance_of_contract['accept_terms'] as $value) {
                    PocomosRecruitAgreementTerm::updateOrCreate(['agreement_id' => $basic_information['recruit_agreement_id'], 'term_id' => $value], ['agreement_id' => $basic_information['recruit_agreement_id'] ?? null, 'term_id' => $value]);
                }
            }

            $recruit = $recruit->update($input_details);

            if (isset($basic_information['note'])) {
                $note_detail['user_id'] = $or_user->id ?? null;
                $note_detail['summary'] = $basic_information['note'];
                $note_detail['interaction_type'] = 'Other';
                $note_detail['active'] = true;
                $note_detail['body'] = '';
                if (isset($basic_information['note_id'])) {
                    $note = PocomosNote::findOrFail($basic_information['note_id']);
                    PocomosNote::findOrFail($basic_information['note_id'])->update($note_detail);
                } else {
                    $note = PocomosNote::create($note_detail);
                    PocomosRecruitNote::create(['recruit_id' => $recruit->id, 'note_id' => $note->id]);
                }
            }

            DB::commit();
            $status = true;
            $message = __('strings.update', ['name' => 'Recruit']);
        } catch (\Exception $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }
        return $this->sendResponse($status, $message);
    }

    /**
     * API for get details of Recruitement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosRecruits = PocomosRecruits::with('status_detail', 'contract_detail.agreement', 'contract_detail.custome_fields', 'office_detail', 'current_address.primaryPhone', 'primary_address.primaryPhone', 'current_address.altPhone', 'primary_address.altPhone', 'profile_details', 'current_address.region', 'primary_address.region', 'attachment_details.attachment', 'note_details.note', 'recruiter_detail.user.user_details', 'contract_detail.signature', 'contract_detail.recruiter_signature', 'contract_detail.recruit_initial', 'user_detail')
            ->findOrFail($id);

        return $this->sendResponse(true, __('strings.details', ['name' => 'Recruitment']), $PocomosRecruits);
    }

    /**
     * API for send Email Remote Completion
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function remote_completion_email(Request $request, $id)
    {
        $v = validator(
            $request->all(),
            [
                'emails' => 'required',
                'emails.*' => 'regex:/(.+)@(.+)\.(.+)/i',
                'office_id' => 'required|exists:pocomos_company_offices,id',
            ],
            [
                'emails.*.regex' => __('validation.not_regex', ['attribute' => 'email'])
            ]
        );

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $recruit = PocomosRecruits::findOrFail($id);
        $emails = $request->emails;
        $office_id = $request->office_id;
        $recruit_user = ($recruit->first_name ?? '') . ' ' . ($recruit->last_name ?? '');

        $data = DB::select(DB::raw("SELECT co.name, CONCAT(ud.first_name, ' ' , ud.last_name ) as 'recruiter_name'
        FROM pocomos_recruits AS pr
        JOIN pocomos_recruiting_offices AS ro ON pr.recruiting_office_id = ro.id
        JOIN pocomos_recruiting_office_configurations AS roc ON ro.office_configuration_id = roc.id
        JOIN pocomos_company_offices AS co ON roc.office_id = co.id
        JOIN pocomos_recruiters AS ru ON pr.recruiter_id = ru.id
        JOIN pocomos_company_office_users AS ou ON ru.user_id = ou.id
        JOIN orkestra_users AS ud ON ou.user_id = ud.id
        WHERE (roc.office_id = '$office_id' OR co.parent_id = '$office_id') AND pr.active = 1
        GROUP BY co.id
        ORDER BY co.id ASC"));

        if (!count($data)) {
            return $this->sendResponse(false, __('strings.something_went_wrong'));
        };

        $office = $data[0]->name ?? '';
        $recruiter_user = $data[0]->recruiter_name ?? '';

        $email = Mail::to($emails);
        $email->send(new RemoteCompletionRecruitment($office, $recruit_user, $recruiter_user, $recruit));

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Remote Completion Email Send']));
    }

    /**
     * API for send agreement attachment file on Email
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function send_agreement(Request $request, $id)
    {
        $v = validator(
            $request->all(),
            [
                'emails' => 'required',
                'emails.*' => 'regex:/(.+)@(.+)\.(.+)/i',
                'office_id' => 'required|exists:pocomos_company_offices,id',
            ],
            [
                'emails.*.regex' => __('validation.not_regex', ['attribute' => 'email'])
            ]
        );

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $recruit = PocomosRecruits::findOrFail($id);
        $emails = $request->emails;
        $office_id = $request->office_id;
        $recruit_user = ($recruit->first_name ?? '') . ' ' . ($recruit->last_name ?? '');

        // Job dispacth for sending agreement
        RecruitAgreementGenerate::dispatch($recruit, $emails, $office_id, $recruit_user, $id);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Recruitement Agreement Copy Send']));
    }

    /**
     * API for download agreement attachment file
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function download_agreement(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $recruit = PocomosRecruits::findOrFail($id);
        $office_id = $request->office_id;
        $recruit_user = ($recruit->first_name ?? '') . ' ' . ($recruit->last_name ?? '');
        $is_download = true;

        // Job dispacth for sending agreement
        // RecruitAgreementGenerate::dispatch($recruit, [], $office_id, $recruit_user, $id, $is_download);

        $data = DB::select(DB::raw("SELECT co.name, CONCAT(ud.first_name, ' ' , ud.last_name ) as 'recruiter_name', ra.agreement_body, ra.name as 'agreement_name'
        FROM pocomos_recruits AS pr
        JOIN pocomos_recruiting_offices AS ro ON pr.recruiting_office_id = ro.id
        JOIN pocomos_recruiting_office_configurations AS roc ON ro.office_configuration_id = roc.id
        JOIN pocomos_company_offices AS co ON roc.office_id = co.id
        JOIN pocomos_recruiters AS ru ON pr.recruiter_id = ru.id
        JOIN pocomos_company_office_users AS ou ON ru.user_id = ou.id
        JOIN orkestra_users AS ud ON ou.user_id = ud.id
        JOIN pocomos_recruit_contracts AS rc ON pr.recruit_contract_id = rc.id
        JOIN pocomos_recruit_agreements AS ra ON rc.agreement_id = ra.id
        WHERE (roc.office_id = '$office_id' OR co.parent_id = '$office_id') AND pr.active = 1
        GROUP BY co.id
        ORDER BY co.id ASC"));

        if (!count($data)) {
            return $this->sendResponse(false, __('strings.something_went_wrong'));
        }

        $agreement = $data[0]->agreement_name ?? '';
        $agreement_body = $data[0]->agreement_body ?? '';

        $agreement_body = $this->generateAgreement($agreement_body, $id, $office_id);

        $path = 'public/pdf/agreement_' . $id . '.pdf';

        $pdf = PDF::loadView('emails.dynamic_email_render', compact('agreement_body'));

        return $pdf->download('agreement_' . $id . '.pdf');
    }

    /**
     * API for regenerate agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function regenerate_agreement(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $recruitment = PocomosRecruits::findOrFail($id);

        $agreement = PocomosRecruitContract::findOrFail($recruitment->recruit_contract_id);
        $agreement->status = 'Unsigned';
        $agreement->save();

        $path = storage_path('app/public/pdf') . '/agreement_' . $id . '.pdf';
        if ($path && file_exists($path)) {
            unlink($path);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Agreement Regenerated']));
    }

    /**Preview recruit agreement */
    public function previewRecruitAgreement()
    {
        try {
            DB::beginTransaction();

            $office_id = $_GET['office_id'];
            $agreement_id = $_GET['agreement_id'];
            $office_user_id = Session::get(config('constants.ACTIVE_OFFICE_ID')) ?? null;

            if (!$office_user_id) {
                throw new \Exception(__('strings.something_went_wrong'));
            }

            $office = PocomosCompanyOffice::findOrFail($office_id);
            $recruiter = PocomosRecruiter::where('user_id', $office_user_id)->first();

            $recruitAgreement = PocomosRecruitAgreement::findOrFail($agreement_id);
            $officeConfig = PocomosRecruitingOfficeConfiguration::where('office_id', $office_id)->firstOrFail();
            $region = OrkestraCountryRegion::findOrFail(1791);

            $phone_number['alias'] = 'Primary';
            $phone_number['number'] = '(505)555-3485';
            $phone_number['type'] = 'Home';
            $phone_number['active'] = true;
            $phone = PocomosPhoneNumber::create($phone_number);

            $input['region_id'] = $region->id ?? null;
            $input['street'] = '490 E Main Street';
            $input['postal_code'] = '06360';
            $input['phone_id'] = $phone->id ?? null;
            $input['city'] = 'Norwich';
            $input['suite'] = '';
            $input['valid'] = true;
            $input['validated'] = true;
            $input['active'] = true;
            $address =  PocomosAddress::create($input);

            $recruitingOffice = PocomosRecruitOffice::where('office_configuration_id', $officeConfig->id)->firstOrFail();
            $recruitingOffice->name = 'Test Recruiting Office';
            $recruitingOffice->description = 'Test Recruiting Office';
            $recruitingOffice->active = true;
            $recruitingOffice->date_created = date('Y-m-d H:i:s');
            $recruitingOffice->save();

            $signatures = DB::select(DB::raw("SELECT f.*
            FROM orkestra_files AS f
            JOIN pocomos_contracts AS c ON f.id = c.signature_id
            LIMIT 3"));

            // $recruitSig = $signatures[0];
            // $recruitInitials = $signatures[1];
            // $recruiterSig = $signatures[2];
            $recruitSig = (object)[];
            $recruitInitials = (object)[];
            $recruiterSig = (object)[];

            $contract = new PocomosRecruitContract();
            $contract->date_end = date('Y-m-d H;i:s');
            $contract->date_start = date('Y-m-d H;i:s', strtotime('May 1'));
            $contract->recruiter_signature_id = $recruiterSig->id ?? null;
            $contract->signature_id = $recruitSig->id ?? null;
            $contract->initials_id = $recruitInitials->id ?? null;
            $contract->agreement_id = $recruitAgreement->id ?? null;
            $contract->pay_level = true;
            $contract->status = config('constants.UNSIGNED');
            $contract->active = true;
            $contract->addendum = '';
            $contract->save();

            foreach ($officeConfig->custom_fields_configuration as $customFieldConfig) {
                if (!$customFieldConfig->active) {
                    continue;
                }
                $customField = new PocomosRecruitCustomFields();
                $customField->custom_field_configuration_id = $customFieldConfig->id ?? null;
                $customField->recruit_contract_id = $contract->id ?? null;

                if (($options = unserialize($customFieldConfig->options)) && count($options) > 0) {
                    $customField->value = array_shift($options);
                } else {
                    $customField->value = 'Preview';
                }
                $customField->active = true;
                $customField->save();
            }

            $recruit = new PocomosRecruits();
            $recruit->current_address_id = $address->id ?? null;
            $recruit->primary_address_id = $address->id ?? null;
            $recruit->active = true;
            $recruit->date_of_birth = date('Y-m-d', strtotime('Jan 1 1979'));
            $recruit->email = 'recruit_email@email.com';
            $recruit->first_name = 'John';
            $recruit->last_name = 'Doe';
            $recruit->recruiting_office_id = $recruitingOffice->id ?? null;
            $recruit->legal_name = 'Johnathon Doe';
            $recruit->recruit_contract_id = $contract->id ?? null;
            $recruit->recruiter_id = $recruiter->id ?? null;
            $recruit->linked = false;
            $recruit->desired_username = '';
            $recruit->desired_password = '';
            $recruit->save();

            $params = array(
                'recruit' => $recruit,
                'recruiter' => $recruiter,
                'office' => $office,
                'recruitAgreement' => $recruitAgreement,
            );

            $template = $this->replaceDynamicVariables($recruitAgreement->agreement_body, $params);

            $filename = str_replace(' ', '_', $recruitAgreement->name);

            $pdf = PDF::loadView('pdf.dynamic_render', compact('template'));

            DB::commit();

            return $pdf->download($filename . '_preview.pdf');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendResponse(false, $e->getMessage());
        }
    }

    /**Get recruitment agreement body details */
    public function generateRecruitementSalesContract(Recruitment $request)
    {
        $data = array();
        try {
            $basic_information = $request->basic_information ?? array();
            $general_information = $request->full_information['general_information'] ?? array();
            $login_information = $request->full_information['login_information'] ?? array();
            $contact_information = $request->full_information['contact_information'] ?? array();
            $current_address = $request->full_information['current_address'] ?? array();
            $primary_address = $request->full_information['primary_address'] ?? array();
            $additional_information = $request->full_information['additional_information'] ?? array();
            $finalize_agreement = $request->finalize_agreement ?? array();
            $full_information = $request->full_information ?? array();

            $recruit_agreement_id = $basic_information['recruit_agreement_id'];

            $recruit_agreement = PocomosRecruitAgreement::findOrFail($recruit_agreement_id);
            $recruiter = PocomosRecruiter::with('user.user_details')->findOrFail($basic_information['recruiter_id']);
            $office = PocomosRecruitOffice::with('office_configuration.office_detail.logo')->findOrFail($basic_information['recruiting_office_id']);
            $office_logo = $office->office_configuration->office_detail->logo->full_path;
            $agreement_body = $recruit_agreement->agreement_body;
            $recruiter_sign = null;
            $recruit_signature = null;
            $initial_signature = null;
            $custome_fields = '';

            foreach ($additional_information as $custom_field) {
                $configuration = PocomosRecruitCustomFieldConfiguration::findOrFail($custom_field['custom_field_configuration_id']);
                $custome_fields .= $configuration->label . ' : ' . $custom_field['value'] . ', ';
            }

            if (isset($basic_information['recruiter_sign']) && $basic_information['recruiter_sign']) {
                $recruiter_sign = $basic_information['recruiter_sign'];
                $recruiter_sign_id = $this->uploadFileOnS3('Contract', $recruiter_sign);
                $file = OrkestraFile::findOrFail($recruiter_sign_id);
                $recruiter_sign = $file['full_path'];
            }

            if (isset($finalize_agreement['contract_signature']) && $finalize_agreement['contract_signature']) {
                $recruit_signature = $finalize_agreement['contract_signature'];
                $recruit_signature_id = $this->uploadFileOnS3('Contract', $recruit_signature);
                $file = OrkestraFile::findOrFail($recruit_signature_id);
                $recruit_signature = $file['full_path'];
            }

            if (isset($finalize_agreement['initial']) && $finalize_agreement['initial']) {
                $initial = $finalize_agreement['initial'];
                $initial_id = $this->uploadFileOnS3('Contract', $initial);
                $file = OrkestraFile::findOrFail($initial_id);
                $initial_signature = $file['full_path'];
            }

            $recruitment_details['company_logo'] = "<img src='$office_logo height='100px' width='200px'/>";
            $recruitment_details['recruit_beginning_date'] = $basic_information['date_start'] ? date('Y-m-d', strtotime($basic_information['date_start'])) : date('Y-m-d');
            if ($current_address) {
                $recruitment_details['recruit_current_address'] = $current_address['suite'] . ', ' . $current_address['street'] . ', ' . $current_address['city'] . ', ' . $current_address['state'] . ', ' . $current_address['postal'];
            }
            $recruitment_details['recruit_end_date'] = $basic_information['date_end'] ? date('Y-m-d', strtotime($basic_information['date_end'])) : date('Y-m-d');
            $recruitment_details['recruiter_name'] = $recruiter->user->user_details->first_name ?? '' . ' ' . $recruiter->user->user_details->last_name;
            $recruitment_details['recruiter_signature'] = '<img src="' . $recruiter_sign . '" height="100px" width="200px">';
            $recruitment_details['recruit_first_name'] = $basic_information['first_name'] ?? '';
            $recruitment_details['recruit_initials'] = '<img src="' . $initial_signature . '" height="100px" width="200px">';
            $recruitment_details['recruit_last_name'] = $basic_information['last_name'] ?? '';
            if ($primary_address) {
                $recruitment_details['recruit_permanent_address'] = $primary_address['suite'] . ', ' . $primary_address['street'] . ', ' . $primary_address['city'] . ', ' . $primary_address['state'] . ', ' . $primary_address['postal'];
            }
            $recruitment_details['recruit_phone'] = $contact_information['phone'] ?? '';
            $recruitment_details['recruit_signature'] = '<img src="' . $recruit_signature . '" height="100px" width="200px">';
            $recruitment_details['recruit_signature_date'] = date('Y-m-d');
            $recruitment_details['custom_fields'] = $custome_fields;
            $recruitment_details['recruiting_office'] = $office->office_configuration->office_detail->name ?? '';
            $recruitment_details['addendum'] = '';
            $recruitment_details['current_date'] = date('Y-m-d');

            $variables = ['{{company_logo}}', '{{recruit_beginning_date}}', '{{recruit_current_address}}', '{{recruit_end_date}}', '{{recruiter_name}}', '{{recruiter_signature}}', '{{recruit_first_name}}', '{{recruit_initials}}', '{{recruit_last_name}}', '{{recruit_permanent_address}}', '{{recruit_phone}}', '{{recruit_signature}}', '{{recruit_signature_date}}', '{{custom_fields}}', '{{recruiting_office}}', '{{addendum}}', '{{current_date}}'];
            $body = str_replace($variables, $recruitment_details, $agreement_body);

            $data = array(
                'agreement_body' => $body,
            );
        } catch (\Exception $e) {
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }
        return $this->sendResponse(true, __('strings.details', ['name' => 'Agreement']), $data);
    }

    /**
     * API delete recruit attachment
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteAttachment(Request $request)
    {
        $v = validator($request->all(), [
            'recruit_id' => 'required|exists:pocomos_recruits,id',
            'file_id' => 'required|exists:pocomos_recruits_files,file_id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $recruitAttachment = PocomosRecruitsFile::where('recruit_id', $request->recruit_id)->where('file_id', $request->file_id)->first();

        if (!$recruitAttachment) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        PocomosRecruitsFile::where('recruit_id', $request->recruit_id)->where('file_id', $request->file_id)->delete();

        return $this->sendResponse(true, __('strings.delete', ['name' => 'Recruit attachment']));
    }

    /**
     * Get remote completion details based on received email hash decode
     */
    public function getRemoteCompletionDetails(Request $request)
    {
        $v = validator($request->all(), [
            'hash' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $recruit_id = Crypt::decryptString($request->hash);

        if (!$recruit_id) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate the recruit.']));
        }

        $PocomosRecruits = PocomosRecruits::with('status_detail', 'contract_detail.agreement', 'contract_detail.custome_fields', 'office_detail', 'current_address.primaryPhone', 'primary_address.primaryPhone', 'current_address.altPhone', 'primary_address.altPhone', 'profile_details', 'current_address.region', 'primary_address.region', 'attachment_details.attachment', 'note_details.note', 'recruiter_detail.user.user_details', 'contract_detail.signature', 'contract_detail.recruiter_signature', 'contract_detail.recruit_initial', 'user_detail')
            ->findOrFail($recruit_id);

        return $this->sendResponse(true, __('strings.details', ['name' => 'Recruitment']), $PocomosRecruits);
    }

    /**Update recruite user details */
    public function updateUser(Request $request, $id)
    {
        $v = validator($request->all(), [
            'username' => 'nullable',
            'password' => 'nullable',
            'link_id' => 'nullable|exists:orkestra_users,id',
            'link_type' => 'nullable|in:link,new',
            'users' => 'nullable|exists:orkestra_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $recruit = PocomosRecruits::findOrFail($id);

        if ($request->link_type == 'new') {
            $user_details['first_name'] = $recruit->first_name;
            $user_details['last_name'] = $recruit->last_name;
            $user_details['email'] = $recruit->email;
            $user_details['locked'] = false;
            $user_details['active'] =  true;
            $user_details['expired'] = false;
            $password = $request->password;
            $user_details['password'] = bcrypt($password);
            $salt = '10';
            $user_details['salt'] = md5($salt . $password);
            $user_details['username'] = $request->username;
            $user = OrkestraUser::create($user_details);

            $profile = PocomosCompanyOfficeUserProfile::create([
                'user_id' => $user->id, 'active' => true
            ]);

            $office_user_dettails['office_id'] = $request->office_id;
            $office_user_dettails['user_id'] = $user->id ?? null;
            $office_user_dettails['active'] = true;
            $office_user_dettails['profile_id'] = $profile->id;
            $office_user_dettails['deleted'] = false;
            $office_user = PocomosCompanyOfficeUser::create($office_user_dettails);

            /**Default employee roles */
            $roles = [25, 26, 27, 28, 32];
            foreach ($roles as $val) {
                $roleDetails['user_id'] = $user->id;
                $roleDetails['group_id'] = $val;
                OrkestraUserGroup::create($roleDetails);
                $roleDetails = array();
            }
        } else {
            $user = OrkestraUser::findOrFail($request->link_id);

            $profile = PocomosCompanyOfficeUserProfile::updateOrCreate([
                'user_id' => $user->id, 'active' => true
            ], ['user_id' => $user->id]);

            $office_user_dettails['office_id'] = $request->office_id;
            $office_user_dettails['user_id'] = $user->id ?? null;
            $office_user_dettails['active'] = true;
            $office_user_dettails['profile_id'] = $profile->id;
            $office_user_dettails['deleted'] = false;
            $office_user = PocomosCompanyOfficeUser::updateOrCreate(['office_id' => $request->office_id, 'user_id' => $user->id], $office_user_dettails);

            /**Default employee roles */
            $roles = [25, 26, 27, 28, 32];
            foreach ($roles as $val) {
                $roleDetails['user_id'] = $user->id;
                $roleDetails['group_id'] = $val;
                OrkestraUserGroup::updateOrCreate($roleDetails, $roleDetails);
                $roleDetails = array();
            }
        }

        $update_detail['desired_username'] = $request->username ?? '';
        $update_detail['desired_password'] = $request->password ? Hash::make($request->password) : '';
        $update_detail['user_id'] = $user->id;
        $recruit->update($update_detail);

        $data['user_id'] = $user->id;
        return $this->sendResponse(true, __('strings.update', ['name' => 'Recruit']), $data);
    }

    /**Convert to employee update user details */
    public function convertToEmployeeUser(Request $request, $id)
    {
        $v = validator($request->all(), [
            'employee' => 'required|array',
            'employee.user' => 'required|array',
            'employee.user.preferences' => 'required|array',
            'salesperson' => 'required|array',
            'salesperson.user_profile_offices' => 'required|array',
            'employee.technician' => 'required|array',
            'employee.technician.licenses' => 'required|array',
            'employee.recruiter' => 'required|array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        try {
            DB::beginTransaction();

            $recruit = PocomosRecruits::findOrFail($id);
            $userDetails = $request->employee['user'] ?? array();
            $technician = $request->employee['technician'] ?? array();
            $user_profile_offices = $request->salesperson['user_profile_offices'] ?? array();

            $recruit->first_name = $userDetails['first_name'] ?? '';
            $recruit->last_name = $userDetails['last_name'] ?? '';
            $recruit->desired_username = $userDetails['user_name'] ?? '';
            $recruit->legal_name = $userDetails['first_name'] ?? '' . ' ' . $userDetails['last_name'] ?? '';
            $recruit->active = $userDetails['active'] ?? false;
            $recruit->save();

            $recruitContract = PocomosRecruitContract::findOrFail($recruit->recruit_contract_id);
            $recruitContract->status = 'Signed, Converted';
            $recruitContract->save();

            $officeUser = PocomosCompanyOfficeUser::where('user_id', $recruit->user_id)->first();
            if (!$officeUser) {
                throw new \Exception(__('strings.something_went_wrong'));
            }
            $technicianRes = PocomosTechnician::where('user_id', $officeUser->id)->first();

            if (!$technicianRes) {
                $technicianRes = PocomosTechnician::create([
                    'user_id' => $officeUser->id,
                    'active' => true,
                    'color' => $technician['color'],
                    'commission_type' => $technician['commission_type'],
                    'commission_value' => $technician['commission_value'],
                ]);
            }

            if ($technician['active']) {
                $group = OrkestraGroup::where('name', 'Technician')->first();
                if ($group && !OrkestraUserGroup::where('user_id', $recruit->user_id)->where('group_id', $group->id)->count()) {
                    OrkestraUserGroup::create(['user_id' => $recruit->user_id, 'group_id' => $group->id]);
                }

                $i = 0;
                foreach ($technician['licenses'] as $value) {
                    $techLicensesDetails['technician_id'] = $technicianRes->id ?? '';
                    $techLicensesDetails['service_type_id'] = $value['service_type_id'] ?? '';
                    $techLicensesDetails['license_number'] = $value['license_number'] ?? '';
                    $techLicensesDetails['active'] = $value['active'] ?? false;
                    PocomosTechnicianLicenses::updateOrCreate([
                        'technician_id' => $technicianRes->id,
                        'service_type_id' => $value['service_type_id']
                    ], $techLicensesDetails);
                    $techLicensesDetails = array();
                }
            }

            if (isset($userDetails['groups']) && !OrkestraUserGroup::where('user_id', $recruit->user_id)->where('group_id', $userDetails['groups'])->count()) {
                OrkestraUserGroup::create(['user_id' => $recruit->user_id, 'group_id' => $userDetails['groups']]);
            }
            $isRecruiter = ($request->employee['recruiter'] ? $request->employee['recruiter']['active'] : false);
            if ($isRecruiter) {
                $group = OrkestraGroup::where('name', 'Recruiter')->first();
                if ($group  && !OrkestraUserGroup::where('user_id', $recruit->user_id)->where('group_id', $group->id)->count()) {
                    OrkestraUserGroup::create(['user_id' => $recruit->user_id, 'group_id' => $group->id]);
                }
            }

            $isSalesperson = ($request->employee['salesperson'] ? $request->employee['salesperson']['active'] : false);
            if ($isSalesperson) {
                $group = OrkestraGroup::where('name', 'Salesperson')->first();
                if ($group && !OrkestraUserGroup::where('user_id', $recruit->user_id)->where('group_id', $group->id)->count()) {
                    OrkestraUserGroup::create(['user_id' => $recruit->user_id, 'group_id' => $group->id]);
                }

                $profile = PocomosCompanyOfficeUserProfile::where('user_id', $recruit->user_id)->first();
                $salesProfile = PocomosSalespersonProfile::where('office_user_profile_id', $profile->id)->first();

                if (!$salesProfile) {
                    $salesProfile = PocomosSalespersonProfile::create([
                        'office_user_profile_id' => $profile->id,
                        'experience' => $user_profile_offices['experience'],
                        'pay_level' => $user_profile_offices['pay_level'],
                        'tagline' => '',
                        'active' => true,
                    ]);
                } else {
                    $salesProfile->experience = $user_profile_offices['experience'];
                    $salesProfile->pay_level = $user_profile_offices['pay_level'];
                    $salesProfile->save();
                }
            }

            foreach ($user_profile_offices['offices'] as $value) {
                $userProfile = PocomosCompanyOfficeUser::where('office_id', $value['office_id'])->where('user_id', $recruit->user_id)->first();

                PocomosCompanyOfficeUser::updateOrCreate([
                    'office_id' => $value['office_id'],
                    'user_id' => $recruit->user_id
                ], [
                    'office_id' => $value['office_id'],
                    'user_id' => $recruit->user_id,
                    'active' => $value['active'],
                    'deleted' => false,
                ]);

                if ($user_profile_offices['default_office'] == $value['office_id']) {
                    if ($userProfile && $userProfile->profile_id) {
                        $defaultUser = PocomosCompanyOfficeUser::where('office_id', $user_profile_offices['default_office'])->where('user_id', $recruit->user_id)->first();
                        if ($defaultUser) {
                            PocomosCompanyOfficeUserProfile::findOrFail($defaultUser->profile_id)->update(['default_office_user_id' => $defaultUser->id]);
                        }
                    }
                }
            }

            DB::commit();
            $status = true;
            $message = __('strings.sucess', ['name' => 'Converted to Employee']);
        } catch (\RuntimeException $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }
        return $this->sendResponse($status, $message);
    }

    /**
     * gives a select2 box that allows user to search for username
     */
    public function linkSelectData(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office = PocomosCompanyOffice::findOrFail($request->office_id);
        $offices = $office->getChildWithParentOffices();

        $offices[] = $office;
        $offices = array_map(function ($office) {
            return $office['id'];
        }, $offices);

        $offices = array_unique($offices);
        $offices = $this->convertArrayInStrings($offices);

        $sql = 'SELECT DISTINCT u.id, CONCAT(u.first_name, \' \', u.last_name) as name, u.username
                FROM orkestra_users u
                JOIN pocomos_company_office_users ou ON ou.user_id = u.id
                JOIN orkestra_user_groups ug on u.id = ug.user_id
                LEFT JOIN orkestra_groups g on ug.group_id = g.id
                WHERE ou.deleted = false AND ou.office_id IN ( ' . $offices . ' ) AND (g.role <>  \'ROLE_CUSTOMER\' AND g.role <> \'ROLE_RECRUIT\')';

        $params = array();

        if ($request->search) {
            $search = $request->search;
            $sql .= ' AND (u.first_name LIKE "%' . $search . '%" OR u.last_name LIKE "%' . $search . '%" OR u.username LIKE "%' . $search . '%" OR CONCAT(u.first_name, \' \', u.last_name) LIKE "%' . $search . '%")';
        }

        $sql .= ' ORDER BY name';

        /**For pagination */
        $count = count(DB::select(DB::raw($sql)));
        /**If result data are from DB::row query then `true` else `false` normal laravel get listing */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";
        $data = DB::select(DB::raw("$sql"));
        /**End */

        $data = [
            'results' => $data,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Customers']), $data);
    }

    /**
     * Displays a modal send recruit agreements to the given emails
     * @Secure(roles="ROLE_RECRUIT_AGREEMENT_READ")
     */
    public function createW9(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'recruit_id' => 'required|exists:pocomos_recruits,id',
            'name' => 'nullable',
            'business' => 'nullable',
            'fedClassification' => 'nullable',
            'taxClassification' => 'nullable',
            'otherText' => 'nullable',
            'address' => 'array',
            'address.*.street' => 'nullable',
            'address.*.city' => 'nullable',
            'address.*.region' => 'nullable',
            'address.*.postalCode' => 'nullable',
            'tinType' => 'in:ssn,ein',
            'tin' => 'nullable',
            'signature' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $userId = auth()->user()->id;
        $recruitId = $request->recruit_id;
        $office = PocomosCompanyOffice::findOrFail($request->office_id);
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($office->id)->whereUserId($userId)->first();

        $recruit = $this->findOneByOfficeAndIdViewableByOfficeUser($office, $recruitId, $officeUser);

        if (!$recruit) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the requested Recruit']));
        }

        $w9Helper = $this->get('pocomos.recruiting.helper.w9_form_helper');
        try {
            $w9 = $w9Helper->generate(
                $form,
                array(
                    'recruit' => $recruit,
                    'signature' => $form->has('signature') && $form->get('signature')->getData(),
                )
            );
        } catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
            return new JsonErrorResponse('An error occurred while generating the W9 PDF');
        }

        if ($w9) {
            $em = $this->getEntityManager();
            $file = new File($w9, 'w9.pdf', 'application/pdf', filesize($w9));

            /** @var File $oldW9 */
            $oldW9 = $recruit->getW9();
            if ($oldW9 && $oldW9->getPath() != $w9) {
                $recruit->getFiles()->removeElement($recruit->getW9());
                $em->remove($recruit->getW9());
                $recruit->addFile($file);
                $em->persist($file);
            }

            if (!$oldW9) {
                $recruit->addFile($file);
                $em->persist($file);
            }

            $em->persist($recruit);
            $em->flush();

            $this->getSession()->getFlashBag()->add('success', 'W9 completed successfully');

            return $this->redirect($this->generateUrl('recruiting_recruits'));
        }

        return new JsonErrorResponse('There was an error generating the w9.');

        return new JsonErrorResponse($form);
    }
}
