<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use DB;
use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosTeam;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosRoute;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosSchedule;
use App\Models\Pocomos\PocomosRouteSlots;

use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosOfficeWidget;
use App\Models\Pocomos\PocomosRouteTemplate;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosCustomJobColorRule;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

use function Ramsey\Uuid\v1;

class OfficeScheduleController extends Controller
{
    use Functions;

    public function getTechs(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $technicians = PocomosTechnician::select('*', 'pocomos_technicians.id')
                ->join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
                ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
                ->where('pocomos_technicians.active', 1)
                ->where('pcou.active', 1)
                ->where('pcou.office_id', $officeId)
                ->get();

        return $this->sendResponse(true, 'Reschedule filters', [
            'technicians'   => $technicians,
        ]);
    }

    public function getSchedule(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'technician_id' => 'nullable|exists:pocomos_technicians,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        // $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if ($officeId && !$request->technician_id) {
            // return 77;
            if ($request->override_date) {
                $schedule = PocomosSchedule::whereType('OfficeOverride')->where('date', $request->override_date)
                                        ->where('office_id', $officeId)->first();
                if (!$schedule) {
                    $schedule = PocomosSchedule::whereType('Default')->where('office_id', $officeId)->first();
                }
            } else {
                $schedule = PocomosSchedule::whereType('Default')->where('office_id', $officeId)->first();
            }
        } elseif ($officeId && $request->technician_id) {
            // return 88;
            if ($request->override_date) {
                // return 99;
                $schedule = PocomosSchedule::whereType('TechnicianOverride')->where('date', $request->override_date)
                                    ->whereTechnicianId($request->technician_id)->first();
                if (!$schedule) {
                    $schedule = PocomosSchedule::whereType('DefaultTechnician')
                                            ->whereTechnicianId($request->technician_id)->first();
                    if (!$schedule) {
                        // return 66;
                        $schedule = PocomosSchedule::whereType('Default')->where('office_id', $officeId)->first();
                    }
                }
            } else {
                $schedule = PocomosSchedule::whereType('DefaultTechnician')
                                            ->whereTechnicianId($request->technician_id)->first();
                if (!$schedule) {
                    $schedule = PocomosSchedule::whereType('Default')->where('office_id', $officeId)->first();
                }
            }
        }

        if (isset($schedule->days_open)) {
            $schedule->days_open = json_decode($schedule->days_open, true);
        }

