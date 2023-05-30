<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosOnlineBooking;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosOnlineBookingOfficeConfiguration;
use App\Models\Pocomos\PocomosOnlineBookingAgreements;
use Illuminate\Support\Facades\DB;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Pocomos\PocomosAgreement;

class OnlineBookingConfigurationController extends Controller
{
    use Functions;

    /**
     * API for list of OnlineBooking
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $parent_office_name = PocomosOnlineBookingOfficeConfiguration::where('office_id', $request->office_id)->first();

        if (!$parent_office_name) {
            return $this->sendResponse(false, 'You are not authorized to use this section.');
        }

        if ($parent_office_name) {
            if ($parent_office_name->enabled != 1) {
                return $this->sendResponse(false, 'You are not authorized to use this section.');
            }
        }

        $PocomosOnlineBooking = PocomosOnlineBooking::where('office_id', $request->office_id)->where('active', 1);

        if ($request->search) {
            $search = $request->search;

            $tempTechIds = DB::select(DB::raw("SELECT pt.marketing_type_id
            FROM pocomos_online_booking AS pt
            JOIN pocomos_marketing_types AS ou ON pt.marketing_type_id = ou.id
            WHERE (ou.name LIKE '%$search%')"));

            $techIds = array_map(function ($value) {
                return $value->marketing_type_id;
            }, $tempTechIds);

            $PocomosOnlineBooking = $PocomosOnlineBooking->where(function ($q) use ($search, $techIds) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('date_created', $search);
                $q->orWhere('referring_url', $search);
                $q->orWhereIn('marketing_type_id', $techIds);
                $q->orderBy('id', 'DESC');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosOnlineBooking->count();
        $PocomosOnlineBooking = $PocomosOnlineBooking->skip($perPage * ($page - 1))->take($perPage);

        $PocomosOnlineBooking = $PocomosOnlineBooking->with(
            'office_details',
            'marketing_type_details',
            'salesperson_details.office_user_details.user_details_name',
            'technician_details.user_detail.user_details_name',
        )->get();

        $data = [
            'OnlineBooking' => $PocomosOnlineBooking,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'OnlineBooking']), $data);
    }

    /**
     * API for get  OnlineBooking details
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosOnlineBooking = PocomosOnlineBooking::find($id);
        if (!$PocomosOnlineBooking) {
            return $this->sendResponse(false, 'OnlineBooking Not Found');
        }
        return $this->sendResponse(true, 'OnlineBooking details.', $PocomosOnlineBooking);
    }

    /**
     * API for create of OnlineBooking
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|unique:pocomos_online_booking',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'referring_url' => 'required',
            'marketing_type_id' => 'required|exists:pocomos_marketing_types,id',
            'salesperson_id' => 'required|exists:pocomos_salespeople,id',
            'technician_id' => 'required|exists:pocomos_technicians,id',
            'agreements' => 'nullable|array|exists:pocomos_pest_agreements,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input = $request->only('office_id', 'name', 'referring_url', 'marketing_type_id', 'salesperson_id', 'technician_id');

        $parent_office_name = PocomosOnlineBookingOfficeConfiguration::where('office_id', $request->office_id)->first();

        if (!$parent_office_name) {
            return $this->sendResponse(false, 'You are not authorized to use this section.');
        }

        if ($parent_office_name->enabled != 1) {
            return $this->sendResponse(false, 'You are not authorized to use this section.');
        }

        $PocomosOnlineBooking = PocomosOnlineBooking::create($input);

        if (isset($request->agreements)) {
            foreach ($request->agreements as $tag) {
                $input_details['agreement_id'] = $tag;
                $input_details['booking_id'] = $PocomosOnlineBooking->id;
                $success = PocomosOnlineBookingAgreements::create($input_details);
            }
        }


        /**End manage trail */
        return $this->sendResponse(true, 'OnlineBooking created successfully.', $PocomosOnlineBooking);
    }

    /**
     * API for update of OnlineBooking
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'booking_id' => 'required|exists:pocomos_online_booking,id',
            'name' => 'required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'referring_url' => 'required',
            'marketing_type_id' => 'required|exists:pocomos_marketing_types,id',
            'salesperson_id' => 'required|exists:pocomos_salespeople,id',
            'technician_id' => 'required|exists:pocomos_technicians,id',
            'agreements' => 'nullable|array|exists:pocomos_pest_agreements,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosOnlineBooking = PocomosOnlineBooking::where('id', $request->booking_id)->where('office_id', $request->office_id)->first();

        if (!$PocomosOnlineBooking) {
            return $this->sendResponse(false, 'OnlineBooking not found.');
        }

        $input = $request->only('office_id', 'name', 'referring_url', 'marketing_type_id', 'salesperson_id', 'technician_id');

        $result =  $PocomosOnlineBooking->update($input);

        if ($request->agreements) {
            $PocomosLeadQuoteTag = PocomosOnlineBookingAgreements::where('booking_id', $request->booking_id)
                ->delete();

            foreach ($request->agreements as $tag) {
                $input_details['agreement_id'] = $tag;
                $input_details['booking_id'] = $request->booking_id;
                $success = PocomosOnlineBookingAgreements::create($input_details);
            }
        }

        return $this->sendResponse(true, 'OnlineBooking updated successfully.', $PocomosOnlineBooking);
    }

    /**
     * API for delete of OnlineBooking
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosOnlineBooking = PocomosOnlineBooking::find($id);
        if (!$PocomosOnlineBooking) {
            return $this->sendResponse(false, 'OnlineBooking not found.');
        }

        $PocomosOnlineBooking->delete();

        return $this->sendResponse(true, 'OnlineBooking deleted successfully.');
    }


    /* API for changeStatus of  OnlineBooking */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'booking_id' => 'required|exists:pocomos_online_booking,id',
            'active' => 'boolean|required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosOnlineBooking = PocomosOnlineBooking::find($request->booking_id);
        if (!$PocomosOnlineBooking) {
            return $this->sendResponse(false, 'OnlineBooking type not found');
        }

        $PocomosOnlineBooking->update([
            'active' => $request->active
        ]);

        return $this->sendResponse(true, 'Status changed successfully.');
    }
}
