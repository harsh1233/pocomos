<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use DB;
use Excel;
use App\Jobs\AutopayJob;
use App\Jobs\OfficeStateJob;
use Illuminate\Http\Request;
use App\Jobs\OfficeSnapshotJob;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosZipCode;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraCountry;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Orkestra\OrkestraCountryRegion;
use Illuminate\Support\Facades\DB as FacadesDB;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosReportsOfficeState;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosCustomAgreementTemplate;
use App\Models\Pocomos\PocomosCustomAgreementToOffice;
use App\Models\Pocomos\PocomosSalestrackerOfficeSetting;
use App\Models\Pocomos\PocomosRecruitingOfficeConfiguration;

class OfficeController extends Controller
{
    use Functions;

    /**
     * API for list of Company
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'status' => 'in:active,inactive', //import_type
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $search = $request->search;

        $companies = PocomosReportsOfficeState::select('*', 'pocomos_reports_office_states.*', 'pco.active')
            ->leftjoin('pocomos_company_offices as pco', 'pocomos_reports_office_states.office_id', 'pco.id')
            ->leftjoin('pocomos_addresses as pa', 'pco.contact_address_id', 'pa.id')
            ->leftjoin('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
            ->leftjoin('pocomos_office_settings as pos', 'pco.id', 'pos.office_id')
            ->whereNull('pco.parent_id')
            ->where('pocomos_reports_office_states.type', 'State');
        // ->get();

        // $companies = companies::with(['contact.primaryPhone'])
        //     ->whereHas('contact.primaryPhone' ,function($query)use($search) {
        //     if($search){
        //         // dd(11);
        //         $query->orWhere('number', 'like', '%'.$search.'%');
        //     }
        // })
        // ;

        if ($request->status == 'active') {
            $companies->where('pco.active', true);
        } elseif ($request->status == 'inactive') {
            $companies->where('pco.active', false);
        }

        if ($search) {
            $companies->where(function ($query) use ($search) {
                $query->where('pocomos_reports_office_states.office_id', 'like', '%' . $search . '%')
                    ->orWhere('pco.name', 'like', '%' . $search . '%')
                    ->orWhere('ppn.number', 'like', '%' . $search . '%')
                    ->orWhere('pocomos_reports_office_states.users', 'like', '%' . $search . '%')
                    ->orWhere('pocomos_reports_office_states.salespeople', 'like', '%' . $search . '%')
                    ->orWhere('pocomos_reports_office_states.customers', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $companies->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $companies->skip($perPage * ($page - 1))->take($perPage);
        }

        $companies = $companies->orderBy('pocomos_reports_office_states.id', 'desc')->get();

        foreach ($companies as $q) {
            // return $q;
            $userIds = PocomosCompanyOfficeUser::whereOfficeId($q->office_id)->pluck('user_id');
            $user = OrkestraUser::whereIn('id', $userIds)->orderByDesc('last_login')->first();
            if ($user) {
                $q->last_login = $user->last_login;
            }
        }

        // $companies->map(function ($status) {

        //     $status->email_data = [];
        //     if (unserialize($status->email)) {
        //         $status->email_data = unserialize($status->email);
        //     }

        //     $status->Office_Setting = PocomosOfficeSetting::where('office_id', $status->id)->with('timezone')->first();
        // });

        // $data = [
        //     'records' => $companies,
        // ];

        return $this->sendResponse(true, 'List of Companies', [
            'companies' => $companies,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Company
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosCompanyOffice = PocomosCompanyOffice::where('id', $id)->with('coontact_address', 'office_settings')->with('routing_address')->first();

        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Not Found');
        }

        // $PocomosCompanyOffice->map(function ($status) {
        //     $status->email_data = [];
        //     if (unserialize($status->email)) {
        //         $status->email_data = unserialize($status->email);
        //     }

        //     $status->Office_Setting = PocomosOfficeSetting::where('office_id', $status->id)->with('timezone')->first();
        // });


        $childOfficees = PocomosCompanyOffice::where('parent_id', $id)->where('active', 1)->with('coontact_address')->orderBy('id', 'desc')->select('id', 'contact_address_id', 'name', 'contact_name', 'list_name', 'parent_id')->get();

        $childOfficees->map(function ($status) {
            $status->child_office_data = PocomosReportsOfficeState::where('office_id', $status->id)->where('type', 'State')->first();

            $PocomosReportsOfficeState = PocomosReportsOfficeState::where('office_id', $status->id)->where('type', 'State')->pluck('customers')->first();

            $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $status->id)->pluck('price_per_customer')->first();

            $status->recurring = $PocomosReportsOfficeState * $PocomosOfficeSetting;
        });

        $data = [
            'parent_office' => $PocomosCompanyOffice,
            'child_offices' => $childOfficees
        ];

        return $this->sendResponse(true, 'Company details.', $data);
    }

    /**
     * API for create of Company
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|unique:pocomos_company_offices',
            'contact_name' => 'required',
            'list_name' => 'required',
            'average_contract_value' => 'required',
            'send_agreement_copy' => 'required|boolean',
            'deliverEmail' => 'required|boolean',
            'contact_address.phone' => 'required',
            'contact_address.street' => 'required',
            'contact_address.city' => 'required',
            'contact_address.postal_code' => 'required',
            'routing_address' => 'nullable|array',
            'emails' => 'nullable|array',
            'timeZone' => 'nullable|exists:pocomos_timezones,id',
            'contact_address.region_id' => 'nullable|exists:orkestra_countries_regions,id',
            'routing_address.region_id' => 'nullable|exists:orkestra_countries_regions,id',
            'parent_id' => 'nullable|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('name', 'contact_name', 'list_name', 'billed_separately', 'customer_portal_link', 'average_contract_value', 'parent_id');

        $input_details['email'] =   serialize($request->input('emails'));
        $input_details['url'] =   $request->url ?? '';
        $input_details['fax'] =   $request->fax ?? '';
        $input_details['active'] = $request->active ?? 1;
        $input_details['license_number'] =   $request->license_number ?? '';
        $input_details['enabled'] = $request->enabled ?? 1;

        $contact_address = ($request->contact_address ?? array());

        if (isset($contact_address['phone'])) {
            $inputphone = [];
            $inputphone['type'] = "Mobile";
            $inputphone['number'] = $contact_address['phone'];
            $inputphone['alias'] = "Primary";
            $inputphone['active'] = 1;
            $PocomosPhoneNumber =  PocomosPhoneNumber::create($inputphone);
        }

        $input['phone_id'] =  $PocomosPhoneNumber->id;


        if (isset($contact_address['alt_phone'])) {
            $inputphone = [];
            $inputphone['type'] = "Mobile";
            $inputphone['number'] = $contact_address['alt_phone'];
            $inputphone['alias'] = "Alternate";
            $inputphone['active'] = 1;
            $PocomosPhoneNumberalt =  PocomosPhoneNumber::create($inputphone);
            $input['alt_phone_id'] =  $PocomosPhoneNumberalt->id;
        }

        if (isset($contact_address['region_id'])) {
            $input['region_id'] = $contact_address['region_id'];
        }

        $input['suite'] =  $contact_address['suite'] ?? '';
        $input['street'] =  $contact_address['street'];
        $input['postal_code'] = $contact_address['postal_code'];
        $input['active'] = 1;
        $input['valid'] = 1;
        $input['city'] = $contact_address['city'];
        $input['validated'] = 1;

        $PocomosAddress =  PocomosAddress::create($input);

        if ($PocomosAddress) {
            $input_details['contact_address_id'] = $PocomosAddress->id;
        }

        $routing_address = ($request->routing_address ?? array());

        if (isset($routing_address['phone'])) {
            $inputphone = [];
            $inputphone['type'] = "Mobile";
            $inputphone['number'] = $routing_address['phone'];
            $inputphone['alias'] = "Primary";
            $inputphone['active'] = 1;
            $PocomosPhoneNumber =  PocomosPhoneNumber::create($inputphone);
            $input['phone_id'] =  $PocomosPhoneNumber->id;
        }

        if (isset($routing_address['alt_phone'])) {
            $inputphone = [];
            $inputphone['type'] = "Mobile";
            $inputphone['number'] = $routing_address['alt_phone'];
            $inputphone['alias'] = "Alternate";
            $inputphone['active'] = 1;
            $PocomosPhoneNumberalt =  PocomosPhoneNumber::create($inputphone);
            $input['alt_phone_id'] =  $PocomosPhoneNumberalt->id;
        }


        if (isset($routing_address['region_id'])) {
            $input['region_id'] = $routing_address['region_id'];
        }

        $input['street'] =  $routing_address['street'] ?? '';
        $input['suite'] =  $routing_address['suite'] ?? '';
        $input['postal_code'] = $routing_address['postal_code'] ?? '';
        $input['validated'] = 1;
        $input['city'] = $routing_address['city'] ?? '';
        $input['active'] = 1;
        $input['valid'] = 1;
        $PocomosAddress =  PocomosAddress::create($input);

        if ($PocomosAddress) {
            $input_details['routing_address_id'] = $PocomosAddress->id;
        }

        if ($request->file('logo')) {
            $signature = $request->file('logo');
            //store file into document folder
            $sign_detail['filename'] = $signature->getClientOriginalName();
            $sign_detail['mime_type'] = $signature->getMimeType();
            $sign_detail['file_size'] = $signature->getSize();
            $sign_detail['active'] = 1;
            $sign_detail['md5_hash'] =  md5_file($signature->getRealPath());

            $url = "Office" . "/" . $sign_detail['filename'];
            Storage::disk('s3')->put($url, file_get_contents($signature));
            $sign_detail['path'] = Storage::disk('s3')->url($url);

            $agreement_sign =  OrkestraFile::create($sign_detail);
            $input_details['logo_file_id'] = $agreement_sign->id;
        }

        $PocomosCompanyOffice =  PocomosCompanyOffice::create($input_details);
        OfficeSnapshotJob::dispatch([$PocomosCompanyOffice->id]);

        OfficeStateJob::dispatch([$PocomosCompanyOffice->id]);

        $inputphone = [];
        $inputphone['timezone_id'] = $request->timeZone;
        $inputphone['theme'] = 'default';
        $inputphone['enable_points'] = 1;
        $inputphone['active'] = 1;
        $inputphone['sales_tax'] = 0;
        $inputphone['tax_code_required'] = 0;
        $inputphone['send_agreement_copy'] =  $request->send_agreement_copy;
        $inputphone['deliver_email'] =  $request->deliverEmail;
        $inputphone['price_per_customer'] = 0;
        $inputphone['office_id'] = $PocomosCompanyOffice->id;
        $PocomosPhoneNumber =  PocomosOfficeSetting::create($inputphone);

        return $this->sendResponse(true, 'Company created successfully.', $PocomosCompanyOffice);
    }

    /**
     * API for update of Company
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'contact_name' => 'required',
            'list_name' => 'required',
            'average_contract_value' => 'required',
            'send_agreement_copy' => 'required|boolean',
            'deliverEmail' => 'required|boolean',
            'contact_address.phone' => 'required',
            'contact_address.street' => 'required',
            'contact_address.city' => 'required',
            'contact_address.postal_code' => 'required',
            'routing_address' => 'nullable|array',
            'emails' => 'nullable|array',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'timeZone' => 'nullable|exists:pocomos_timezones,id',
            'contact_address.region_id' => 'nullable|exists:orkestra_countries_regions,id',
            'routing_address.region_id' => 'nullable|exists:orkestra_countries_regions,id',
            'parent_id' => 'nullable|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);

        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company not found.');
        }

        $input_details = $request->only('name', 'contact_name', 'list_name', 'billed_separately', 'customer_portal_link', 'average_contract_value', 'parent_id');

        $input_details['email'] =   serialize($request->input('emails')) ?? $PocomosCompanyOffice->email;
        $input_details['url'] =   $request->url ?? $PocomosCompanyOffice->url;
        $input_details['fax'] =   $request->fax ?? $PocomosCompanyOffice->fax;
        $input_details['active'] = $request->active ?? $PocomosCompanyOffice->active;
        $input_details['license_number'] =   $request->license_number ?? $PocomosCompanyOffice->license_number;
        $input_details['enabled'] = $request->enabled ?? $PocomosCompanyOffice->enabled;

        // contact address
        $contact_address = ($request->contact_address ?? array());

        $PocomosAddress = PocomosAddress::findOrFail($PocomosCompanyOffice->contact_address_id);

        if (isset($contact_address['phone'])) {
            $inputphone = [];
            $inputphone['type'] = "Mobile";
            $inputphone['number'] = $contact_address['phone'];
            $inputphone['alias'] = "Primary";
            $inputphone['active'] = 1;

            $PocomosPhoneNumber = PocomosPhoneNumber::findOrFail($PocomosAddress->phone_id);

            if ($PocomosPhoneNumber) {
                $PocomosPhoneNumber->update($inputphone);
            } else {
                $Configuration =  PocomosPhoneNumber::create($inputphone);
                $address['phone_id'] =  $Configuration->id;
            }
        }

        if (isset($contact_address['alt_phone'])) {
            $inputphone = [];
            $inputphone['type'] = "Mobile";
            $inputphone['number'] = $contact_address['alt_phone'];
            $inputphone['alias'] = "Alternate";
            $inputphone['active'] = 1;

            $PocomosPhoneNumber = PocomosPhoneNumber::findOrFail($PocomosAddress->alt_phone_id);

            if ($PocomosPhoneNumber) {
                $PocomosPhoneNumber->update($inputphone);
            } else {
                $Configuration =  PocomosPhoneNumber::create($inputphone);
                $address['alt_phone_id'] =  $Configuration->id;
            }
        }

        if (isset($contact_address['region_id'])) {
            $address['region_id'] = $contact_address['region_id'];
        }

        $address['suite'] =  $contact_address['suite'] ?? $PocomosAddress->suite;
        $address['street'] =  $contact_address['street'];
        $address['postal_code'] = $contact_address['postal_code'];
        $address['city'] = $contact_address['city'];
        $PocomosAddress->update($address);


        // routing address
        $routing_address = ($request->routing_address ?? array());

        $PocomosAddress = PocomosAddress::findOrFail($PocomosCompanyOffice->routing_address_id);

        if (isset($routing_address['phone'])) {
            $inputphone = [];
            $inputphone['type'] = "Mobile";
            $inputphone['number'] = $routing_address['phone'];
            $inputphone['alias'] = "Primary";
            $inputphone['active'] = 1;

            $PocomosPhoneNumber = PocomosPhoneNumber::findOrFail($PocomosAddress->phone_id);

            if ($PocomosPhoneNumber) {
                $PocomosPhoneNumber->update($inputphone);
            } else {
                $Configuration =  PocomosPhoneNumber::create($inputphone);
                $address['phone_id'] =  $Configuration->id;
            }
        }

        if (isset($routing_address['alt_phone'])) {
            $inputphone = [];
            $inputphone['type'] = "Mobile";
            $inputphone['number'] = $routing_address['alt_phone'];
            $inputphone['alias'] = "Alternate";
            $inputphone['active'] = 1;

            $PocomosPhoneNumber = PocomosPhoneNumber::findOrFail($PocomosAddress->alt_phone_id);

            if ($PocomosPhoneNumber) {
                $PocomosPhoneNumber->update($inputphone);
            } else {
                $Configuration =  PocomosPhoneNumber::create($inputphone);
                $address['alt_phone_id'] =  $Configuration->id;
            }
        }

        if (isset($routing_address['region_id'])) {
            $address['region_id'] = $routing_address['region_id'];
        }

        $address['suite'] =  $routing_address['suite'] ?? $PocomosAddress->suite;
        $address['street'] =  $routing_address['street'] ?? $PocomosAddress->street;
        $address['postal_code'] = $routing_address['postal_code'] ?? $PocomosAddress->postal_code;
        $address['city'] = $routing_address['city'] ?? $PocomosAddress->city;
        $PocomosAddress->update($address);


        if ($request->file('logo')) {
            $OrkestraFile = OrkestraFile::findOrFail($PocomosCompanyOffice->logo_file_id);

            $signature = $request->file('logo');                //store file into document folder

            $sign_detail['filename'] = preg_replace('/\s+/', '', $signature->getClientOriginalName());
            $sign_detail['mime_type'] = $signature->getMimeType();
            $sign_detail['file_size'] = $signature->getSize();
            $sign_detail['active'] = 1;
            $sign_detail['md5_hash'] =  md5_file($signature->getRealPath());

            $url = "Office" . "/" . $sign_detail['filename'];
            Storage::disk('s3')->put($url, file_get_contents($signature));
            $sign_detail['path'] = Storage::disk('s3')->url($url);

            if ($OrkestraFile) {
                $OrkestraFile->update($sign_detail);
            } else {
                $Configuration =  OrkestraFile::create($sign_detail);
                $input_details['logo_file_id'] =  $Configuration->id;
            }
        }

        $PocomosCompanyOffice->update($input_details);

        $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $request->office_id)->first();

        if ($PocomosOfficeSetting) {
            $inputphone = [];
            $inputphone['timezone_id'] = $request->timeZone;
            $inputphone['send_agreement_copy'] =  $request->send_agreement_copy;
            $inputphone['deliver_email'] =  $request->deliverEmail;
            $PocomosOfficeSetting->update($inputphone);
        } else {
            $inputphone = [];
            $inputphone['timezone_id'] = $request->timeZone;
            $inputphone['theme'] = 'default';
            $inputphone['enable_points'] = 1;
            $inputphone['active'] = 1;
            $inputphone['sales_tax'] = 0;
            $inputphone['tax_code_required'] = 0;
            $inputphone['send_agreement_copy'] =  $request->send_agreement_copy;
            $inputphone['deliver_email'] =  $request->deliverEmail;
            $inputphone['price_per_customer'] = 0;
            $inputphone['office_id'] = $PocomosCompanyOffice->id;
            $PocomosPhoneNumber =  PocomosOfficeSetting::create($inputphone);
        }

        return $this->sendResponse(true, 'Company updated successfully.', $PocomosCompanyOffice);
    }

    /* API for reactivate Users */
    public function reactivateUsers($id)
    {
        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($id);

        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office');
        }