        return $this->sendResponse(true, 'office/tech schedule', [
            'schedule'   => $schedule,
        ]);
    }

    public function createOrUpdateSchedule(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'time_tech_start' => 'required',
            'time_tech_end' => 'required',
            'lunch_duration' => 'required',
            'time_lunch_start' => 'required',
            'days_open' => 'array',
            'technician_id' => 'nullable|exists:pocomos_technicians,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        // $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if ($officeId && !$request->technician_id) {
            // return 77;
            if ($request->override_date) {
                // return 77;
                $schedule = PocomosSchedule::updateOrCreate(
                    [
                    'date' => $request->override_date,
                    'office_id' => $officeId,
                    'type' => 'OfficeOverride',
                ],
                    [
                        'time_tech_start' => $request->time_tech_start,
                        'time_tech_end' => $request->time_tech_end,
                        'lunch_duration' => $request->lunch_duration,
                        'time_lunch_start' => $request->time_lunch_start,
                        'active' => 1,
                        'open' => true,
                        'memo' => $request->memo,
                        'date' => $request->override_date,
                        'office_id' => $officeId,
                        'type' => 'OfficeOverride',
                    ]
                );
            } else {
                $schedule = PocomosSchedule::updateOrCreate(
                    [
                    'office_id' => $officeId,
                    'type'      => 'Default'
                    ],
                    [
                    'time_tech_start' => $request->time_tech_start,
                    'time_tech_end' => $request->time_tech_end,
                    'lunch_duration' => $request->lunch_duration,
                    'time_lunch_start' => $request->time_lunch_start,
                    'active' => 1,
                    'days_open' => json_encode($request->days_open),
                    'open' => true,
                    'office_id' => $officeId,
                    'type'      => 'Default'
                ]
                );
            }
        } elseif ($officeId && $request->technician_id) {
            if ($request->override_date) {
                // return 66;

                $schedule = PocomosSchedule::updateOrCreate(
                    [
                    'date' => $request->override_date,
                    'technician_id' => $request->technician_id,
                    'type' => 'TechnicianOverride',
                ],
                    [
                        'time_tech_start' => $request->time_tech_start,
                        'time_tech_end' => $request->time_tech_end,
                        'lunch_duration' => $request->lunch_duration,
                        'time_lunch_start' => $request->time_lunch_start,
                        'open' => true,
                        'memo' => $request->memo,
                        'active' => 1,
                        'date' => $request->override_date,
                        'technician_id' => $request->technician_id,
                        'type' => 'TechnicianOverride',
                    ]
                );
            } else {
                $schedule = PocomosSchedule::updateOrCreate(
                    [
                    'technician_id' => $request->technician_id,
                    'type' => 'DefaultTechnician',
                ],
                    [
                        'time_tech_start' => $request->time_tech_start,
                        'time_tech_end' => $request->time_tech_end,
                        'lunch_duration' => $request->lunch_duration,
                        'time_lunch_start' => $request->time_lunch_start,
                        'days_open' => json_encode($request->days_open),
                        'active' => 1,
                        'open' => true,
                        'technician_id' => $request->technician_id,
                        'type' => 'DefaultTechnician',
                    ]
                );
            }
        }

        // return date('H:i:s', strtotime($request->time_tech_end) - 60*60);

        // move all calendar slots to pool jobs and remove lunches, blocks etc. if tech start/end time changes.
        // get calender slots
        $slotsQuery = PocomosRoute::select(
            '*',
            'pocomos_routes.id',
            'pocomos_routes.name',
            'pocomos_routes.locked',
            'pocomos_routes.technician_id',
            'pocomos_routes.date_scheduled',
            'pi.balance as invoice_balance',
            'prs.time_begin',
            'pj.slot_id as job_slot_id',
            'prs.id as slot_id',
            'prs.type as slot_type',
            'pj.status as job_status',
            'pj.id as job_id',
            'pj.color',
            'prs.color as slot_color',
            'ppc.technician_id as pref_tech_id',
            'pj.commission_type',
            'pj.commission_value',
            'pcu.first_name',
            'pcu.last_name',
            'slot_ou.first_name as slot_ou_fname',
            'slot_ou.last_name as slot_ou_lname',
            'ppcst.name as service_type',
            'ppcst.color as service_type_color',
            'prs.date_created'
        )
        ->leftJoin('pocomos_route_slots as prs', 'pocomos_routes.id', 'prs.route_id')
        ->leftJoin('pocomos_jobs as pj', 'prs.id', 'pj.slot_id')
        ->leftJoin('pocomos_technicians as pt', 'pocomos_routes.technician_id', 'pt.id')
        ->leftJoin('pocomos_company_office_users as pcou', 'pt.user_id', 'pcou.id')
        ->leftJoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
        ->leftJoin('pocomos_pest_contracts as ppc', 'pj.contract_id', 'ppc.id')
        ->leftJoin('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
        ->leftJoin('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
        ->leftJoin('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
        ->leftJoin('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
        ->leftJoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
        ->leftJoin('orkestra_countries_regions as ocr', 'pa.region_id', 'ocr.id')
        ->leftJoin('pocomos_invoices as pi', 'pj.invoice_id', 'pi.id')
        ->leftJoin('pocomos_invoice_items as pii', 'pi.id', 'pii.invoice_id')
        ->join('pocomos_company_offices as pco', 'pocomos_routes.office_id', 'pco.id')

        //added
        ->leftJoin('pocomos_company_office_users as slot_pcou', 'prs.office_user_id', 'slot_pcou.id')
        ->leftJoin('orkestra_users as slot_ou', 'pcou.user_id', 'slot_ou.id')
        ->leftJoin('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id')

        ->where(function ($q) use ($request) {
            $q->whereNotBetween('prs.time_begin', [$request->time_tech_start, $request->time_tech_end])
            ->orwhere(DB::raw('DATE_ADD(prs.time_begin, INTERVAL duration MINUTE)'), '>', $request->time_tech_end);
        });

        $slotIds = (clone($slotsQuery))->whereNotNull('pj.slot_id')->groupBy('pj.slot_id')
                        ->pluck('slot_id');

        PocomosRouteSlots::whereIn('id', $slotIds)->delete();

        $blockIds = (clone($slotsQuery))->where(function ($q) {
            $q->where('prs.type', 'Lunch')
                ->orWhere('prs.type', 'Blocked')
                ->orWhere('prs.type', 'Reserved');
        })->pluck('slot_id');

        PocomosRouteSlots::whereIn('id', $blockIds)->delete();


        return $this->sendResponse(true, 'office/tech schedule', [
            'schedule'   => $schedule,
            'slotIds'   => $slotIds,
        ]);
    }

    public function getServiceDurations(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            // 'technician_id' => 'nullable|exists:pocomos_technicians,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        // $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        $pestConfig = PocomosPestOfficeSetting::where('office_id', $officeId)->firstOrFail();

        return $this->sendResponse(true, 'office/tech schedule', [
            'pest_config'   => $pestConfig,
        ]);
    }

    public function updateServiceDurations(Request $request, $id)
    {
        $v = validator($request->all(), [
                'show_service_duration_option_agreement' => 'required',
                'disable_recurring_jobs' => 'required',
                'require_map_code' => 'required',
                'anytime_enabled' => 'required',
                'enable_remote_completion' => 'required',
                'enable_blocked_spots' => 'required',
                'restrict_salesperson_customer_creation' => 'required',
                'show_initial_job_note' => 'required',
                'default_autopay_value' => 'required',
                'send_customer_portal_setup' => 'required',
                'alert_on_remote_completion' => 'required',
                'email_on_remote_completion' => 'required',
                'show_custom_fields_on_remote_completion' => 'required',
                'enable_discount_type' => 'required',
                'validate_initial_price' => 'required',
                'view_add_days' => 'required',
                'job_pool_sorting_by' => 'required',
                'my_spots_duration' => 'required',
                'best_fit_range' => 'required',
                'coloring_scheme' => 'required',
                'regular_duration' => 'required',
                'initial_duration' => 'required',
                'lock_default_job_duration' => 'required'
            ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        // $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        $pestConfig = PocomosPestOfficeSetting::whereId($id)->update([
            'show_service_duration_option_agreement' => $request->show_service_duration_option_agreement,
            'disable_recurring_jobs' => $request->disable_recurring_jobs,
            'require_map_code' => $request->require_map_code,
            'anytime_enabled' => $request->anytime_enabled,
            'enable_remote_completion' => $request->enable_remote_completion,
            'enable_blocked_spots' => $request->enable_blocked_spots,
            'restrict_salesperson_customer_creation' => $request->restrict_salesperson_customer_creation,
            'show_initial_job_note' => $request->show_initial_job_note,
            'default_autopay_value' => $request->default_autopay_value,
            'send_customer_portal_setup' => $request->send_customer_portal_setup,
            'alert_on_remote_completion' => $request->alert_on_remote_completion,
            'email_on_remote_completion' => $request->email_on_remote_completion,
            'show_custom_fields_on_remote_completion' => $request->show_custom_fields_on_remote_completion,
            'enable_discount_type' => $request->enable_discount_type,
            'validate_initial_price' => $request->validate_initial_price,
            'view_add_days' => $request->view_add_days,
            'job_pool_sorting_by' => $request->job_pool_sorting_by,
            'my_spots_duration' => $request->my_spots_duration,
            'best_fit_range' => $request->best_fit_range,
            'coloring_scheme' => $request->coloring_scheme,
            'regular_duration' => $request->regular_duration,
            'initial_duration' => $request->initial_duration,
            'lock_default_job_duration' => $request->lock_default_job_duration,
        ]);


        // move all anytime slots to pool jobs if anytime disabled
        if ($request->anytime_enabled == 0) {
            // for calender slots
            $slotIds = PocomosRoute::select(
                '*',
                'pocomos_routes.id',
                'pocomos_routes.name',
                'pocomos_routes.locked',
                'pocomos_routes.technician_id',
                'pocomos_routes.date_scheduled',
                'pi.balance as invoice_balance',
                'prs.time_begin',
                'pj.slot_id as job_slot_id',
                'prs.id as slot_id',
                'prs.type as slot_type',
                'pj.status as job_status',
                'pj.id as job_id',
                'pj.color',
                'prs.color as slot_color',
                'ppc.technician_id as pref_tech_id',
                'pj.commission_type',
                'pj.commission_value',
                'pcu.first_name',
                'pcu.last_name',
                'slot_ou.first_name as slot_ou_fname',
                'slot_ou.last_name as slot_ou_lname',
                'ppcst.name as service_type',
                'ppcst.color as service_type_color',
                'prs.date_created'
            )
            ->leftJoin('pocomos_route_slots as prs', 'pocomos_routes.id', 'prs.route_id')
            ->leftJoin('pocomos_jobs as pj', 'prs.id', 'pj.slot_id')
            ->leftJoin('pocomos_technicians as pt', 'pocomos_routes.technician_id', 'pt.id')
            ->leftJoin('pocomos_company_office_users as pcou', 'pt.user_id', 'pcou.id')
            ->leftJoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->leftJoin('pocomos_pest_contracts as ppc', 'pj.contract_id', 'ppc.id')
            ->leftJoin('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->leftJoin('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->leftJoin('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->leftJoin('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
            ->leftJoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->leftJoin('orkestra_countries_regions as ocr', 'pa.region_id', 'ocr.id')
            ->leftJoin('pocomos_invoices as pi', 'pj.invoice_id', 'pi.id')
            ->leftJoin('pocomos_invoice_items as pii', 'pi.id', 'pii.invoice_id')
            ->join('pocomos_company_offices as pco', 'pocomos_routes.office_id', 'pco.id')

            //added
            ->leftJoin('pocomos_company_office_users as slot_pcou', 'prs.office_user_id', 'slot_pcou.id')
            ->leftJoin('orkestra_users as slot_ou', 'pcou.user_id', 'slot_ou.id')
            ->leftJoin('pocomos_pest_contract_service_types as ppcst', 'ppc.service_type_id', 'ppcst.id')

            ->whereNotNull('pj.slot_id')->groupBy('pj.slot_id')

            ->where('prs.time_begin', '00:00:00')
            ->where('prs.anytime', 1)

            ->get()->makeHidden('agreement_body')->pluck('slot_id');

            PocomosRouteSlots::whereIn('id', $slotIds)->delete();
        }

        return $this->sendResponse(true, __('strings.update', ['name' => 'Office configuration']));
    }

    public function listJobColorRules(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            // 'technician_id' => 'nullable|exists:pocomos_technicians,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        // $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        $JobColorRules = PocomosCustomJobColorRule::with([
                    'agreement:id,name',
                    'pest_contract_service_type:id,name',
                    'tag:id,name'
                    ])
                ->where('office_id', $officeId)->orderBy('priority')->whereActive(true)->get();

        return $this->sendResponse(true, 'custom job color rules', [
            'job_color_rules'   => $JobColorRules,
        ]);
    }


    public function createJobColorRule(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'color' => 'required',
            // 'technician_id' => 'nullable|exists:pocomos_technicians,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // $officeId = $request->office_id;
        // $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        PocomosCustomJobColorRule::create([
            'agreement_id' => $request->agreement_id,
            'service_type_id' => $request->service_type_id,
            'office_id' => $request->office_id,
            'job_type' => $request->job_type,
            'color' => $request->color,
            'priority' => 0,
            'tag_id' => $request->tag_id,
            'active' => 1
        ]);

        return $this->sendResponse(true, __('strings.create', ['name' => 'JCR']));
    }


    public function updateJobColorRule(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            // 'technician_id' => 'nullable|exists:pocomos_technicians,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // $officeId = $request->office_id;
        // $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();



        PocomosCustomJobColorRule::whereId($id)->update([
            'agreement_id' => $request->agreement_id,
            'service_type_id' => $request->service_type_id,
            'job_type' => $request->job_type,
            'color' => $request->color,
            'tag_id' => $request->tag_id
        ]);

        return $this->sendResponse(true, __('strings.update', ['name' => 'JCR']));
    }

    public function deleteJobColorRule(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $JobColorRule = PocomosCustomJobColorRule::whereId($id)->firstorfail();
        $JobColorRule->active = false;
        $JobColorRule->save();

        return $this->sendResponse(true, __('strings.delete', ['name' => 'JCR']));
    }

    public function reorderJobColorRules(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $rules = $request->rules;

        $i = 1;
        foreach ($rules as $rule) {
            $res = DB::select(DB::raw("UPDATE pocomos_custom_job_color_rules SET priority = $i 
                        WHERE office_id = $request->office_id AND id = $rule"));
            $i++;
        }
    }
}
