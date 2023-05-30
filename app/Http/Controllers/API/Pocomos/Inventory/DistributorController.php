<?php

namespace App\Http\Controllers\API\Pocomos\Inventory;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosDistributor;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosPhoneNumber;
use DB;

class DistributorController extends Controller
{
    use Functions;

    /**
     * API for list of Distributor
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

        $PocomosDistributor = PocomosDistributor::with('coontact_address')->where('office_id', $request->office_id)->where('active', 1);

        // if ($request->search) {
        //     $search = $request->search;
        //     $PocomosDistributor->where(function ($query) use ($search) {
        //         $query->where('name', 'like', '%' . $search . '%')
        //             ->orWhere('contact_name', 'like', '%' . $search . '%');
        //     });
        // }

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
                if (PocomosDistributor::whereIn('contact_address_id', $addrIds)->count()) {
                    $numAddrIds = $numAddrIds->whereIn('id', $addrIds);
                }
                $numAddrIds = $numAddrIds->whereIn('phone_id', $numIds)->pluck('id')->toArray();

                $numAddrIds = array_unique($numAddrIds);
                $addrIds = array_merge($addrIds, $numAddrIds);
                $addrIds = array_unique($addrIds);
            }
            if (PocomosDistributor::whereIn('contact_address_id', $addrIds)->count()) {
                $PocomosDistributor->whereIn('contact_address_id', $addrIds);
            } else {
                $PocomosDistributor->where(function ($query) use ($search) {
                    $query
                        ->where('id', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
            }

            $PocomosDistributor->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('contact_name', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosDistributor->count();
        $PocomosDistributor->skip($perPage * ($page - 1))->take($perPage);

        $PocomosDistributor = $PocomosDistributor->get();

        return $this->sendResponse(true, 'List', [
            'Distributor' => $PocomosDistributor,
            'count' => $count,
        ]);
    }

    /**
     * API for get details of Distributor.
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosDistributor = DB::table('pocomos_distributors as sa')->join('pocomos_addresses as ca', 'ca.id', '=', 'sa.contact_address_id')->join('pocomos_phone_numbers as phone', 'phone.id', '=', 'ca.phone_id')->where('sa.id', $id)->get();

        if (!$PocomosDistributor) {
            return $this->sendResponse(false, 'Distributor Not Found');
        }
        return $this->sendResponse(true, 'Distributor details.', $PocomosDistributor);
    }

    /**
     * API for create Distributor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'contact_name' => 'required',
            'contact_address.phone' => 'required',
            'contact_address.street' => 'required',
            'contact_address.city' => 'required',
            'contact_address.postal_code' => 'required',
            'contact_address.region_id' => 'nullable|exists:orkestra_countries_regions,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('office_id', 'name', 'contact_name');
        $input_details['active'] = 1;

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

        $PocomosDistributor =  PocomosDistributor::create($input_details);

        return $this->sendResponse(true, 'Distributor created successfully.', $PocomosDistributor);
    }

    /**
     * API for update Distributor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'contact_name' => 'required',
            'contact_address.phone' => 'required',
            'contact_address.street' => 'required',
            'contact_address.city' => 'required',
            'contact_address.postal_code' => 'required',
            'contact_address.region_id' => 'nullable|exists:orkestra_countries_regions,id',
            'distributor_id' => 'required|exists:pocomos_distributors,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosDistributor = PocomosDistributor::where('office_id', $request->office_id)->where('id', $request->distributor_id)->where('active', 1)->first();

        if (!$PocomosDistributor) {
            return $this->sendResponse(false, 'Distributor not found.');
        }

        $input_details = $request->only('name', 'contact_name');

        $contact_address = ($request->contact_address ?? array());

        $PocomosAddress = PocomosAddress::find($PocomosDistributor->contact_address_id);

        if (isset($contact_address['phone'])) {
            $inputphone = [];
            $inputphone['type'] = "Mobile";
            $inputphone['number'] = $contact_address['phone'];
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

        //if (isset($contact_address['alt_phone'])) {

        $inputphone = [];
        $inputphone['type'] = "Mobile";
        $inputphone['number'] = $contact_address['alt_phone'] ?? '';
        $inputphone['alias'] = "Alternate";
        $inputphone['active'] = 1;

        $PocomosPhoneNumber = PocomosPhoneNumber::find($PocomosAddress->alt_phone_id);

        if ($PocomosPhoneNumber) {
            $PocomosPhoneNumber->update($inputphone);
        } else {
            $Configuration =  PocomosPhoneNumber::create($inputphone);
            $address['alt_phone_id'] =  $Configuration->id;
        }
        //}

        if (isset($contact_address['region_id'])) {
            $address['region_id'] = $contact_address['region_id'];
        }

        $address['suite'] =  $contact_address['suite'] ?? '';
        $address['street'] =  $contact_address['street'];
        $address['postal_code'] = $contact_address['postal_code'];
        $address['city'] = $contact_address['city'];
        $PocomosAddress->update($address);

        $PocomosDistributor->update($input_details);

        return $this->sendResponse(true, 'Distributor updated successfully.', $PocomosDistributor);
    }

    /**
     * API for delete Distributor.
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosDistributor = PocomosDistributor::find($id);
        if (!$PocomosDistributor) {
            return $this->sendResponse(false, 'Distributor not found.');
        }

        $PocomosDistributor->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Distributor deleted successfully.');
    }
}
