<?php

namespace App\Http\Controllers\API\Pocomos\Lead;

use Excel;
use DateTime;
use Geokit\Bounds;
use Geokit\LatLng;
use Twilio\Rest\Client;
use App\Jobs\ExportLeads;
use App\Exports\ExportLead;
use Illuminate\Http\Request;
use App\Jobs\SendMassSmsLeadJob;
use App\Notifications\SendEmail;
use App\Jobs\SendEmailLeadExport;
use App\Jobs\SendMassEmailLeadJob;
use App\Models\Pocomos\PocomosTag;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosLead;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosPest;
use App\Models\Pocomos\PocomosTeam;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Pocomos\PocomosEmail;
use App\Models\Orkestra\OrkestraUser;
use Illuminate\Support\Facades\Crypt;
use App\Models\Orkestra\OrkestraGroup;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosLeadNote;
use App\Models\Pocomos\PocomosSmsUsage;
use App\Models\Pocomos\PocomosLeadQuote;
use App\Models\Pocomos\PocomosSalesArea;
use App\Models\Pocomos\PocomosOfficeAlert;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\OfficeConfiguration;
use App\Models\Pocomos\PocomosEmailMessage;
use App\Models\Pocomos\PocomosLeadQuoteTag;
use App\Models\Pocomos\PocomosLeadQuotPest;
use App\Models\Pocomos\PocomosLeadReminder;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Orkestra\OrkestraCountryRegion;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosSalespersonProfile;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosSalesAreaPivotTeams;
use App\Models\Pocomos\PocomosSalesAreaPivotManager;
use App\Models\Pocomos\PocomosSmsReceivedMessageLog;
use App\Models\Pocomos\PocomosLeadQuoteSpecialtyPest;
use App\Models\Pocomos\PocomosReportSalespersonState;
use App\Models\Pocomos\PocomosLeadNotInterestedReason;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Pocomos\PocomosSalesAreaPivotSalesPerson;

class LeadController extends Controller
{
    use Functions;

    /**
     * API for list of Lead
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable',
            'status' => 'array|in:lead,not home,not interested,monitor',
            'is_empty' => 'nullable|boolean'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $data = array();
        $officeId = $request->office_id;
        $isEmpty = $request->is_empty ?? null;

        if ($isEmpty == 1) {
            return $this->sendResponse(true, __('strings.list', ['name' => 'Leads']), $data);
        }

        $PocomosLead = PocomosLead::query();

        $PocomosLead = $PocomosLead->where('active', 1);

        if ($request->salesperson) {
            $PocomosLeadQuote = PocomosLeadQuote::where('salesperson_id', $request->salesperson)->pluck('id')->toArray();
            $PocomosLead = $PocomosLead->whereIn('quote_id', $PocomosLeadQuote);
        }

        if ($request->status) {
            $PocomosLead = $PocomosLead->whereIn('status', $request->status);
        }

        /**For search filters */
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
                if (PocomosLead::whereIn('contact_address_id', $addrIds)->count()) {
                    $numAddrIds = $numAddrIds->whereIn('id', $addrIds);
                }
                $numAddrIds = $numAddrIds->whereIn('phone_id', $numIds)->pluck('id')->toArray();