        $update_data = DB::select(DB::raw("UPDATE orkestra_users AS u
                JOIN pocomos_company_office_users AS ou ON u.id = ou.user_id
                SET u.active = 1, ou.deactivated_with_office = 0
                WHERE ou.office_id = '$id' AND ou.deactivated_with_office = 1"));

        return $this->sendResponse(true, 'Users reactive successfully.', $update_data);
    }

    /**
     * API for delete of Company
     .
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company not found.');
        }

        $PocomosCompanyOffice->delete();

        return $this->sendResponse(true, 'Company deleted successfully.');
    }

    /**
     * API for details of Company
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function companies_offices($id)
    {
        $office = PocomosCompanyOffice::findOrFail($id);

        $childOfficees = PocomosCompanyOffice::where('parent_id', $id)->where('active', 1)->with('coontact_address')->orderBy('id', 'desc')->select('id', 'contact_address_id', 'name', 'contact_name', 'list_name', 'parent_id')->get();

        $parent_office_name = PocomosCompanyOffice::where('id', $id)->select('id', 'contact_address_id', 'name', 'contact_name', 'list_name')->first();

        $childOfficees->map(function ($status) {
            $status->child_office_data = PocomosReportsOfficeState::where('office_id', $status->id)->where('type', 'State')->first();

            $PocomosReportsOfficeState = PocomosReportsOfficeState::where('office_id', $status->id)->where('type', 'State')->pluck('customers')->first();

            $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $status->id)->pluck('price_per_customer')->first();

            $status->recurring = $PocomosReportsOfficeState * $PocomosOfficeSetting;
        });

        $data = [
            'parent_office_name' => $parent_office_name,
            'records' => $childOfficees,
        ];

        return $this->sendResponse(true, 'Company details.', $data);
    }

    /**
     * API for details of optimization
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function optimization(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'status' => 'required|boolean',
            'enable_best_fit_rescheduling' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosPestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosPestOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        $PocomosPestOfficeSetting->update([
            'enable_optimization' => $request->status,
            'enable_best_fit_rescheduling' => $request->enable_best_fit_rescheduling
        ]);

        return $this->sendResponse(true, 'Setting changed successfully.');
    }


    public function optimizationget(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosPestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->select('enable_optimization', 'enable_best_fit_rescheduling')
            ->first();

        if (!$PocomosPestOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        return $this->sendResponse(true, 'Office Configuration.', $PocomosPestOfficeSetting);
    }

    /**
     * API for details of configuration
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function configuration(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'status' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Not Found');
        }

        $PocomosRecruitingOfficeConfiguration = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosRecruitingOfficeConfiguration) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        $PocomosRecruitingOfficeConfiguration->update([
            'recruiting_enabled' => $request->status
        ]);

        // return $this->sendResponse(true, 'Setting changed successfully.', $PocomosRecruitingOfficeConfiguration);
        return $this->sendResponse(true, __('strings.update', ['name' => 'Recruiting Office Configuration']), $PocomosRecruitingOfficeConfiguration);
    }


    public function configurationGet(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Not Found');
        }

        $PocomosRecruitingOfficeConfiguration = PocomosRecruitingOfficeConfiguration::where('office_id', $request->office_id)->select('recruiting_enabled')
            ->first();

        if (!$PocomosRecruitingOfficeConfiguration) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        return $this->sendResponse(true, 'Office Configuration.', $PocomosRecruitingOfficeConfiguration);
    }

    /**
     * API for details of configuration
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function security(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'hide_cc' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        $PocomosOfficeSetting->update([
            'hide_cc' => $request->hide_cc
        ]);

        return $this->sendResponse(true, 'Security Settings updated successfully.');
    }

    public function securityget(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $request->office_id)->select('hide_cc')
            ->first();

        if (!$PocomosOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        return $this->sendResponse(true, 'Office Configuration.', $PocomosOfficeSetting);
    }

    /**
     * API for details of salestax setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function salestaxupdate(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'sales_tax' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        $PocomosOfficeSetting->update([
            'sales_tax' => $request->sales_tax
        ]);

        return $this->sendResponse(true, 'Sales Tax Configuration updated successfully.');
    }


    public function salestaxget(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $request->office_id)->select('sales_tax')
            ->first();

        if (!$PocomosOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        return $this->sendResponse(true, 'Office Configurations.', $PocomosOfficeSetting);
    }

    public function zipcode(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'status' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosPestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosPestOfficeSetting) {
            $PocomosPestOfficeSetting = $this->createPestOfficeSetting($request->office_id);
        }

        $input_details['validate_zipcode'] = $request->status;
        $PocomosPestOfficeSetting->update($input_details);

        return $this->sendResponse(true, 'Setting changed successfully.');
    }


    public function zipcodeget(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosPestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->select('validate_zipcode')
            ->first();

        if (!$PocomosPestOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        return $this->sendResponse(true, 'Office Configuration.', $PocomosPestOfficeSetting);
    }

    /**
     * API for details of sms setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function smsUpdate(Request $request)
    {
        $v = validator($request->all(), [
            'sender_phone_id' => 'required|exists:pocomos_phone_numbers,id',
            'vantage_dnc_registry' => 'required|boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        $PocomosOfficeSetting->update(
            $request->only('sender_phone_id', 'vantage_dnc_registry', 'vantage_dnc_uid', 'vantage_dnc_username')
        );

        return $this->sendResponse(true, 'Office Security updated successfully.', $PocomosOfficeSetting);
    }

    /**
     * API for details of sms setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function smsget(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $request->office_id)->select('sender_phone_id', 'vantage_dnc_registry', 'vantage_dnc_uid', 'vantage_dnc_username')
            ->first();

        if (!$PocomosOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        return $this->sendResponse(true, 'Office Configuration.', $PocomosOfficeSetting);
    }

    /**
     * API for details of email setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function emailUpdate(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        if ($request->sender_email_id == "Null") {
            $PocomosOfficeSetting->update([
                'sender_email_id' => null
            ]);
        } else {
            $PocomosOfficeSetting->update([
                'sender_email_id' => $request->sender_email_id
            ]);
        }


        return $this->sendResponse(true, 'Office Email updated successfully.', $PocomosOfficeSetting);
    }

    /**
     * API for details of email setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function emailGet(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $request->office_id)->select('sender_email_id')
            ->first();

        if (!$PocomosOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        return $this->sendResponse(true, 'Office Configuration.', $PocomosOfficeSetting);
    }

    /**
     * API for details of vtp setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function vtpUpdate(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'vtp_enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $SalestrackerOfficeSetting = PocomosSalestrackerOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$SalestrackerOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        $SalestrackerOfficeSetting->update([
            'vtp_enabled' => $request->vtp_enabled
        ]);

        return $this->sendResponse(true, 'Office Settings updated successfully.', $SalestrackerOfficeSetting);
    }

    /**
     * API for details of vtp setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function vtpget(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $SalestrackerOfficeSetting = PocomosSalestrackerOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$SalestrackerOfficeSetting) {
            return $this->sendResponse(false, 'Unable to find the Office Configuration');
        }

        return $this->sendResponse(true, 'Office Configuration.', $SalestrackerOfficeSetting);
    }

    /**
     * API for update details of custom-agreements setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function updAgreement(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'custom_agreement_id' => 'required|array|exists:pocomos_custom_agreement_templates,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomAgreementToOffice = PocomosCustomAgreementToOffice::where('office_id', $request->office_id)
            ->delete();

        foreach ($request->custom_agreement_id as $key => $language_detail) {
            $input_details['custom_agreement_id'] = $language_detail;
            $input_details['office_id'] = $request->office_id;
            $success = PocomosCustomAgreementToOffice::create($input_details);
        }

        return $this->sendResponse(true, 'The Office has been updated successfully');
    }



    /**
     * API for get details of custom-agreements setting
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function getupdAgreement(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomAgreementToOffice = PocomosCustomAgreementToOffice::where('office_id', $request->office_id)->select('custom_agreement_id')->get()->toArray();

        $agreement_data = PocomosCustomAgreementTemplate::whereIn('id', ($PocomosCustomAgreementToOffice))->select('name', 'id')->get();

        return $this->sendResponse(true, 'Agreement data', $agreement_data);
    }

    /**
     * API for list of The country region
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function countryregionlist(Request $request)
    {
        $OrkestraCountryRegion = OrkestraCountryRegion::orderBy('id', 'desc')
            ->get();

        return $this->sendResponse(true, 'List of the country region.', $OrkestraCountryRegion);
    }

    /**
     * API for list of The country
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function countrylist(Request $request)
    {
        $OrkestraCountry = OrkestraCountry::orderBy('id', 'desc')
            ->get();

        return $this->sendResponse(true, 'List of the country .', $OrkestraCountry);
    }

    /**
     * API for list of The country
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function countrywithregion(Request $request)
    {
        $OrkestraCountry = DB::table('orkestra_countries as sa')->join('orkestra_countries_regions as ca', 'sa.id', '=', 'ca.country_id')->select('sa.name as CountriesRegionName', 'ca.*')->get();

        return $this->sendResponse(true, 'List of the country .', $OrkestraCountry);
    }

    /**
     * API for details of All branches
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function all_branches(Request $request)
    {
        $branches = PocomosCompanyOffice::query();
        $branches = $branches->with('contact.primaryPhone');

        $branches->whereNotNull('parent_id');

        if ($request->status == 'active') {
            $branches->whereActive(true);
        } elseif ($request->status  == 'inactive') {
            $branches->whereActive(false);
        }

        if ($request->search) {
            $search = $request->search;
            $addrIds = PocomosAddress::where('street', 'like', '%' . $search . '%')
                ->orWhere('suite', 'like', '%' . $search . '%')
                ->orWhere('city', 'like', '%' . $search . '%')
                ->orWhere('postal_code', 'like', '%' . $search . '%')
                ->pluck('id')->toArray();
            $addrIds = array_unique($addrIds);

            $numIds = PocomosPhoneNumber::where('number', 'like', '%' . $search . '%')->where('alias', 'Primary')->pluck('id')->toArray();
            $numIds = array_unique($numIds);

            if ($numIds) {
                $numAddrIds = PocomosAddress::query();
                if (PocomosCompanyOffice::whereIn('contact_address_id', $addrIds)->count()) {
                    $numAddrIds = $numAddrIds->whereIn('id', $addrIds);
                }
                $numAddrIds = $numAddrIds->whereIn('phone_id', $numIds)->pluck('id')->toArray();

                $numAddrIds = array_unique($numAddrIds);
                $addrIds = array_merge($addrIds, $numAddrIds);
                $addrIds = array_unique($addrIds);
            }
            if (PocomosCompanyOffice::whereIn('contact_address_id', $addrIds)->count()) {
                $branches->whereIn('contact_address_id', $addrIds);
            } else {
                $branches->where(function ($query) use ($search) {
                    $query
                        ->where('id', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
            }
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $branches->count();
        $branches->skip($perPage * ($page - 1))->take($perPage);

        $branches = $branches->orderBy('id', 'desc')->get();

        foreach ($branches as $q) {
            $userIds = PocomosCompanyOfficeUser::whereOfficeId($q->office_id)->pluck('user_id');
            $user = OrkestraUser::whereIn('id', $userIds)->orderByDesc('last_login')->first();
            if ($user) {
                $q->last_login = $user->last_login;
            }
        }

        $branches->map(function ($officies) {
            if ($officies['parent_id']) {
                $parent_office = PocomosCompanyOffice::where('id', $officies['parent_id'])->first();
                $officies['parent_office_name'] = $parent_office->name;
            }
        });

        $res = [
            'branches' => $branches,
            'count' => $count
        ];
        return $this->sendResponse(true, 'Company details.', $res);
    }

    /**
     * Switches branches
     *
     * @param Request $request
     */
    public function switchBranch(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office_id = $request->office_id;
        $this->switchOffice($office_id);

        $officeUserId = Session::get(config('constants.ACTIVE_OFFICE_USER_ID'));
        $officeUser = PocomosCompanyOfficeUser::findOrFail($officeUserId);
        $user = OrkestraUser::whereId($officeUser->user_id)->first();
        // with('pocomos_company_office_users.company_details.office_settings')->
        $allOffices = PocomosCompanyOffice::with('office_settings', 'logo', 'coontact_address')->where('parent_id', $office_id)->get()->toArray();
        if (!$allOffices) {
            $allOffices = PocomosCompanyOffice::whereId($office_id)->first();
            $allOffices = PocomosCompanyOffice::with('office_settings', 'logo', 'coontact_address')->whereId($allOffices->parent_id)->get()->toArray();
        }
        $parentOffice = PocomosCompanyOffice::with('office_settings', 'logo', 'coontact_address')->whereId($office_id)->first()->toArray();
        $allOffices[] = $parentOffice;
        $success['user'] =  $user;
        //Create new token
        $success['token'] =  $user->createToken('MyAuthApp')->plainTextToken;

        $i = 0;
        foreach ($allOffices as $office) {
            $current_active_office = Session::get(config('constants.ACTIVE_OFFICE_ID'));
            $is_default_selected = false;
            if ($current_active_office == $office['id']) {
                $is_default_selected = true;
            }
            $allOffices[$i]['is_default_selected'] = $is_default_selected;
            $i = $i + 1;
        }
        $user->offices_details = $allOffices;

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Office switched']), $success);
    }

    /**
     * Creates a new Office entity (clone branch)
     *
     * @Secure(roles="ROLE_COMPANY_WRITE")
     * @param Request $request
     */
    public function cloneBranch(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        FacadesDB::beginTransaction();
        try {
            $office = PocomosCompanyOffice::findOrFail($request->office_id);
            $cloneOffice = $this->cloneOffice($office);

            $args = array($cloneOffice->id);

            OfficeSnapshotJob::dispatch($args);

            OfficeStateJob::dispatch($args);

            $this->createEmailTypeSettings($office, $cloneOffice);

            FacadesDB::commit();
        } catch (\Exception $e) {
            FacadesDB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Office cloned']), array('id' => $cloneOffice->id));
    }

    /**
     * Creates a new Office entity (clone branch)
     *
     * @Secure(roles="ROLE_COMPANY_WRITE")
     * @param Request $request
     */
    public function runAutoPay(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        FacadesDB::beginTransaction();
        try {
            $office = PocomosCompanyOffice::findOrFail($request->office_id);

            $cspIds = PocomosCustomerSalesProfile::where('office_id', $request->office_id)->pluck('id')->toArray();

            $args = $cspIds;

            AutopayJob::dispatch($args);

            FacadesDB::commit();
        } catch (\Exception $e) {
            FacadesDB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }
        return $this->sendResponse(true, __('strings.sucess', ['name' => 'An autopay job has been completed']));
    }

    /**Get office fix details */
    public function getDetails(Request $request)
    {
        $v = validator($request->all(), [
            'type' => 'required|in:billing_frequencies,service_frequencies,jobs',
            'lenth_per_month' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $data = array();
        $lenth_per_month = $request->lenth_per_month;

        if ($request->type === 'billing_frequencies') {
            $data = $this->getBillingFrequency();
        } elseif ($request->type === 'service_frequencies') {
            $data = $this->getServiceFrequency($lenth_per_month);
        } else {
            $data = $this->getJobTypes();
        }

        return $this->sendResponse(true, __('strings.list', ['name' => $request->type]), $data);
    }

    /* API for Deactivate Users */
    public function deactivateAllUsers($id)
    {
        PocomosCompanyOffice::findOrFail($id);

        $update_data = DB::select(DB::raw("UPDATE orkestra_users AS u
            JOIN pocomos_company_office_users AS ou ON u.id = ou.user_id
            SET u.active = 0, ou.deactivated_with_office = 1
            WHERE ou.office_id = $id AND ou.active = 1"));

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Users deactivated']));
    }

    /**Get user office details */
    public function getUserOffices($id)
    {
        $office = PocomosCompanyOffice::where('id', $id)->firstOrFail();
        $officesDetails = $office->getChildWithParentOffices();

        $c = 0;
        $i = 0;
        foreach ($officesDetails as $val) {
            $activeOfficeId = Session::get(config('constants.ACTIVE_OFFICE_ID')) ?? null;
            $is_default_selected = false;
            if (isset($activeOfficeId) && $activeOfficeId == $val['id']) {
                $is_default_selected = true;
            }

            if (!$is_default_selected) {
                $c = $c + 1;
            }
            if (count($officesDetails) == $c) {
                $officesDetails[$i]['is_default_selected'] = true;
            } else {
                $officesDetails[$i]['is_default_selected'] = $is_default_selected;
            }
            $i = $i + 1;
        }
        return $this->sendResponse(true, 'List of offices.', $officesDetails);
    }

    /**Get logged in user details */
    public function getLoggedinUserDetails()
    {
        $userId = auth()->user()->id ?? null;
        $activeOfficeId = Session::get(config('constants.ACTIVE_OFFICE_ID')) ?? null;

        $officeUser = PocomosCompanyOfficeUser::with('user_details', 'profile_details', 'company_details.office_settings')->whereUserId($userId)->whereOfficeId($activeOfficeId)->first();

        if (!$officeUser) {
            return $this->sendResponse(false, __('strings.something_went_wrong'));
        }

        return $this->sendResponse(true, __('strings.details', ['name' => 'User']), $officeUser);
    }

    /**
     * API for Displays a form to edit an existing OfficeConfiguration entity.
     .
     *
     * @return \Illuminate\Http\Response
     */

    public function authenticate(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'password' => 'required|max:255',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        if ($request->password == "Pocomai1!") {
            $results = true;
        } else {
            $results = false;
        }

        return $this->sendResponse(true, 'Password result.', $results);
    }

    /**Office base zipcode is valid or not check */
    public function zipcodeCheckValid(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'zipcode' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $res = array();
        $isValid = false;
        $validate_zipcode = false;
        $officeSetting = PocomosPestOfficeSetting::whereOfficeId($request->office_id)->first();
        if ($officeSetting) {
            $validate_zipcode = $officeSetting->validate_zipcode;
        }
        $res['validate_zipcode'] = $validate_zipcode;

        if ($validate_zipcode) {
            $zipCode = PocomosZipCode::whereZipCode($request->zipcode)->whereActive(true)->whereDeleted(false)->first();
            if ($zipCode) {
                $isValid = true;
            }
        }
        $res['is_valid'] = $isValid;
        return $this->sendResponse(true, __('strings.details', ['name' => 'Office base zipcode']), $res);
    }

    public function validateActionRequest(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'zipcode' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        
        $entity = PocomosZipCode::whereZipCode($request->zipcode)->whereActive(1)->whereOfficeId($request->office_id)->first();

        if (!$entity) {
            return $this->sendResponse(true, 'validate an existing Zip Code.', array(
                'error' => true,
                'message' => 'Zipcode is not allowed for this office'
            ));
        } else {
            return $this->sendResponse(true, 'validate an existing Zip Code.',array(
                'error' => false,
                'message' => 'Zipcode is allowed for this office'
            ));
        }
    }

    /**Get office setting */
    public function getOfficeSettig(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();

        return $this->sendResponse(true, 'Office Configuration.', $pestOfficeSetting);
    }
}
