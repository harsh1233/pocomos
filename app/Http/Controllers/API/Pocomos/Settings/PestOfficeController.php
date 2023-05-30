<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAcsNotification;
use App\Models\Pocomos\PocomosAcsEvent;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Orkestra\OrkestraFile;
use Illuminate\Support\Facades\Storage;

class PestOfficeController extends Controller
{
    use Functions;


    /**
     * API for details of Company
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosCompanyOffice = PocomosCompanyOffice::where('id', $id)->with('coontact_address')->with('routing_address')->get();

        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Not Found');
        }

        $PocomosCompanyOffice->map(function ($status) {
            $status->email_data = [];
            if (unserialize($status->email)) {
                $status->email_data = unserialize($status->email);
            }

            $status->Office_Setting = PocomosOfficeSetting::where('office_id', $status->id)->with('timezone')->first();
        });

        $data = [
            'records' => $PocomosCompanyOffice,
        ];

        return $this->sendResponse(true, 'Company details.', $PocomosCompanyOffice);
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
            'customer_portal_link'=>'required|url',
            'url'=>'required|url',

        ], [
            'name.required' => 'The street field is required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::find($request->office_id);

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

        $contact_address = ($request->contact_address ?? array());

        $PocomosAddress = PocomosAddress::find($PocomosCompanyOffice->contact_address_id);

        if (isset($contact_address['phone'])) {
            $inputphone = [];
            $inputphone['type'] = "Mobile";
            $inputphone['number'] = $contact_address['phone'];
            $inputphone['alias'] = "Primary";
            $inputphone['active'] = 1;

            if ($PocomosAddress) {
                $PocomosPhoneNumber = PocomosPhoneNumber::find($PocomosAddress->phone_id);
            }

            if (isset($PocomosPhoneNumber)) {
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

            if ($PocomosAddress) {
                $PocomosPhoneNumber = PocomosPhoneNumber::find($PocomosAddress->alt_phone_id);
            }

            if (isset($PocomosPhoneNumber)) {
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

        if ($PocomosAddress) {
            $PocomosAddress->update($address);
        }


        $routing_address = ($request->routing_address ?? array());

        $PocomosAddress = PocomosAddress::find($PocomosCompanyOffice->routing_address_id);

        if (isset($routing_address['phone'])) {
            $inputphone = [];
            $inputphone['type'] = "Mobile";
            $inputphone['number'] = $routing_address['phone'];
            $inputphone['alias'] = "Primary";
            $inputphone['active'] = 1;

            $PocomosPhoneNumber = PocomosPhoneNumber::find($PocomosAddress->phone_id);

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

            $PocomosPhoneNumber = PocomosPhoneNumber::find($PocomosAddress->alt_phone_id);

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
            $OrkestraFile = OrkestraFile::find($PocomosCompanyOffice->logo_file_id);

            $signature = $request->file('logo');                //store file into document folder

            $sign_detail['filename'] = $signature->getClientOriginalName();
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
        }

        return $this->sendResponse(true, 'Company updated successfully.', $PocomosCompanyOffice);
    }
}