                $numAddrIds = array_unique($numAddrIds);
                $addrIds = array_merge($addrIds, $numAddrIds);
                $addrIds = array_unique($addrIds);
            }

            if (PocomosLead::whereIn('contact_address_id', $addrIds)->count()) {
                $PocomosLead->whereIn('contact_address_id', $addrIds);
            } else {
                $qtIdsTmp = DB::select(DB::raw("SELECT plq.id
                FROM pocomos_lead_quotes AS plq
                JOIN pocomos_salespeople AS psp ON plq.salesperson_id = psp.id
                JOIN pocomos_company_office_users AS pcou ON psp.user_id = pcou.id
                JOIN orkestra_users AS ou ON pcou.user_id = ou.id
                WHERE (ou.first_name like '%$search%' OR ou.last_name like '%$search%')"));

                $qtIds = array_map(function ($value) {
                    return $value->id;
                }, $qtIdsTmp);

                $mapQtIds = PocomosLeadQuote::where(function ($query) use ($search) {
                    $query->where('map_code', 'like', '%' . $search . '%');
                })->pluck('id')->toArray();

                $qtIds = array_merge($qtIds, $mapQtIds);
                $qtIds = array_unique($qtIds);

                $ntLeadIdsTmp = DB::select(DB::raw("SELECT pln.lead_id
                FROM pocomos_leads_notes AS pln
                JOIN pocomos_notes AS pn ON pln.note_id = pn.id
                WHERE (pn.summary like '%$search%')"));

                $ntLeadIds = array_map(function ($value) {
                    return $value->lead_id;
                }, $ntLeadIdsTmp);

                $PocomosLead->where(function ($query) use ($search, $qtIds, $ntLeadIds) {
                    $query->whereIn('id', $ntLeadIds);
                    if ($qtIds) {
                        $query->orWhereIn('quote_id', $qtIds);
                    }
                    $query->orWhere('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('status', 'like', '%' . $search . '%')
                        ->orWhere('date_created', 'like', '%' . $search . '%');
                });
            }
        }
        /**End search filters */

        /**For pagination */
        // $count = $PocomosLead->count();
        // if ($request->page && $request->perPage) {
        //     $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        //     $page    = $paginateDetails['page'];
        //     $perPage = $paginateDetails['perPage'];
        //     $PocomosLead->skip($perPage * ($page - 1))->take($perPage);
        // }

        $PocomosLead = $PocomosLead->whereHas('quote_id_detail.sales_person_detail.office_user_details', function ($q) use ($officeId) {
            $q->whereOfficeId($officeId);
        })->with(
            'addresses',
            'quote_id_detail.service_type',
            'quote_id_detail.found_by_type_detail',
            'quote_id_detail.county_detail',
            'quote_id_detail.sales_person_detail.office_user_details.user_details',
            'quote_id_detail.pest_agreement_detail.agreement_detail:id,name',
            'quote_id_detail.tags.tag_detail',
            'quote_id_detail.pests.pest_detail',
            'quote_id_detail.specialty_pests.specialty_pest_detail',
            'permanent_note.note_detail',
            'quote_id_detail.technician_detail.user_detail.user_details:id,username,first_name,last_name',
            'not_interested_reason',
            'initial_job',
            'lead_reminder'
        )->orderBy('id', 'desc');

        /**For pagination */
        $count = $PocomosLead->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosLead->skip($perPage * ($page - 1))->take($perPage);
        }

        $PocomosLead = $PocomosLead->get();

        $data = [
            'leads' => $PocomosLead,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Leads']), $data);
    }

    /**
     * API for details of Lead
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosLead = PocomosLead::findOrFail($id);
        if (!$PocomosLead) {
            return $this->sendResponse(false, 'Lead Not Found');
        }
        return $this->sendResponse(true, 'Lead details.', $PocomosLead);
    }

    /**
     * API for create of Leads
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'nullable|exists:orkestra_users,id',
            'first_name' => 'nullable',
            'last_name' => 'nullable',
            'email' => 'nullable',
            'phone' => 'nullable',
            'initial_job_note' => 'nullable',
            'permanent_note' => 'nullable',
            'status' => 'nullable',
            'salesperson_id' => 'nullable',
            'street' => 'nullable',
            'suite' => 'nullable',
            'city' => 'nullable',
            'region_id' => 'nullable|exists:orkestra_countries_regions,id',
            'postal_code' => 'nullable',
            'map_code' => 'nullable',
            'service_type' => 'nullable',
            'service_frequency' => 'nullable',
            'marketing_type' => 'nullable',
            'contract_type' => 'nullable',
            'normal_initial' => 'nullable',
            'initial_discount' => 'nullable',
            'initial_price' => 'nullable',
            'recurring_price' => 'nullable',
            'job_duration' => 'nullable',
            'initial_job_duration' => 'nullable',
            'week_of_the_month' => 'nullable',
            'day_of_the_week' => 'nullable',
            'preferred_time' => 'nullable',
            'technician_id' => 'exists:pocomos_technicians,id',
            'make_tech_preferred' => 'nullable',
            'pest_id' => 'nullable|array|exists:pocomos_pests,id',
            'county_id' => 'nullable|exists:pocomos_counties,id',
            'special_pest_id' => 'nullable|array|exists:pocomos_pests,id',
            'tag_id' => 'nullable|array|exists:pocomos_tags,id',
            'note' => 'nullable',
            'reminder_date' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        if ($request->phone) {
            $phone = [];
            $phone['alias'] = 'Primary';
            $phone['number'] = $request->phone;
            $phone['type'] = 'Mobile';
            $phone['active'] = 1;
            $phone = PocomosPhoneNumber::create($phone);
        }
        $address = [];
        if ($request->street) {
            $address['street'] = $request->street;
        } else {
            $address['street'] = '';
        }
        if ($request->suite) {
            $address['suite'] = $request->suite;
        } else {
            $address['suite'] = '';
        }
        if ($request->city) {
            $address['city'] = $request->city;
        } else {
            $address['city'] = '';
        }
        if ($request->postal_code) {
            $address['postal_code'] = $request->postal_code;
        } else {
            $address['postal_code'] = '';
        }
        if ($request->region_id) {
            $address['region_id'] = $request->region_id;
        } else {
            $address['region_id'] = null;
        }
        if ($request->phone) {
            $address['phone_id'] = $phone->id;
        }
        $address['active'] = 1;
        $address['validated'] = 1;
        $address['valid'] = 1;
        // $address_details = $request->only('street', 'suite', 'city', 'postal_code', 'region_id');
        $PocomosAddress =  PocomosAddress::create($address);
        $pestAgreement = PocomosPestAgreement::find($request->contract_type);

        // Data array for pocomos_leads_quotes table
        $input_leads_quotes = [];
        $input_leads_quotes['service_type_id'] = $request->service_type;
        $input_leads_quotes['service_frequency'] = $request->service_frequency;
        $input_leads_quotes['found_by_type_id'] = $request->marketing_type;
        $input_leads_quotes['salesperson_id'] = $request->salesperson_id;
        $input_leads_quotes['pest_agreement_id'] = $request->contract_type ?? null;
        $input_leads_quotes['agreement_id'] = $pestAgreement->agreement_id ?? null;
        $input_leads_quotes['regular_initial_price'] = $request->normal_initial ?: '0.00';
        $input_leads_quotes['initial_discount'] = $request->initial_discount ?: '0.00';
        $input_leads_quotes['initial_price'] = $request->initial_price ?: '0.00';
        $input_leads_quotes['recurring_price'] = $request->recurring_price ?: '0.00';
        $input_leads_quotes['week_of_the_month'] = $request->week_of_the_month;
        $input_leads_quotes['day_of_the_week'] = $request->day_of_the_week;
        if ($request->week_of_the_month && $request->day_of_the_week) {
            $input_leads_quotes['specific_recurring_schedule'] = 1;
        } else {
            $input_leads_quotes['specific_recurring_schedule'] = 0;
        }
        $input_leads_quotes['map_code'] = $request->map_code ?: '';
        $input_leads_quotes['autopay'] = 0;
        $input_leads_quotes['auto_renew'] = 1;
        $input_leads_quotes['active'] = 1;
        $input_leads_quotes['tax_code'] = '';
        $input_leads_quotes['previous_balance'] = '0.00';
        $input_leads_quotes['preferred_time'] = $request->preferred_time;
        $input_leads_quotes['technician_id'] = $request->technician_id;
        $input_leads_quotes['make_tech_preferred'] = $request->make_tech_preferred ?: '0';
        $input_leads_quotes['county_id'] = $request->county_id; // county_id column is connected with pocomos_counties column.
        $PocomosLeadQuote = PocomosLeadQuote::create($input_leads_quotes);
        // Data entry of pocomos_lead_quotes_tags table
        // It is connected with pocomos_tags table
        if ($request->tag_id) {
            foreach ($request->tag_id as $tag) {
                $input_details['tag_id'] = $tag;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuoteTag::create($input_details);
            }
        }

        if ($request->pest_id) {
            foreach ($request->pest_id as $pest) {
                $input_details['pest_id'] = $pest;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuotPest::create($input_details);
            }
        }

        if ($request->special_pest_id) {
            foreach ($request->special_pest_id as $special_pest) {
                $input_details['pest_id'] = $special_pest;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuoteSpecialtyPest::create($input_details);
            }
        }

        // Data entry of pocomos_lead_reminders table
        if ($request->reminder_date) {
            $pocomos_lead_reminders = [];
            $pocomos_lead_reminders['note'] = $request->note;
            $pocomos_lead_reminders['reminder_date'] = $request->reminder_date;
            $PocomosLeadReminder = PocomosLeadReminder::create($pocomos_lead_reminders);
        }

        // Data array for pocomos_notes table
        if ($request->initial_job_note) {
            $notes_1 = [];
            $notes_1['user_id'] = $request->user_id;
            $notes_1['summary'] = $request->initial_job_note;
            $notes_1['body'] = "";
            $notes_1['interaction_type'] = 'Other';
            $notes_1['active'] = 1;
            $PocomosNoteinitial = PocomosNote::create($notes_1);
        }
        if ($request->permanent_note) {
            $notes_2 = [];
            $notes_2['user_id'] = $request->user_id;
            $notes_2['summary'] = $request->permanent_note;
            $notes_2['body'] = "";
            $notes_2['interaction_type'] = 'Other';
            $notes_2['active'] = 1;
            $PocomosNotepermanent = PocomosNote::create($notes_2);
        }

        // Data array for pocomos_leads table
        $input = [];
        $input['contact_address_id'] = $PocomosAddress->id;
        $input['billing_address_id'] = $PocomosAddress->id;
        $input['quote_id'] = $PocomosLeadQuote->id;
        $input['first_name'] = $request->first_name ?: '';
        $input['last_name'] = $request->last_name ?: '';
        $input['email'] = $request->email ?: '';
        $input['status'] = $request->status ?: 'NOT HOME';

        if ($request->not_interested_reason_id) {
            $input['not_interested_reason_id'] = $request->not_interested_reason_id;
        }

        $input['company_name'] = '';
        $input['external_account_id'] = '';
        $input['subscribed'] = 0;
        $input['active'] = 1;

        if (isset($PocomosLeadReminder)) {
            $input['lead_reminder_id'] = $PocomosLeadReminder->id;
        }
        if ($request->initial_job_note) {
            $input['initial_job_note_id'] = $PocomosNoteinitial->id;
        }

        $PocomosLead =  PocomosLead::create($input);

        if (isset($PocomosNotepermanent)) {
            $leadNote['lead_id'] = $PocomosLead->id;
            $leadNote['note_id'] = $PocomosNotepermanent->id;
            $PocomosLeadNote = PocomosLeadNote::create($leadNote);
        }

        return $this->sendResponse(true, 'Lead created successfully.', $PocomosLead);
    }



    /**
     * API for update Leads
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'user_id' => 'nullable|exists:orkestra_users,id',
            'first_name' => 'nullable',
            'last_name' => 'nullable',
            'email' => 'nullable|email',
            'street' => 'nullable',
            'suite' => 'nullable',
            'city' => 'nullable',
            'region_id' => 'nullable|exists:orkestra_countries_regions,id',
            'postal_code' => 'nullable',
            'map_code' => 'nullable',
            'phone' => 'nullable',
            'initial_job_note' => 'nullable',
            'permanent_note' => 'nullable',
            'status' => 'nullable',
            'service_type_id' => 'nullable|exists:pocomos_pest_contract_service_types,id',
            'service_frequency' => 'nullable|in:Weekly,Bi-weekly,Tri-weekly,Monthly,Bi-monthly,Twice Per Month,Every Six Weeks,Quarterly,Semi-annually,Annually,Tri-Annually,One-Time,Custom,Custom (Manual)',
            'found_by_type_id' => 'nullable|exists:pocomos_marketing_types,id',
            'pest_agreement_id' => 'nullable|exists:pocomos_pest_agreements,id',
            'regular_initial_price' => 'nullable',
            'initial_discount' => 'nullable',
            'initial_price' => 'nullable',
            'recurring_price' => 'nullable',
            'initial_duration' => 'nullable',
            'specific_recurring_schedule' => 'required|boolean',
            'week_of_the_month' =>  'nullable|in:First,Second,Fourth,Third',
            'day_of_the_week' =>  'nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'preferred_time' => 'nullable',
            'salesperson_id' => 'nullable|exists:pocomos_salespeople,id',
            'technician_id' => 'nullable|exists:pocomos_technicians,id',
            'make_tech_preferred' => 'required|boolean',
            'pest_id' => 'nullable|array|exists:pocomos_pests,id',
            'county_id' => 'nullable|exists:pocomos_counties,id',
            'special_pest_id' => 'nullable|array|exists:pocomos_pests,id',
            'tag_id' => 'nullable|array|exists:pocomos_tags,id',
            'note' => 'nullable',
            'reminder_date' => 'nullable',
            'not_interested_reason_id' => 'nullable|exists:pocomos_lead_not_interested_reasons,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosLead::findOrFail($request->lead_id);

        if (!$PocomosLead) {
            return $this->sendResponse(false, 'Lead not found.');
        }

        if ($PocomosLead->contact_address_id) {
            $PocomosAddress = PocomosAddress::findOrFail($PocomosLead->contact_address_id);
        }

        if ($request->phone) {
            if ($PocomosAddress) {
                $PocomosPhoneNumber = PocomosPhoneNumber::findOrFail($PocomosAddress->phone_id);
            }

            $phone = [];
            $phone['alias'] = 'Primary';
            $phone['number'] = $request->phone;
            $phone['type'] = 'Mobile';
            $phone['active'] = 1;

            if ($PocomosPhoneNumber) {
                $PocomosPhoneNumber->update($phone);
            } else {
                $PocomosPhoneNumber = PocomosPhoneNumber::create($phone);
            }
        }

        $address = [];
        if ($request->street) {
            $address['street'] = $request->street;
        } else {
            $address['street'] = $PocomosAddress['street'];
        }
        if ($request->suite) {
            $address['suite'] = $request->suite;
        } else {
            $address['suite'] = $PocomosAddress['suite'];
        }
        if ($request->city) {
            $address['city'] = $request->city;
        } else {
            $address['city'] = $PocomosAddress['city'];
        }
        if ($request->postal_code) {
            $address['postal_code'] = $request->postal_code;
        } else {
            $address['postal_code'] = $PocomosAddress['postal_code'];
        }
        if ($request->region_id) {
            $address['region_id'] = $request->region_id;
        } else {
            $address['region_id'] = $PocomosAddress['region_id'];
        }
        if ($request->phone) {
            $address['phone_id'] = $PocomosPhoneNumber->id;
        }
        $address['active'] = 1;
        $address['validated'] = 1;
        $address['valid'] = 1;
        // $address_details = $request->only('street', 'suite', 'city', 'postal_code', 'region_id');

        if ($PocomosAddress) {
            $PocomosAddress->update($address);
        } else {
            $PocomosAddress =  PocomosAddress::create($address);
        }

        $input_leads_quotes = $request->only('service_type_id', 'service_frequency', 'found_by_type_id', 'pest_agreement_id', 'regular_initial_price', 'initial_discount', 'initial_price', 'recurring_price', 'week_of_the_month', 'day_of_the_week', 'map_code', 'preferred_time', 'salesperson_id', 'technician_id', 'make_tech_preferred', 'county_id', 'specific_recurring_schedule');

        if ($request->week_of_the_month && $request->day_of_the_week) {
            $input_leads_quotes['specific_recurring_schedule'] = 1;
        } else {
            $input_leads_quotes['specific_recurring_schedule'] = 0;
        }

        $input_leads_quotes['autopay'] = 0;
        $input_leads_quotes['auto_renew'] = 1;
        $input_leads_quotes['active'] = 1;
        $input_leads_quotes['tax_code'] = '';
        $input_leads_quotes['previous_balance'] = '0.00';

        if ($PocomosLead->quote_id) {
            $PocomosLeadQuote = PocomosLeadQuote::findOrFail($PocomosLead->quote_id);
        }

        if ($PocomosLeadQuote) {
            $PocomosLeadQuote->update($input_leads_quotes);
        } else {
            $PocomosLeadQuote = PocomosLeadQuote::create($input_leads_quotes);
        }

        // Data entry of pocomos_lead_quotes_tags table
        // It is connected with pocomos_tags table
        if ($request->tag_id) {
            $PocomosLeadQuoteTag = PocomosLeadQuoteTag::where('lead_quote_id', $PocomosLeadQuote->id)
                ->delete();

            foreach ($request->tag_id as $tag) {
                $input_details['tag_id'] = $tag;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuoteTag::create($input_details);
            }
        }

        if ($request->pest_id) {
            $PocomosLeadQuotPest = PocomosLeadQuotPest::where('lead_quote_id', $PocomosLeadQuote->id)->delete();

            foreach ($request->pest_id as $pest) {
                $input_details['pest_id'] = $pest;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuotPest::create($input_details);
            }
        }

        if ($request->special_pest_id) {
            $PocomosLeadQuoteSpecialtyPest = PocomosLeadQuoteSpecialtyPest::where('lead_quote_id', $PocomosLeadQuote->id)
                ->delete();

            foreach ($request->special_pest_id as $special_pest) {
                $input_details['pest_id'] = $special_pest;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuoteSpecialtyPest::create($input_details);
            }
        }

        // Data entry of pocomos_lead_reminders table
        if ($PocomosLead->lead_reminder_id) {
            $PocomosLeadReminder = PocomosLeadReminder::findOrFail($PocomosLead->lead_reminder_id);
        }

        if ($request->reminder_date) {
            $pocomos_lead_reminders = [];
            $pocomos_lead_reminders['note'] = $request->note;
            $pocomos_lead_reminders['reminder_date'] = $request->reminder_date;

            if (isset($PocomosLeadReminder)) {
                $PocomosLeadReminder->update($pocomos_lead_reminders);
            } else {
                $PocomosLeadReminder = PocomosLeadReminder::create($pocomos_lead_reminders);
            }
        }

        if ($PocomosLead->initial_job_note_id) {
            $PocomosNoteinitial = PocomosNote::findOrFail($PocomosLead->initial_job_note_id);
        }

        // Data array for pocomos_notes table
        if ($request->initial_job_note) {
            $notes_1 = [];
            $notes_1['user_id'] = $request->user_id;
            $notes_1['summary'] = $request->initial_job_note;
            $notes_1['body'] = "";
            $notes_1['interaction_type'] = 'Other';
            $notes_1['active'] = 1;

            if (isset($PocomosNoteinitial)) {
                $PocomosNoteinitial->update($notes_1);
            } else {
                $PocomosNoteinitial = PocomosNote::create($notes_1);
            }
        }

        $input = $request->only('status', 'company_name', 'external_account_id');

        $input['first_name'] = $request->first_name ?? $PocomosLead['first_name'];
        $input['last_name'] = $request->last_name ?? $PocomosLead['last_name'];
        $input['email'] = $request->email ?? $PocomosLead['email'];
        $input['contact_address_id'] = $PocomosAddress->id;
        $input['billing_address_id'] = $PocomosAddress->id;
        $input['quote_id'] = $PocomosLeadQuote->id;

        if ($request->not_interested_reason_id) {
            $input['not_interested_reason_id'] = $request->not_interested_reason_id;
        }

        $input['subscribed'] = 0;
        $input['active'] = 1;
        if (isset($PocomosLeadReminder)) {
            $input['lead_reminder_id'] = $PocomosLeadReminder->id;
        }
        if ($request->initial_job_note) {
            $input['initial_job_note_id'] = $PocomosNoteinitial->id;
        }

        if ($PocomosLead) {
            $PocomosLead->update($input);
        } else {
            $PocomosLead =  PocomosLead::create($input);
        }

        if ($request->permanent_note) {
            $notes_1 = [];
            $notes_1['user_id'] = $request->user_id;
            $notes_1['summary'] = $request->permanent_note;
            $notes_1['body'] = "";
            $notes_1['interaction_type'] = 'Other';
            $notes_1['active'] = 1;

            $PocomosLeadNote = PocomosLeadNote::where('lead_id', $PocomosLead->id)
                ->first();

            if (($PocomosLeadNote)) {
                $PocomosNotepermanent = PocomosNote::findOrFail($PocomosLeadNote->note_id);
                $PocomosNotepermanent->update($notes_1);
            } else {
                $PocomosNotepermanent = PocomosNote::create($notes_1);
                $leadNote['lead_id'] = $PocomosLead->id;
                $leadNote['note_id'] = $PocomosNotepermanent->id;
                $PocomosLeadNote = PocomosLeadNote::create($leadNote);
            }
        }

        return $this->sendResponse(true, 'Lead updated successfully.', $PocomosLead);
    }

    /**
     * API for add of reminder
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addreminder(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'note' => 'nullable',
            'reminder_date' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $lead = PocomosLead::where('id', $request->lead_id)->first();
        if (!$lead) {
            return $this->sendResponse(false, 'Lead not found.');
        }

        $pocomos_lead_reminders = [];
        $pocomos_lead_reminders['note'] = $request->note;
        $pocomos_lead_reminders['reminder_date'] = $request->reminder_date;
        $PocomosLeadReminder = PocomosLeadReminder::create($pocomos_lead_reminders);

        $lead->lead_reminder_id = $PocomosLeadReminder->id;
        $lead->save();

        return $this->sendResponse(true, 'Reminder Updated successfully.', $lead);
    }

    /**
     * API for Update of Leads status
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function UpdateStatus(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'status' => 'nullable',
            'reason' => 'nullable'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $lead = PocomosLead::where('id', $request->lead_id)->first();
        if (!$lead) {
            return $this->sendResponse(false, 'Lead not found.');
        }
        $lead->status = $request->status;
        if ($request->status == "Not Interested") {
            $lead->not_interested_reason_id = $request->reason;
        }
        $lead->save();
        return $this->sendResponse(true, 'Lead Updated successfully.', $lead);
    }

    /**
     * API for Edit billing info of Leads
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function EditBillingInfo(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'first_name' => 'nullable',
            'last_name' => 'nullable',
            'email' => 'nullable|email',
            'street' => 'nullable',
            'city' => 'nullable',
            'postal_code' => 'nullable',
            'region_id' => 'nullable|exists:orkestra_countries_regions,id',
            'suite' => 'nullable',
            'map_code' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $Lead = PocomosLead::findOrFail($request->lead_id);

        if (!$Lead) {
            return $this->sendResponse(false, 'Lead not found.');
        }

        $PocomosAddress = PocomosAddress::findOrFail($Lead->contact_address_id);
        $PocomosLeadQuote = PocomosLeadQuote::findOrFail($Lead->quote_id);

        $detail['first_name'] = $request['first_name'] ?? $Lead['first_name'];
        $detail['last_name'] = $request['last_name'] ?? $Lead['last_name'];
        $detail['email'] = $request['email'] ?? $Lead['email'];
        $Lead->update($detail);

        $code['map_code'] = $request['map_code'] ?? $PocomosLeadQuote['map_code'];
        $PocomosLeadQuote->update($code);

        $address['suite'] =  $request['suite'] ?? $PocomosAddress['suite'];
        $address['street'] =  $request['street'] ?? $PocomosAddress['street'];
        $address['postal_code'] = $request['postal_code'] ?? $PocomosAddress['postal_code'];
        $address['city'] = $request['city'] ?? $PocomosAddress['city'];
        $address['region_id'] = $request['region_id'] ?? $PocomosAddress['region_id'];
        $PocomosAddress->update($address);

        return $this->sendResponse(true, 'Lead Updated successfully.', $Lead);
    }

    /**
     * API for Edit ServiceInfo info of Leads
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function EditServiceInfo(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'service_type_id' => 'nullable|exists:pocomos_pest_contract_service_types,id',
            'service_frequency' => 'nullable',//|in:Weekly,Bi-weekly,Tri-weekly,Monthly,Bi-monthly,Twice Per Month,Every Six Weeks,Quarterly,Semi-annually,Annually,Tri-Annually,One-Time,Custom,Custom (Manual)
            'found_by_type_id' => 'nullable|exists:pocomos_marketing_types,id',
            'contract_type' => 'nullable|exists:pocomos_pest_agreements,id',
            'regular_initial_price' => 'nullable',
            'initial_discount' => 'nullable',
            'initial_price' => 'nullable',
            'recurring_price' => 'nullable',
            'initial_duration' => 'nullable',
            'specific_recurring_schedule' => 'required|boolean',
            'week_of_the_month' =>  'nullable|in:First,Second,Fourth,Third',
            'day_of_the_week' =>  'nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'preferred_time' => 'nullable',
            'salesperson_id' => 'required|exists:pocomos_salespeople,id',
            'technician_id' => 'nullable|exists:pocomos_technicians,id',
            'make_tech_preferred' => 'required|boolean',
            'pests' => 'nullable|array|exists:pocomos_pests,id',
            'county_id' => 'nullable|exists:pocomos_counties,id',
            'specialtyPests' => 'nullable|array|exists:pocomos_pests,id',
            'tags' => 'nullable|array|exists:pocomos_tags,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosLead = PocomosLead::findOrFail($request->lead_id);

        if (!$PocomosLead) {
            return $this->sendResponse(false, 'Lead not found.');
        }

        if ($PocomosLead->quote_id) {
            $PocomosLeadQuote = PocomosLeadQuote::findOrFail($PocomosLead->quote_id);
        }

        $input_leads_quotes = $request->only('service_type_id', 'service_frequency', 'found_by_type_id', 'regular_initial_price', 'initial_discount', 'initial_price', 'recurring_price', 'specific_recurring_schedule', 'week_of_the_month', 'day_of_the_week', 'preferred_time', 'salesperson_id', 'technician_id', 'make_tech_preferred', 'county_id');

        $pestAgreement = PocomosPestAgreement::findOrFail($request->contract_type);
        $input_leads_quotes['pest_agreement_id'] = $request->contract_type;
        $input_leads_quotes['agreement_id'] = $pestAgreement->agreement_id;
        
        if ($PocomosLeadQuote) {
            $PocomosLeadQuote->update($input_leads_quotes);
        } else {
            $PocomosLeadQuote = PocomosLeadQuote::create($input_leads_quotes);
        }

        $PocomosPestOfficeSetting = PocomosPestOfficeSetting::where('office_id', $request->office_id)->first();
        $code['initial_duration'] = $request['initial_duration'] ?? $PocomosPestOfficeSetting['initial_duration'];
        $PocomosPestOfficeSetting->update($code);

        if (isset($request->tags)) {
            $PocomosLeadQuoteTag = PocomosLeadQuoteTag::where('lead_quote_id', $PocomosLeadQuote->id)
                ->delete();

            foreach ($request->tags as $tag) {
                $input_details['tag_id'] = $tag;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuoteTag::create($input_details);
            }
        }

        if (isset($request->pests)) {
            $PocomosLeadQuotPest = PocomosLeadQuotPest::where('lead_quote_id', $PocomosLeadQuote->id)->delete();

            foreach ($request->pests as $pest) {
                $input_details['pest_id'] = $pest;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuotPest::create($input_details);
            }
        }

        if (isset($request->specialtyPests)) {
            $PocomosLeadQuoteSpecialtyPest = PocomosLeadQuoteSpecialtyPest::where('lead_quote_id', $PocomosLeadQuote->id)
                ->delete();

            foreach ($request->specialtyPests as $special_pest) {
                $input_details['pest_id'] = $special_pest;
                $input_details['lead_quote_id'] = $PocomosLeadQuote->id;
                $success = PocomosLeadQuoteSpecialtyPest::create($input_details);
            }
        }

        return $this->sendResponse(true, 'Lead Updated successfully.', $PocomosLeadQuote);
    }

    /**
     * API for SendEmail of Leads
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function SendEmail(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'subject' => 'nullable',
            'body' => 'nullable',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $leads = PocomosLead::findOrFail($request->lead_id);
        $body = $this->emailTemplate($request->body);
        $leads->notify(new SendEmail($request->subject, $body));

        $office = PocomosCompanyOffice::findOrFail($request->office_id);
        $office_email = unserialize($office->email);
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->whereUserId(auth()->user()->id)->first();
        $from = $this->getOfficeEmail($request->office_id);

        $email_input['office_id'] = $office->id;
        $email_input['office_user_id'] = $officeUser->id;
        $email_input['customer_sales_profile_id'] = null;
        $email_input['type'] = 'Lead email';
        $email_input['body'] = $body;
        $email_input['subject'] = $request->subject;
        $email_input['reply_to'] = $from;
        $email_input['reply_to_name'] = $office->name ?? '';
        $email_input['sender'] = $from;
        $email_input['sender_name'] = $office->name ?? '';
        $email_input['active'] = true;
        $email_input['lead_id'] = $request->lead_id;
        $email = PocomosEmail::create($email_input);

        $input['email_id'] = $email->id;
        $input['recipient'] = $leads->email;
        $input['recipient_name'] = $leads->first_name . ' ' . $leads->last_name;
        $input['date_status_changed'] = date('Y-m-d H:i:s');
        $input['status'] = 'Delivered';
        $input['external_id'] = '';
        $input['active'] = true;
        $input['office_user_id'] = $officeUser->id;
        PocomosEmailMessage::create($input);
        
        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Email sended']), $body);
    }
    /**
     * API for Send SMS
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function SendSMS(Request $request)
    {
        $v = validator($request->all(), [
            'message' => 'required',
            'lead_number' => 'required',
            'office_id' => 'required',
            'user_id' => 'required|exists:pocomos_company_office_users,id',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $accountSid = env('TWILIO_ACCOUNT_SID');
        $authToken  = env('TWILIO_AUTH_TOKEN');
        // dd($appSid);
        $client = new Client($accountSid, $authToken);
        $find_office_phone_number = PocomosOfficeSetting::where('office_id', $request->office_id)->with('phone_details')->firstOrFail();
        if (!$find_office_phone_number) {
            return $this->sendResponse(false, 'Unable to find the sender number');
        }
        $find_lead_number_id = PocomosPhoneNumber::where('number', $request->lead_number)->firstOrFail();
        $office = PocomosCompanyOffice::findOrFail($request->office_id);

        DB::beginTransaction();
        $input = [];
        $input['office_id'] = $request->office_id;
        $input['phone_id'] = $find_lead_number_id->id;
        $input['message_part'] = $request->message;
        $input['sender_phone_id'] = $office->coontact_address->phone_id;
        $input['office_user_id'] = $request->user_id;
        $input['inbound'] = '0';
        $input['answered'] = '0';
        $input['seen'] = '0';
        $input['active'] = '1';
        try {
            // Use the client to do fun stuff like send text messages!
            $client->messages->create(
                // the number you'd like to send the message to
                $request->lead_number,
                array(
                    // A Twilio phone number you purchased at twilio.com/console
                    'from' => config('constants.TWILLIO_NUMBER'),
                    // the body of the text message you'd like to send
                    'body' => $request->message
                )
            );
            PocomosSmsUsage::create($input);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }
        return $this->sendResponse(true, 'Message sended successfully.');
    }

    /**
     * API for SMS History
   .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function SmsHistory(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'lead_id' => 'required|exists:pocomos_leads,id',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $find_lead_number_id = PocomosLead::where('id', $request->lead_id)->with('addresses')->first();

        $find_messages = PocomosSmsUsage::where('office_id', $request->office_id)->whereIn('phone_id', [$find_lead_number_id->addresses->phone_id, $find_lead_number_id->addresses->alt_phone_id])->orderBy('date_created', 'DESC')->get();
        $find_messages->map(function ($message) {
            $find_user_data = PocomosAddress::where('phone_id', $message->phone_id)->orWhere('alt_phone_id', $message->phone_id)->with('address_details')->first();
            $message->user_data = $find_user_data;
        });
        return $this->sendResponse(true, 'Message sended successfully.', $find_messages);
    }

    /**
     * API for SMS Webhook
   .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function receiveSms(Request $request)
    {
        // return auth()->user();
        $v = validator($request->all(), [
            'Body' => 'required',
            'From' => 'required',
            'To' => 'required'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $messageBody = $request->Body;
        $fromPhoneNumber = addslashes($request->From);
        $toPhoneNumber = addslashes($request->To);

        $input['sender_phone'] = $fromPhoneNumber;
        $input['receiver_phone'] = $toPhoneNumber;
        $input['message'] = $messageBody;

        try {
            // return 111;
            $receivingNumber = substr($toPhoneNumber, 2); // remove code "+1"

            $officeNumber = PocomosPhoneNumber::whereNumber($receivingNumber)->whereActive(true)->first();

            if (!$officeNumber) {
                throw new \Exception(__('strings.message', ['message' => 'Unable to find Receiver Phone entity.']));
            }
            // return 11117;

            // if there's a customer has been contacted recently, get his phone id
            $customerPhoneNumber = substr($fromPhoneNumber, 2);
            $customerPhoneId = $this->getLastContactedPhone($customerPhoneNumber);

            if ($customerPhoneId) { // if there's a customer has been contacted recently
                $filterData = $customerPhoneId[0]->phone_id;
                $fromNumbers = PocomosPhoneNumber::whereId($filterData)->orderByDesc('active')->get();
            } else { // get all the customer with the given phone numbers
                // return 11;
                $filterData =  $customerPhoneNumber;
                $fromNumbers = PocomosPhoneNumber::whereNumber($filterData)->orderByDesc('active')->get();
            }

            if (count($fromNumbers)) {
                // return $fromNumbers;
                foreach ($fromNumbers as $fromNumber) {
                    // return $fromNumber;


                    DB::select(DB::raw(('UPDATE `pocomos_sms_usage` SET answered = 1, seen = 1
                                            WHERE phone_id = ' . $fromNumber->id . '')));


                    $office = $this->getOfficeByPhoneId($fromNumber);

                    $officeConfiguration = PocomosOfficeSetting::whereOfficeId($office->id)->first();
                    // dd(11);
                    $this->createMessage_officeSmsController(
                        $office,
                        $officeNumber,
                        $messageBody,
                        $fromNumber,
                        /* inbound */
                        true,
                        /* fromNumber */
                        $fromPhoneNumber
                    );

                    if ($officeConfiguration->vantage_dnc_registry == 1 && $messageBody == 'STOP') {
                        $postArray = [
                            'Phone' => $fromPhoneNumber,
                            'UserAccountUID' => $officeConfiguration->vantage_dnc_uid,
                            'UserName' => $officeConfiguration->vantage_dnc_username
                        ];

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => "https://restapi.sales-exec.net/api/DoNotContact",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "GET",
                            CURLOPT_POSTFIELDS => \GuzzleHttp\json_encode($postArray),
                            CURLOPT_HTTPHEADER => array(
                                "Content-Type: application/json",
                                "Accept: application/json",
                                "AuthToken:  8f5dc9a9-0cd7-49fb-a1ab-b4c1e2132e6b"
                            ),
                        ));

                        $response = curl_exec($curl);

                        curl_close($curl);
                        echo $response;
                    }
                }
            } else {
                // return 119;

                $office = $this->getOfficeBySenderPhone($officeNumber);

                if (!$office) {
                    throw new \Exception(__('strings.message', ['message' => "Unable to find Office entity for $toPhoneNumber number."]));
                }

                $this->createMessage_officeSmsController(
                    $office,
                    $officeNumber,
                    $messageBody,
                    /* from */
                    null,
                    /* inbound */
                    true,
                    /* fromNumber */
                    $fromPhoneNumber
                );
            }
        } catch (\Exception $e) {
            $input['error'] = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
        }

        $messageLog = PocomosSmsReceivedMessageLog::create($input);

        return $this->sendResponse(true, 'Message received successfully.', $messageLog);
    }

    public function createNote(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'note' => 'required',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $note = [];
        $note['user_id'] = auth()->user()->id;
        $note['summary'] = $request->note;
        $note['interaction_type'] = 'Other';
        $note['body'] = '';
        $note['active'] = true;
        $PocomosNote = PocomosNote::create($note);
        $leadNote = [];
        $leadNote['lead_id'] = $request->lead_id;
        $leadNote['note_id'] = $PocomosNote->id;
        $PocomosLeadNote = PocomosLeadNote::create($leadNote);

        return $this->sendResponse(true, 'Note added successfully.', $PocomosNote);
    }

    public function getNote(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $findLead = PocomosLead::findOrFail($request->lead_id);
        if (!$findLead) {
            return $this->sendResponse(false, 'Lead Not Found.');
        }

        $leadnote = PocomosLeadNote::where('lead_id', $request->lead_id)->pluck('note_id')->toArray();

        $allNote = PocomosNote::with(['user_details' => function ($query) {
            $query->select('id', 'username', 'first_name', 'last_name');
        }])->whereIn('id', $leadnote);

        if ($request->search) {
            $search = $request->search;
            $allNote->where(function ($query) use ($search) {
                $query->where('interaction_type', 'like', '%' . $search . '%')
                    ->orWhere('summary', 'like', '%' . $search . '%')
                    ->orWhere('date_modified', 'like', '%' . $search . '%')
                    ->orWhere('date_created', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $allNote->count();
        $allNote->skip($perPage * ($page - 1))->take($perPage);

        $allNote = $allNote->orderBy('id', 'desc')->get();

        return $this->sendResponse(true, 'List', [
            'allNote' => $allNote,
            'count' => $count,
        ]);
    }

    public function editNote(Request $request)
    {
        $v = validator($request->all(), [
            'note_id' => 'required|exists:pocomos_notes,id',
            'note' => 'required'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $PocomosNote = PocomosNote::findOrFail($request->note_id);
        if (!$PocomosNote) {
            return $this->sendResponse(false, 'Note Not Found');
        }
        $PocomosNote['summary'] = $request->note;
        $PocomosNote->save();
        return $this->sendResponse(true, 'Note edited successfully.', $PocomosNote);
    }

    public function deleteNote(Request $request)
    {
        $PocomosNote = PocomosNote::findOrFail($request->note_id);
        if (!$PocomosNote) {
            return $this->sendResponse(false, 'Note Not Found');
        }
        $deleteNote = $PocomosNote->delete();
        return $this->sendResponse(true, 'Deleted Note Successfully', $deleteNote);
    }


    public function getPhoneNumber(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $findLead = PocomosLead::findOrFail($request->lead_id);
        if (!$findLead) {
            return $this->sendResponse(false, 'Lead Not Found.');
        }

        $PocomosAddress = PocomosAddress::where('id', $findLead->contact_address_id)->first();

        $phoner_numbers = PocomosPhoneNumber::where('active', 1)->where('id', $PocomosAddress->phone_id);

        $phone = PocomosPhoneNumber::where('active', 1)->where('id', $PocomosAddress->alt_phone_id);

        if ($request->search) {
            $search = $request->search;

            $phoner_numbers->where(function ($query) use ($search) {
                $query->where('alias', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('number', 'like', '%' . $search . '%');
            });

            $phone->where(function ($query) use ($search) {
                $query->where('alias', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('number', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];

        //$count = $phoner_numbers->count();

        $phoner_numbers->skip($perPage * ($page - 1))->take($perPage);
        $phone->skip($perPage * ($page - 1))->take($perPage);

        $phoner_numbers = $phoner_numbers->get();
        $phone = $phone->get();

        return $this->sendResponse(true, 'List', [
            'primary_phone' => $phoner_numbers,
            'alt_phoner' => $phone,
            //'count' => $count,
        ]);
    }


    public function addPhoneNumber(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id',
            'alias' => 'nullable',
            'number' => 'required',
            'type' => 'required|in:Mobile,Home,Fax,Office',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $findLead = PocomosLead::findOrFail($request->lead_id);
        if (!$findLead) {
            return $this->sendResponse(false, 'Lead Not Found.');
        }
        $phone = [];
        $phone['alias'] = $request->alias ?? '';
        $phone['number'] = $request->number;
        $phone['type'] = $request->type;
        $phone['active'] = true;
        $phone = PocomosPhoneNumber::create($phone);

        $findAddress = PocomosAddress::where('id', $findLead->contact_address_id)->first();
        if ($findAddress['phone_id'] == null) {
            $findAddress['phone_id'] = $phone->id;
        } else {
            $findAddress['alt_phone_id'] = $phone->id;
        }
        $findAddress->save();
        return $this->sendResponse(true, 'Number Added Successfully.', $phone);
    }

    public function editPhoneNumber(Request $request)
    {
        $v = validator($request->all(), [
            'phone_id' => 'required|exists:pocomos_phone_numbers,id',
            'alias' => 'nullable',
            'number' => 'required',
            'type' => 'required|in:Mobile,Home,Fax,Office',
            'active' => 'required'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $phone = PocomosPhoneNumber::findOrFail($request->phone_id);
        if (!$phone) {
            return $this->sendResponse(false, 'Phone id Not Found.');
        }
        $phone['alias'] = $request->alias ?? $phone->alias;
        $phone['number'] = $request->number;
        $phone['type'] = $request->type;
        $phone['active'] = $request->active;
        $phone->save();
        return $this->sendResponse(true, __('strings.update', ['name' => 'Phone number']), $phone);
    }


    public function deletePhoneNumber(Request $request)
    {
        $phone_id = PocomosAddress::where('phone_id', $request->number_id)->select('phone_id')->first();
        $alt_phone_id = PocomosAddress::where('alt_phone_id', $request->number_id)->select('alt_phone_id')->first();

        if ((!$phone_id) && (!$alt_phone_id)) {
            return $this->sendResponse(false, 'Phone id Not Found.');
        }

        if ($phone_id) {
            $query =  DB::table('pocomos_addresses')->where('phone_id', '=', $request->number_id)->update(
                ['phone_id' => null]
            );
        }

        if ($alt_phone_id) {
            $query =  DB::table('pocomos_addresses')->where('alt_phone_id', '=', $request->number_id)->update(
                ['alt_phone_id' => null]
            );
        }
        return $this->sendResponse(true, __('strings.delete', ['name' => 'Phone number']));
    }


    /**
     * API for Delete Lead .
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function leaddelete($id)
    {
        $PocomosLead = PocomosLead::findOrFail($id);
        if (!$PocomosLead) {
            return $this->sendResponse(false, 'Unable to locate Lead.');
        }

        $PocomosLead->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Lead deleted successfully.');
    }
    // API for advance search
    public function leadDataAction(Request $request)
    {
        $v = validator($request->all(), [
            'first_name' => 'nullable',
            'last_name' => 'nullable',
            'company_name' => 'nullable',
            'street_address' => 'nullable',
            'city' => 'nullable',
            'state' => 'nullable',
            'zip' => 'nullable',
            'lead_id' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable',
            'lead_creation_sdate' => 'nullable',
            'lead_creation_edate' => 'nullable',
            'last_person_to_modify' => 'nullable',
            'search_all_branch' => 'nullable',
            'contract_type' => 'nullable',
            'service_type' => 'nullable',
            'service_frequency' => 'nullable',
            'marketing_type' => 'nullable',
            'initical_price' => 'nullable',
            'recurring_price' => 'nullable',
            'sales_person' => 'nullable',
            'technician' => 'nullable',
            'pest' => 'nullable',
            'specialty_pest' => 'nullable',
            'tag' => 'nullable',
            'tag_check' => 'nullable',
            'no_preferred_week' => 'nullable',
            'preferred_week' => 'nullable',
            'preferred_day' => 'nullable',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            'search' => 'nullable'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $query = PocomosLead::query();
        if ($request->first_name) {
            $query = (clone ($query))->where('first_name', 'like', '%' . $request->first_name . '%');
        }
        if ($request->last_name) {
            $query = (clone ($query))->where('last_name', 'like', '%' . $request->last_name . '%');
        }
        if ($request->company_name) {
            $query = (clone ($query))->where('company_name', 'like', '%' . $request->company_name . '%');
        }
        if ($request->street_address || $request->city || $request->state || $request->zip) {
            $query = (clone ($query))->Join('pocomos_addresses', 'pocomos_leads.contact_address_id', '=', 'pocomos_addresses.id');
            if ($request->street_address) {
                $query = $query->where('pocomos_addresses.street_address', 'like', '%' . $request->street_address . '%');
            }
            if ($request->city) {
                $query = $query->where('pocomos_addresses.city', 'like', '%' . $request->city . '%');
            }
            if ($request->state) {
                $query = $query->where('pocomos_addresses.region_id', 'like', '%' . $request->state . '%');
            }
            if ($request->zip) {
                $query = $query->where('pocomos_addresses.postal_code', 'like', '%' . $request->zip . '%');
            }
        }
        if ($request->lead_id) {
            $query = (clone ($query))->where('id', $request->lead_id);
        }
        if ($request->phone) {
            $query = (clone ($query))->Join('pocomos_addresses', 'pocomos_leads.contact_address_id', '=', 'pocomos_addresses.id')->Join('pocomos_phone_numbers', 'pocomos_addresses.phone_id', '=', 'pocomos_phone_numbers.id')->where('pocomos_phone_numbers.number', 'like', '%' . $request->phone . '%');
        }
        if ($request->email) {
            $query = (clone ($query))->where('email', $request->email);
        }
        if ($request->lead_creation_sdate && $request->lead_creation_edate) {
            $query = (clone ($query))->whereBetween('date_created', [$request->lead_creation_sdate, $request->lead_creation_edate]);
        }
        if ($request->lead_creation_sdate && $request->lead_creation_edate == null) {
            $query = (clone ($query))->where('date_created', '>=', $request->lead_creation_sdate);
        }
        if ($request->contract_type || $request->service_type || $request->service_frequency || $request->marketing_type || $request->initial_price || $request->recurring_price || $request->salesperson || $request->technician || $request->pest || $request->specialty_pest || $request->tag || $request->tag_check || $request->preferred_week || $request->preferred_day) {
            $query = (clone ($query))->Join('pocomos_lead_quotes', 'pocomos_leads.quote_id', '=', 'pocomos_lead_quotes.id');
            if ($request->contract_type) {
                $query = $query->where('pocomos_lead_quotes.pest_agreement_id', $request->contract_type);
            }
            if ($request->service_type) {
                $query = $query->where('pocomos_lead_quotes.service_type_id', $request->service_type);
            }
            if ($request->service_frequency) {
                $query = $query->where('pocomos_lead_quotes.service_frequency', $request->service_frequency);
            }
            if ($request->marketing_type) {
                $query = $query->where('pocomos_lead_quotes.found_by_type_id', $request->marketing_type);
            }
            if ($request->initical_price) {
                $query = $query->where('pocomos_lead_quotes.initial_price', $request->initical_price);
            }
            if ($request->recurring_price) {
                $query = $query->where('pocomos_lead_quotes.recurring_price', $request->recurring_price);
            }
            if ($request->sales_person) {
                $query = $query->where('pocomos_lead_quotes.salesperson_id', $request->sales_person);
            }
            if ($request->technician) {
                $query = $query->where('pocomos_lead_quotes.technician_id', $request->technician);
            }
            if ($request->preferred_week) {
                $query = $query->where('pocomos_lead_quotes.day_of_the_week', $request->preferred_week);
            }
            if ($request->preferred_day) {
                $query = $query->where('pocomos_lead_quotes.preferred_time', $request->preferred_day);
            }
            if ($request->pest) {
                $query = $query->Join('pocomos_lead_quotes_pests', 'pocomos_lead_quotes.id', '=', 'pocomos_lead_quotes_pests.lead_quote_id')->where('pocomos_lead_quotes_pests.pest_id', $request->pest);
            }
            if ($request->specialty_pest) {
                $query = $query->Join('pocomos_lead_quotes_specialty_pests', 'pocomos_lead_quotes.id', '=', 'pocomos_lead_quotes_specialty_pests.lead_quote_id')->where('pocomos_lead_quotes_specialty_pests.pest_id', $request->specialty_pest);
            }
            if ($request->tag && $request->tag_check == 'isChecked') {
                $query = $query->Join('pocomos_lead_quotes_tags', 'pocomos_lead_quotes.id', '=', 'pocomos_lead_quotes_tags.lead_quote_id')->where('pocomos_lead_quotes_tags.tag_id', $request->tag);
            }
            if ($request->tag && $request->tag_check == 'isNotChecked') {
                $query = $query->Join('pocomos_lead_quotes_tags', 'pocomos_lead_quotes.id', '=', 'pocomos_lead_quotes_tags.lead_quote_id')->where('pocomos_lead_quotes_tags.tag_id', '!=', $request->tag);
            }
        }

        /**For search filters */
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
                if (PocomosLead::whereIn('contact_address_id', $addrIds)->count()) {
                    $numAddrIds = $numAddrIds->whereIn('id', $addrIds);
                }
                $numAddrIds = $numAddrIds->whereIn('phone_id', $numIds)->pluck('id')->toArray();

                $numAddrIds = array_unique($numAddrIds);
                $addrIds = array_merge($addrIds, $numAddrIds);
                $addrIds = array_unique($addrIds);
            }

            if (PocomosLead::whereIn('contact_address_id', $addrIds)->count()) {
                $query->whereIn('contact_address_id', $addrIds);
            } else {
                $qtIdsTmp = DB::select(DB::raw("SELECT plq.id
                FROM pocomos_lead_quotes AS plq
                JOIN pocomos_salespeople AS psp ON plq.salesperson_id = psp.id
                JOIN pocomos_company_office_users AS pcou ON psp.user_id = pcou.id
                JOIN orkestra_users AS ou ON pcou.user_id = ou.id
                WHERE (ou.first_name like '%$search%' OR ou.last_name like '%$search%')"));

                $qtIds = array_map(function ($value) {
                    return $value->id;
                }, $qtIdsTmp);

                $mapQtIds = PocomosLeadQuote::where(function ($query) use ($search) {
                    $query->where('map_code', 'like', '%' . $search . '%');
                })->pluck('id')->toArray();

                $qtIds = array_merge($qtIds, $mapQtIds);
                $qtIds = array_unique($qtIds);

                $ntLeadIdsTmp = DB::select(DB::raw("SELECT pln.lead_id
                FROM pocomos_leads_notes AS pln
                JOIN pocomos_notes AS pn ON pln.note_id = pn.id
                WHERE (pn.summary like '%$search%')"));

                $ntLeadIds = array_map(function ($value) {
                    return $value->lead_id;
                }, $ntLeadIdsTmp);

                $query->where(function ($query) use ($search, $qtIds, $ntLeadIds) {
                    $query->whereIn('id', $ntLeadIds);
                    if ($qtIds) {
                        $query->orWhereIn('quote_id', $qtIds);
                    }
                    $query->orWhere('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('status', 'like', '%' . $search . '%')
                        ->orWhere('date_created', 'like', '%' . $search . '%');
                });
            }
        }
        /**End search filters */

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $query->count();
        $leadIds = $query->pluck('pocomos_leads.id')->toArray();

        $query->skip($perPage * ($page - 1))->take($perPage);
        /**End */

        $leads = $query->with(
            'addresses',
            'quote_id_detail.service_type',
            'quote_id_detail.found_by_type_detail',
            'quote_id_detail.county_detail',
            'quote_id_detail.sales_person_detail',
            'quote_id_detail.pest_agreement_detail.agreement_detail:id,name',
            'quote_id_detail.tags.tag_detail',
            'quote_id_detail.pests.pest_detail',
            'quote_id_detail.specialty_pests.specialty_pest_detail',
            'permanent_note.note_detail',
            'quote_id_detail.technician_detail.user_detail.user_details:id,username,first_name,last_name',
            'not_interested_reason',
            'initial_job',
            'lead_reminder'
        )->orderBy('pocomos_leads.id', 'desc')->get();

        // $all_leads = collect($query);
        // $leadsCollection = $all_leads->unique('id')->values();
        // $leadsCollection->all();

        $data = [
            'leads' => $leads,
            'lead_ids' => $leadIds,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Leads']), $data);
    }

    /* API for lead Report for knowcking List */
    public function leadKnockingReportBkp(Request $request)
    {
        $v = validator($request->all(), [
            'branches' => 'array',
            'teams' => 'array',
            'salespeople' => 'array',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'office_id' => 'required',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $page = $request->page;
        $perPage = $request->perPage;
        $search = $request->search;
        $leadAdmin = true;
        $salesPerson =  true;
        $office = $request->office_id;
        $leads = array();

        $branches = PocomosCompanyOffice::whereParentId($request->office_id)->select('id')->get()->toArray();
        $PocomosCompanyOfficeUser = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)
            ->select('id')->get()->toArray();

        $dateStart = $request->start_date;
        $dateEnd = $request->end_date;

        $brancheIds = array();
        foreach ($request->branches as $child) {
            $brancheIds[] = $child;
        }

        $salespeopleIds = array();
        foreach ($request->salespeople as $salesperson) {
            $salespeopleIds[] = $salesperson;
        }

        $teamsIds = array();
        foreach ($request->teams as $team) {
            $teamsIds[] = $team;
        }

        $salespeopleReasons = $this->getReasonCountsBySalesPeople(
            $dateStart,
            $dateEnd,
            $brancheIds,
            $teamsIds,
            $salespeopleIds,
            $page,
            $perPage,
            $search
        );

        // return $salespeopleReasons;

        $reasonLeads = $this->generateSalesPeopleLeadsArray($leads, $salespeopleReasons, 'reason');

        // return $reasonLeads;

        $salespeopleKnocks = $this->getKnockCountsBySalesPeople(
            $dateStart,
            $dateEnd,
            $brancheIds,
            $teamsIds,
            $salespeopleIds
        );

        // return $salespeopleKnocks;

        $knockLeads = $this->generateSalesPeopleLeadsArray($leads, $salespeopleKnocks, 'knock');

        // for dynamic columns (name)
        $reasons = PocomosLeadNotInterestedReason::whereIn('office_id', $brancheIds)->where('active', 1)->get();

        $data = [
            'reasonLeads' => $reasonLeads,
            'knockLeads' => $knockLeads,
            'reasons' => $reasons,
        ];

        $object = [$data];

        return $this->sendResponse(true, 'Report data', $object);
    }


    /* API for lead Report List */
    public function leadReport(Request $request)
    {
        $v = validator($request->all(), [
            'salespeople' => 'required|exists:pocomos_salespeople,id',
            'start_date' => 'date',
            'end_date' => 'date',
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $dateStart = new DateTime($request->start_date);
        $dateEnd = new DateTime($request->end_date);
        $dateStart->modify('midnight');
        $dateEnd->modify('+1 day midnight');
        $daysDiff = $dateStart->diff($dateEnd)->days;
        $previousDateStart = clone $dateStart;
        $previousDateStart->modify(sprintf('-%s days midnight', $daysDiff));

        $salespeople = $request->salespeople;
        $dateStart = $dateStart->format('Y-m-d');
        $dateEnd = $dateEnd->format('Y-m-d');
        $previousDateStart = $previousDateStart->format('Y-m-d');

        $valuePerDoor = PocomosReportSalespersonState::where('salesperson_id', $request->salespeople)->select('value_per_door')->get();
        
        $currentRange = DB::select(DB::raw('SELECT la.type as type, COUNT(*) as count
            FROM pocomos_lead_actions la
                JOIN pocomos_leads l ON la.lead_id = l.id
                JOIN pocomos_lead_quotes q ON l.quote_id = q.id
            WHERE q.salesperson_id =  ' . $salespeople . '
            AND la.date_created BETWEEN "'.$dateStart.'" AND "'.$dateEnd.'"
            GROUP BY la.type'));

        $previousRange = DB::select(DB::raw('SELECT la.type as type, COUNT(*) as count
            FROM pocomos_lead_actions la
                JOIN pocomos_leads l ON la.lead_id = l.id
                JOIN pocomos_lead_quotes q ON l.quote_id = q.id
            WHERE q.salesperson_id =  ' . $salespeople . '
            AND la.date_created BETWEEN "'.$previousDateStart.'" AND "'.$dateEnd.'"
            GROUP BY la.type'));

        $data = DB::select(DB::raw('SELECT UNIX_TIMESTAMP(DATE(la.date_created)) as date, la.type as type, COUNT(*) as count
            FROM pocomos_lead_actions la
                JOIN pocomos_leads l ON la.lead_id = l.id
                JOIN pocomos_lead_quotes q ON l.quote_id = q.id
            WHERE q.salesperson_id = ' . $salespeople . '
            AND la.date_created BETWEEN "'.$dateStart.'" AND "'.$dateEnd.'"
            GROUP BY date, la.type'));

        $transformedData = array();

        foreach ($data as $datum) {
            $datum = (array)$datum;
            if (!isset($transformedData[$datum['date']])) {
                $transformedData[$datum['date']] = $this->createEmptyTransformedArray();
            }

            $this->transformDatum($transformedData[$datum['date']], $datum);
        }

        foreach ($transformedData as $date => $datum) {
            $datum = (array)$datum;
            $transformedData[$date] = $this->performAdditionalCalculations($datum);
        }

        $transformedCurrent = $this->createEmptyTransformedArray();
        $transformedPrevious = $this->createEmptyTransformedArray();

        foreach ($currentRange as $datum) {
            $datum = (array)$datum;
            $transformedCurrent[$datum['type'] . 's'] = $this->transformDatum($transformedCurrent, $datum);
        }

        $transformedCurrent = $this->performAdditionalCalculations($transformedCurrent);

        foreach ($previousRange as $datum) {
            $datum = (array)$datum;
            $transformedPrevious[$datum['type'] . 's'] = $this->transformDatum($transformedPrevious, $datum);
        }

        $transformedPrevious = $this->performAdditionalCalculations($transformedPrevious);

        $data = [
            'data' => $transformedData,
            'current' => $transformedCurrent,
            'previous' => $transformedPrevious,
            'value_per_door' => $valuePerDoor,
        ];

        /*
        //percantage logic for frontend

        var knocksChange = (previous.knocks <= 0) ? current.knocks : current.knocks / previous.knocks - 1;
        var talksChange = (previous.talks <= 0) ? current.talks : current.talks / previous.talks - 1;
        var leadsChange = (previous.leads <= 0) ? current.leads : current.leads / previous.leads - 1;
        var salesChange = (previous.sales <= 0) ? current.sales : current.sales / previous.sales - 1;

        _$knocksBadge.text((knocksChange > 0 ? '+' : '') + (knocksChange * 100).toFixed(0) + '%');
        _$talksBadge.text((talksChange > 0 ? '+' : '') + (talksChange * 100).toFixed(0) + '%');
        _$leadsBadge.text((leadsChange > 0 ? '+' : '') + (leadsChange * 100).toFixed(0) + '%');
        _$salesBadge.text((salesChange > 0 ? '+' : '') + (salesChange * 100).toFixed(0) + '%');
        */

        return $this->sendResponse(true, 'Report data', $data);
    }


    // Last person to modify api
    public function lastPersonToModify($id)
    {
        $company_user = PocomosCompanyOfficeUser::where('office_id', $id)->Where('active', '1')->pluck('user_id')->toArray();

        // $find_role_id = OrkestraGroup::where('role','ROLE_CUSTOMER')->pluck('id')->first();

        $user = OrkestraUser::whereIn('id', $company_user)->where('active', '1')->get();

        return $this->sendResponse(true, 'Last person to modify', $user);
    }

    /**
     * Get lead map details.
     *
     * @param Request $request
     * @return array
     */
    public function leadMapDetails(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sales_areas = PocomosSalesArea::query();

        //CONSTANTS BASE MANAGE IS TEMPRORY BECAUSE NOW NOT IMPLETEMENTED LOGIN WILL UPDATE IN FEATURE ONCE LOGIN WILL DONE
        if (config('constants.ROLE_OWNER')) {
            return $sales_areas->whereOfficeId($request->office_id)->whereActive(true)->whereEnabled(true)->get();
        }

        $office_person = PocomosCompanyOfficeUser::where('office_id', $request->office_id)->whereActive(true)->firstOrFail();
        $office_user_profile = PocomosCompanyOfficeUserProfile::whereUserId($office_person->user_id)->firstOrFail();
        $salesperson_profile = PocomosSalespersonProfile::where('office_user_profile_id', $office_user_profile->id)->firstOrFail();
        $sales_person = PocomosSalesPeople::whereUserId($office_person->id)->whereActive(true)->firstOrFail();
        $office = PocomosCompanyOffice::findOrFail($request->office_id);

        $memberships = $this->findOneBySalespersonMembership($office_person->id);

        if ($sales_person) {
            if (isset($memberships[0]) && $memberships[0]->team_id) {
                $team = PocomosTeam::findOrFail($memberships[0]->team_id);
                $data = $this->getUserAssignedSalesAreas($sales_person, $office, $team);
            } else {
                $data = $this->getUserAssignedSalesAreas($sales_person, $office);
            }
        }
        return $this->sendResponse(true, __('strings.list', ['name' => 'Map areas']), $data);
    }

    /**
     * advanced_search_send_form_letter
     *
     * @param Request $request
     */
    public function sendLeadFormLetterAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'form_letter_id' => 'required|exists:pocomos_form_letters,id',
            'leads' => 'required|array',
            'leads.*' => 'exists:pocomos_leads,id',
            'current_office_user_id' => 'required|exists:pocomos_company_office_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $leadIds = $request->leads;

        if (count($leadIds) > 500) {
            $lead_chunk = array_chunk($leadIds, 500);
            foreach ($lead_chunk as $leads) {
                $args = array_merge(array(
                    'officeId' => $request->office_id,
                    'letterId' => $request->form_letter_id,
                    'leadIds' => $leads,
                    'officeUserId' => $request->current_office_user_id
                ), $request->all());

                SendMassEmailLeadJob::dispatch($args);
            }
        } else {
            $args = array_merge(array(
                'officeId' => $request->office_id,
                'letterId' => $request->form_letter_id,
                'leadIds' => $leadIds,
                'officeUserId' => $request->current_office_user_id
            ), $request->all());

            SendMassEmailLeadJob::dispatch($args);
        }

        return $this->sendResponse(true, __('strings.message', ['message' => 'The form letters will be sent shortly.']));
    }

    /**Send lead sms */
    public function sendLeadFormSmsAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'form_letter_id' => 'required|exists:pocomos_sms_form_letters,id',
            'leads' => 'required|array',
            'leads.*' => 'exists:pocomos_leads,id',
            'current_office_user_id' => 'required|exists:pocomos_company_office_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }


        try {
            DB::beginTransaction();

            $leadIds = $request->request->get('leads', []);

            $args = array_merge(array(
                'officeId' => $request->office_id,
                'letterId' => $request->form_letter_id,
                'leadIds' => $leadIds,
                'officeUserId' => $request->current_office_user_id
            ), $request->all());

            SendMassSmsLeadJob::dispatch($args);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }


        return $this->sendResponse(true, __('strings.message', ['message' => 'The form letters will be sent shortly.']));
    }

    /**
     * Create sales area
     * @param Request $request
     * @return Response
     */
    public function createSalesArea(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'color' => 'nullable',
            'enabled' => 'boolean',
            'blocked' => 'boolean',
            'teams' => 'array',
            'managers' => 'array',
            'salespeople' => 'array',
            'area_borders' => 'array',
            'referral' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $i = 0;
        $teams = array();
        $managers = array();

        try {
            DB::beginTransaction();
            $sales_area = new PocomosSalesArea();
            $sales_area->name = $request->name ?? null;
            $sales_area->color = $request->color ?? null;
            $sales_area->area_borders = json_encode($request->area_borders);
            $sales_area->office_id = $request->office_id ?? null;
            $sales_area->enabled = $request->enabled ?? null;
            $sales_area->blocked = $request->blocked ?? null;
            $sales_area->save();

            if ($request->managers) {
                foreach ($request->managers as $val) {
                    $managers[$i]['office_user_id'] = $val;
                    $managers[$i]['sales_area_id'] = $sales_area->id;
                    $i = $i + 1;
                }
                PocomosSalesAreaPivotManager::insert($managers);
            }

            if ($request->teams) {
                $i = 0;
                foreach ($request->teams as $val) {
                    $teams[$i]['team_id'] = $val;
                    $teams[$i]['sales_area_id'] = $sales_area->id;
                    $i = $i + 1;
                }
                PocomosSalesAreaPivotTeams::insert($teams);
            }

            if ($request->salespeople) {
                $i = 0;
                foreach ($request->salespeople as $val) {
                    $sales_people_data[$i]['sales_area_id'] = $sales_area->id;
                    $sales_people_data[$i]['salesperson_id'] = $val;
                    $i = $i + 1;
                }
                PocomosSalesAreaPivotSalesPerson::insert($sales_people_data);
            }

            DB::commit();
            $status = true;
            $message =  __('strings.create', ['name' => 'Sales Area']);
        } catch (\Exception $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }

        return $this->sendResponse($status, $message);
    }

    /**
     * Update sales area
     * @param Request $request
     * @return Response
     */
    public function updateSalesArea(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'color' => 'nullable',
            'enabled' => 'boolean',
            'blocked' => 'boolean',
            'teams' => 'array',
            'managers' => 'array',
            'salespeople' => 'array',
            'area_borders' => 'array',
            'referral' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $i = 0;
        $teams = array();
        $managers = array();

        try {
            DB::beginTransaction();
            $sales_area = PocomosSalesArea::findOrFail($id);

            $sales_area->name = $request->name ?? null;
            $sales_area->color = $request->color ?? null;
            $sales_area->area_borders = json_encode($request->area_borders);
            $sales_area->office_id = $request->office_id ?? null;
            $sales_area->enabled = $request->enabled ?? null;
            $sales_area->blocked = $request->blocked ?? null;
            $sales_area->save();

            if ($request->managers) {
                PocomosSalesAreaPivotManager::whereSalesAreaId($id)->delete();
                foreach ($request->managers as $val) {
                    $managers[$i]['office_user_id'] = $val;
                    $managers[$i]['sales_area_id'] = $sales_area->id;
                    $i = $i + 1;
                }
                PocomosSalesAreaPivotManager::insert($managers);
            }

            if ($request->teams) {
                $i = 0;
                PocomosSalesAreaPivotTeams::whereSalesAreaId($id)->delete();
                foreach ($request->teams as $val) {
                    $teams[$i]['team_id'] = $val;
                    $teams[$i]['sales_area_id'] = $sales_area->id;
                    $i = $i + 1;
                }
                PocomosSalesAreaPivotTeams::insert($teams);
            }

            if ($request->salespeople) {
                $i = 0;
                PocomosSalesAreaPivotSalesPerson::whereSalesAreaId($id)->delete();
                foreach ($request->salespeople as $val) {
                    $sales_people_data[$i]['sales_area_id'] = $sales_area->id;
                    $sales_people_data[$i]['salesperson_id'] = $val;
                    $i = $i + 1;
                }
                PocomosSalesAreaPivotSalesPerson::insert($sales_people_data);
            }

            DB::commit();
            $status = true;
            $message =  __('strings.update', ['name' => 'Sales Area']);
        } catch (\Exception $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }

        return $this->sendResponse($status, $message);
    }

    /**
     * Update sales ares status
     * @param Request $request
     */
    public function areaEnabledAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'sales_area_id' => 'required|exists:pocomos_sales_area,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sales_area = PocomosSalesArea::whereOfficeId($request->office_id)->whereActive(true)->findOrFail($request->sales_area_id);
        $office_user = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->first();

        $can_edit = $this->canSalesAreaEdit($office_user, $sales_area);
        if ($can_edit || $this->isGranted('ROLE_OWNER')) {
            $enabled = (bool)$sales_area->enabled;
            $enabled = !$enabled;
            $sales_area->enabled = $enabled;
            $sales_area->save();

            if ($enabled === false) {
                PocomosLead::where('sales_area_id', $request->sales_area_id)->update(['sales_area_id' => null]);
            }
            $status = true;
            $message = __('strings.sucess', ['name' => 'Status updated']);
        } else {
            $status = false;
            $message = __('strings.message', ['message' => 'You do not have permissions to process this function']);
        }
        return $this->sendResponse($status, $message);
    }

    /**
     * Update sales ares status
     * @param Request $request
     */
    public function areaBlockAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'sales_area_id' => 'required|exists:pocomos_sales_area,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sales_area = PocomosSalesArea::whereOfficeId($request->office_id)->whereActive(true)->findOrFail($request->sales_area_id);
        $office_user = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->first();

        $can_edit = $this->canSalesAreaEdit($office_user, $sales_area);

        if ($can_edit || $this->isGranted('ROLE_OWNER')) {
            $blocked = (bool)$sales_area->blocked;
            $blocked = !$blocked;
            $sales_area->blocked = $blocked;
            $sales_area->save();
            $status = true;
            $message = __('strings.sucess', ['name' => 'Status updated']);
        } else {
            $status = false;
            $message = __('strings.message', ['message' => 'You do not have permissions to process this function']);
        }
        return $this->sendResponse($status, $message);
    }

    /**
     * Update lead custoemer status
     * @param Request $request
     */
    public function handleCustomerStatusAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'street' => 'required',
            'city' => 'required',
            'region' => 'required',
            'postal_code' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'action' => 'required|in:not-knock,not-home,not-interested,add-lead,add-monitor,add-customer',
            'lead_id' => 'nullable|exists:pocomos_leads,id',
            'reason_id' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        DB::beginTransaction();
        try {

            if ($request->lead_id) {
                $lead = PocomosLead::findOrFail($request->lead_id);
            } else {
                $office_user = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->firstOrFail();
                $salesperson = PocomosSalesPeople::whereUserId($office_user->id)->firstOrFail();
    
                if (!$office_user && $salesperson) {
                    throw new \Exception(__('strings.message', ['message' => 'You must be a Salesperson to create leads from this interface.']));
                }
    
                // A new lead... dawns
                $quote = new PocomosLeadQuote();
                $quote->salesperson_id = $salesperson->id;
                $quote->specific_recurring_schedule = 0.00;
                $quote->regular_initial_price = 0.00;
                $quote->initial_discount = 0.00;
                $quote->initial_price = 0.00;
                $quote->recurring_price = 0.00;
                $quote->map_code = '';
                $quote->autopay = 0;
                $quote->auto_renew = 0;
                $quote->active = true;
                $quote->date_created = date('Y-m-d H:i:s');
                $quote->make_tech_preferred = 0;
                $quote->tax_code = '';
                $quote->previous_balance = 0.00;
                $quote->save();
    
                $lead = new PocomosLead();
                $lead->quote_id = $quote->id;
                $lead->company_name = '';
                $lead->first_name = '';
                $lead->last_name = '';
                $lead->email = '';
                $lead->status = '';
                $lead->subscribed = 0;
                $lead->active = true;
                $lead->date_created = date('Y-m-d H:i:s');
                $lead->external_account_id = '';
                $lead->save();
    
                $lead = PocomosLead::findOrFail($lead->id);
            }
    
            if (!$lead) {
                throw new \Exception(__('strings.message', ['message' => 'Unable to locate Lead.']));
            }
            $res = array();
            
            $region_data = OrkestraCountryRegion::whereCode($request->region)->firstOrFail();

            $address = $lead->addresses;
            $billing_addresses = $lead->billing_addresses;
            // if (!$address) {
            $address_input['region_id'] = $region_data->id ?? '';
            $address_input['street'] =  $request->street ?? '';
            $address_input['suite'] =  $request->suite ?? '';
            $address_input['postal_code'] = $request->postal_code ?? '';
            $address_input['validated'] = 1;
            $address_input['city'] = $request->city ?? '';
            $address_input['active'] = 1;
            $address_input['valid'] = 1;
            // }

            // if (!$billing_addresses) {
            $billing_addresses_input['region_id'] = $region_data->id ?? '';
            $billing_addresses_input['street'] =  $request->street ?? '';
            $billing_addresses_input['suite'] =  $request->suite ?? '';
            $billing_addresses_input['postal_code'] = $request->postal_code ?? '';
            $billing_addresses_input['validated'] = 1;
            $billing_addresses_input['city'] = $request->city ?? '';
            $billing_addresses_input['active'] = 1;
            $billing_addresses_input['valid'] = 1;
            // }

            if ($request->latitude) {
                $address_input['latitude'] = $request->latitude;
                $billing_addresses_input['latitude'] = $request->latitude;
            }

            $reason_id = $request->reason_id ?? null;
            if ($reason_id !== null) {
                $reason = PocomosLeadNotInterestedReason::whereOfficeId($request->office_id)->findOrFail($reason_id);
                if (!$reason) {
                    throw new \Exception(__('strings.message', ['message' => 'Unable to find Lead Not Interest Reason.']));
                }

                $lead->not_interested_reason_id = $reason->id;
            }

            if ($request->longitude) {
                $address_input['longitude'] = $request->longitude;
                $billing_addresses_input['longitude'] = $request->longitude;
            }

            if (!$address) {
                $poocomos_address =  PocomosAddress::create($address_input);
                $lead->contact_address_id = $poocomos_address->id;
            } else {
                $poocomos_address =  PocomosAddress::findOrFail($address->id)->update($address_input);
            }

            if (!$billing_addresses) {
                $poocomos_billing_address =  PocomosAddress::create($billing_addresses_input);
                $lead->billing_address_id = $poocomos_billing_address->id;
            } else {
                $poocomos_billing_address =  PocomosAddress::findOrFail($billing_addresses->id)->update($billing_addresses_input);
            }

            $action = $request->action;
            if ($action !== null) {
                switch ($action) {
                    case 'not-knock':
                        $lead->status = config('constants.NOT_KNOCK');
                        break;
                    case 'not-home':
                        $lead->status = config('constants.NOT_HOME');
                        break;
                    case 'not-interested':
                        $lead->status = config('constants.NOT_INTERESTED');
                        break;
                    case 'add-lead':
                        $lead->status = config('constants.LEAD');
                        break;
                    case 'add-monitor':
                        $lead->status = config('constants.MONITOR');
                        break;
                    case 'add-customer':
                        $lead->status = config('constants.CUSTOMER');
                        break;
                }
            }
            $lead->save();

            $this->setActionStatus($lead, $lead->status);

            DB::commit();

            $res['lead_id'] = $lead->id;
            $status = true;
            $message = __('strings.update', ['name' => 'Status']);
        } catch (\Exception $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }
        return $this->sendResponse(true, $message, $res);
    }

    public function leadMapUsersDetails(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'top_right_lat' => 'required',
            'top_right_long' => 'required',
            'bottom_left_lat' => 'required',
            'bottom_left_long' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $leads = PocomosLead::query();
        $bottomLeftLat = (float)$request->get('bottom_left_lat');
        $bottomLeftLong = (float)$request->get('bottom_left_long');
        $topRightLat = (float)$request->get('top_right_lat');
        $topRightLong = (float)$request->get('top_right_long');

        $ne = new LatLng($topRightLat, $topRightLong);
        $sw = new LatLng($bottomLeftLat, $bottomLeftLong);
        $bounds = new Bounds($sw, $ne);

        $office_user = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->first();
        $areas = $this->getSalesAreasByOfficeUser($office_user);

        $data = [
            'customers' => $this->getCustomersNear($request->office_id, $bottomLeftLat, $bottomLeftLong, $topRightLat, $topRightLong),
            'leads' => $this->getLeadsNear($request->office_id, $bottomLeftLat, $bottomLeftLong, $topRightLat, $topRightLong),
            'areas' => $areas,
        ];
        return $this->sendResponse(true, __('strings.list', ['name' => 'Lead map users']), $data);
    }

    /**
     * @param $officeId
     * @param $bottomLeftLat
     * @param $bottomLeftLong
     * @param $topRightLat
     * @param $topRightLong
     * @return array
     */
    private function getCustomersNear($officeId, $bottomLeftLat, $bottomLeftLong, $topRightLat, $topRightLong)
    {
        $update_data = DB::select(DB::raw("SELECT DISTINCT(c.id)
        FROM pocomos_addresses a
        JOIN pocomos_customers c ON c.contact_address_id = a.id
        JOIN pocomos_customer_sales_profiles csp ON csp.customer_id = c.id
        JOIN pocomos_contracts pc ON pc.profile_id = csp.id
        WHERE csp.office_id = $officeId
        AND c.status = '" . config('constants.ACTIVE') . "'
        AND pc.status = '" . config('constants.ACTIVE') . "'
        AND c.active = true
        AND (a.latitude BETWEEN $bottomLeftLat AND $topRightLat AND a.longitude BETWEEN $bottomLeftLong AND $topRightLong)
        "));

        $ids = array();
        $ids = array_map(function ($row) {
            return $row->id;
        }, $update_data);

        if (empty($ids)) {
            return array();
        }

        $customers = PocomosCustomer::with('coontact_address', 'state_details')->whereIn('id', $ids)->get();

        return $customers;
    }

    /**
     * @param $officeId
     * @param $bottomLeftLat
     * @param $bottomLeftLong
     * @param $topRightLat
     * @param $topRightLong
     * @return array
     */
    private function getLeadsNear($officeId, $bottomLeftLat, $bottomLeftLong, $topRightLat, $topRightLong)
    {
        $results = DB::select(DB::raw("SELECT l.id
        FROM pocomos_addresses a
        JOIN pocomos_leads l ON l.contact_address_id = a.id
        JOIN pocomos_lead_quotes q ON l.quote_id = q.id
        JOIN pocomos_salespeople s ON q.salesperson_id = s.id
        JOIN pocomos_company_office_users ou ON s.user_id = ou.id
        WHERE ou.office_id = $officeId
        AND l.status != '" . config("constants.CUSTOMER") . "'
        AND l.active = " . true . "
        AND a.latitude BETWEEN $bottomLeftLat AND $topRightLat
        AND a.longitude BETWEEN $bottomLeftLong AND $topRightLong
        "));

        $ids = array();
        $ids = array_map(function ($row) {
            return $row->id;
        }, $results);

        if (empty($ids)) {
            return array();
        }

        $lead_users = PocomosLead::with('addresses.primaryPhone', 'addresses.region.country_detail')->whereIn('id', $ids)->get();

        return $lead_users;
    }

    /**Deactive all leads users */
    public function deactivateAllLeads(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office_id = $request->office_id;
        $customer_status = config('constants.CUSTOMER');
        $not_knock = config('constants.NOT_KNOCK');

        $sql = "UPDATE pocomos_leads l
                    JOIN pocomos_lead_quotes q ON l.quote_id = q.id
                    JOIN pocomos_salespeople s ON q.salesperson_id = s.id
                    JOIN pocomos_company_office_users ou ON s.user_id = ou.id
                SET l.active = 0
                WHERE ou.office_id = $office_id AND l.active = 1 AND (l.status <>  '$customer_status' OR l.status <> '$not_knock')";
        DB::select(DB::raw($sql));

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Leads deactivated']));
    }

    /**Leads export user details */
    public function export(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'is_downlaod' => 'boolean'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office = Crypt::encryptString($request->office_id);

        $input['name'] = 'Export Lead';
        $input['description'] = 'The Lead Export has completed successfully <br><br><a href="' . config('constants.API_BASE_URL') . 'exportLeadsDownload/' . $office . '" download>Download Lead Export</a>';
        $input['status'] = 'Posted';
        $input['type'] = 'Alert';
        $input['priority'] = 'Success';
        $input['active'] = true;
        $input['notified'] = true;
        $input['date_created'] = date('Y-m-d H:i:s');
        $alert = PocomosAlert::create($input);

        $office_user = PocomosCompanyOfficeUser::whereOfficeId($request->office_id)->whereUserId(222110)->first();

        $office_alert_details['alert_id'] = $alert->id;
        $office_alert_details['assigned_by_user_id'] = $office_user->id ?? null;
        $office_alert_details['assigned_to_user_id'] = $office_user->id ?? null;
        $office_alert_details['active'] = true;
        $office_alert_details['date_created'] = date('Y-m-d H:i:s');
        PocomosOfficeAlert::create($office_alert_details);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Leads exported']));
    }

    /**Get lead details */
    public function getLeadDetails(Request $request)
    {
        $v = validator($request->all(), [
            'lead_id' => 'required|exists:pocomos_leads,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $lead = PocomosLead::with(['addresses', 'billing_addresses', 'not_interested_reason', 'permanent_note', 'lead_reminder', 'quote_id_detail.service_type', 'quote_id_detail.found_by_type_detail', 'quote_id_detail.county_detail', 'quote_id_detail.sales_person_detail.office_user_details.user_details', 'quote_id_detail.pest_agreement_detail.agreement_detail', 'quote_id_detail.technician_detail.user_detail.user_details', 'quote_id_detail.tags.tag_detail', 'quote_id_detail.pests.pest_detail', 'quote_id_detail.specialty_pests.specialty_pest_detail', 'quote_id_detail.account_detail', 'quote_id_detail.tax_code_detail'])->findOrFail($request->lead_id);

        return $this->sendResponse(true, 'Lead details.', $lead);
    }

    public function getSalesPeopleByOffice($officeId)
    {
        $salesPeople = PocomosSalesPeople::select('*', 'pocomos_salespeople.id')
            ->join('pocomos_company_office_users as pcou', 'pocomos_salespeople.user_id', 'pcou.id')
            ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->where('ou.active', 1)
            ->where('pcou.active', 1)
            ->where('pocomos_salespeople.active', 1)
            ->where('pcou.office_id', $officeId)
            ->get();

        return $this->sendResponse(true, 'Salespeople', [
            'salespeople' => $salesPeople,
        ]);
    }

    /**
     * API for export leads
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportLeads(Request $request)
    {
        $v = validator($request->all(), [
            'lead_ids' => 'nullable|array',
            'lead_ids.*' => 'exists:pocomos_leads,id'
        ], [
            'lead_ids.*.exists' => __('validation.exists', ['attribute' => 'lead id'])
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $leadIds = $request->lead_ids ?? array();
        $exported_columns = $request->exported_columns ?? array();

        ExportLeads::dispatch($exported_columns, $leadIds);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Lead export report generation started']));
    }

    public function sendEmailExportedDetails(Request $request)
    {
        $v = validator($request->all(), [
            'recipient' => 'required|email',
            'subject' => 'required',
            'body' => 'required',
            'lead_ids' => 'nullable|array',
            'lead_ids.*' => 'exists:pocomos_customers,id'
        ], [
            'lead_ids.*.exists' => __('validation.exists', ['attribute' => 'lead id'])
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customerIds = $request->customer_ids ?? array();

        $columns = ["first_name", "last_name", "lead_id", "office", "status", "name", "company_name", "email", "secondary_email", "street", "postal_code", "city", "region", "phone", "address", "lead_status", "agreement", "salesperson", "service_type", "service_frequency", "found_by_type", "map_code", "autopay", "service_frequency", "date_created", "date_signed_up", "initial_price", "recurring_price", "regular_initial_price", "technician", "pests", "specialty_pests", "tags"];

        // Job dispacth for sending customer export details
        SendEmailLeadExport::dispatch($columns, $request->all(), $customerIds);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Lead details Email send']));
    }


    public function lastPersons(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $users = PocomosCompanyOfficeUser::select('*', 'ou.id')
            ->join('orkestra_users as ou', 'pocomos_company_office_users.user_id', 'ou.id')
            ->join('orkestra_user_groups as oug', 'ou.id', 'oug.user_id')
            ->join('orkestra_groups as og', 'oug.group_id', 'og.id')
            ->where('og.role', '!=', 'ROLE_CUSTOMER')
            ->where('pocomos_company_office_users.active', 1)
            ->where('ou.active', 1)
            ->where('pocomos_company_office_users.office_id', $request->office_id)
            ->groupBy('pocomos_company_office_users.id')
            ->orderBy('ou.first_name')
            ->orderBy('ou.last_name')
            ->get();

        return $this->sendResponse(true, 'Last persons to modify', $users);
    }

    /* API for lead Report for knowcking List */
    public function leadKnockingReport(Request $request)
    {
        $v = validator($request->all(), [
            'branches' => 'array',
            'teams' => 'array',
            'salespeople' => 'array',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $page = $request->page ?? 1;
        $perPage = $request->perPage ?? 10;
        $search = $request->search;
        $leadAdmin = false;
        $officeId = $request->office_id;
        $leads = array();

        $office = PocomosCompanyOffice::find($officeId);

        if($this->isGranted('ROLE_LEAD_ADMIN') || $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_OWNER')){
            $salesPerson = true;
            $leadAdmin = true;
        }

        $office_user = auth()->user()->pocomos_company_office_user;
        try {
            $salesPerson = PocomosSalesPeople::whereUserId($office_user->id)->first();
        }catch (\Exception $e) {
            if($leadAdmin){
                $salesPerson = false;
            }else {
                throw new \Exception(__('strings.message', ['message' => 'Unable to continue: you do not hold the keys to this kingdom']));
            }
        }

        $brancheIds = array($request->office_id);
        $dateStart = $request->start_date;
        $dateEnd = $request->end_date;
        $offices = $request->branches ?? array();
        $teams = $request->teams ?? array();
        $salespeople = $request->salespeople ?? array();

        $offices = PocomosCompanyOffice::whereIn('id', $offices)->get();
        $salespeople = PocomosSalesPeople::whereIn('id', $salespeople)->get();

        $branchesTemp = array();

        if($this->isGranted('ROLE_LEAD_ADMIN') === false) {
            $branchesTemp = array($office);

            $salespeople = $salesPerson ? array($salesPerson) : array();
        } elseif(count($offices) === 0) {
            $branchesTemp = $this->getOfficeWithChildren($office);
        }else{
            $branchesTemp = $offices;
        }

        $brancheIds = array();
        foreach($branchesTemp as $val){
            $brancheIds[] = $val->id;
        }

        $salespeopleIds = array();
        foreach ($salespeople as $salesperson) {
            $salespeopleIds[] = $salesperson->id;
        }

        $teamsIds = array();
        foreach ($teams as $team) {
            $teamsIds[] = $team;
        }

        $salespeopleReasons = $this->getReasonCountsBySalesPeople(
            $dateStart,
            $dateEnd,
            $brancheIds,
            $teamsIds,
            $salespeopleIds,
            $page,
            $perPage,
            $search
        );

        $reasonLeads = $this->generateSalesPeopleLeadsArray($leads, $salespeopleReasons, 'reason');

        $salespeopleKnocks = $this->getKnockCountsBySalesPeople(
            $dateStart,
            $dateEnd,
            $brancheIds,
            $teamsIds,
            $salespeopleIds
        );

        $knockLeads = $this->generateSalesPeopleLeadsArray($leads, $salespeopleKnocks, 'knock');

        // for dynamic columns (name)
        $reasons = PocomosLeadNotInterestedReason::whereIn('office_id', $brancheIds)->where('active', 1)->get();

        $data = [
            'reasonLeads' => $reasonLeads,
            'knockLeads' => $knockLeads,
            'reasons' => $reasons,
        ];

        $object = [$data];

        return $this->sendResponse(true, 'Report data', $object);
    }
}
