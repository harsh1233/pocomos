<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use DB;
use Excel;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAcsEvent;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosAcsJobEventsException;
use App\Models\Pocomos\PocomosPestContractServiceType;
use App\Models\Pocomos\PocomosAcsJobEventsServiceType;
use App\Models\Pocomos\PocomosAcsJobEventsAgreement;
use App\Models\Pocomos\PocomosAcsJobEventsTag;
use Illuminate\Support\Facades\DB as FacadesDB;

class ACSJobEventController extends Controller
{
    use Functions;

    /**
     * API for list of Job event
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
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $jobSched = PocomosAcsEvent::with('exception_tags.exception_tag_detail', 'acs_agreements.agreement_detail', 'acs_serice_types.service_type_detail', 'acs_tags.tag_detail')
            ->leftJoin('pocomos_form_letters as ca', 'ca.id', '=', 'pocomos_acs_events.form_letter_id')
            ->leftJoin('pocomos_sms_form_letters as sms', 'sms.id', '=', 'pocomos_acs_events.sms_form_letter_id')
            ->leftJoin('pocomos_voice_form_letters as va', 'va.id', '=', 'pocomos_acs_events.voice_form_letter_id')
            ->where('pocomos_acs_events.event_type', 'Job Scheduled')
            ->where('pocomos_acs_events.office_id', $request->office_id)->where('pocomos_acs_events.active', 1)
            ->select('pocomos_acs_events.*', 'ca.title as EmailLetter', 'sms.title as SMSLetter', 'va.title as VoiceLetter')->get()->toArray();

        $jobComp = PocomosAcsEvent::with('exception_tags.exception_tag_detail', 'acs_agreements.agreement_detail', 'acs_serice_types.service_type_detail', 'acs_tags.tag_detail')
            ->leftJoin('pocomos_form_letters as ca', 'ca.id', '=', 'pocomos_acs_events.form_letter_id')
            ->leftJoin('pocomos_sms_form_letters as sms', 'sms.id', '=', 'pocomos_acs_events.sms_form_letter_id')
            ->leftJoin('pocomos_voice_form_letters as va', 'va.id', '=', 'pocomos_acs_events.voice_form_letter_id')
            ->where('pocomos_acs_events.event_type', 'Job Completed')
            ->where('pocomos_acs_events.office_id', $request->office_id)->where('pocomos_acs_events.active', 1)
            ->select('pocomos_acs_events.*', 'ca.title as EmailLetter', 'sms.title as SMSLetter', 'va.title as VoiceLetter')->get()->toArray();

        $newCust = PocomosAcsEvent::with('exception_tags.exception_tag_detail', 'acs_agreements.agreement_detail', 'acs_serice_types.service_type_detail', 'acs_tags.tag_detail')
            ->leftJoin('pocomos_form_letters as ca', 'ca.id', '=', 'pocomos_acs_events.form_letter_id')
            ->leftJoin('pocomos_sms_form_letters as sms', 'sms.id', '=', 'pocomos_acs_events.sms_form_letter_id')
            ->leftJoin('pocomos_voice_form_letters as va', 'va.id', '=', 'pocomos_acs_events.voice_form_letter_id')
            ->where('pocomos_acs_events.event_type', 'New Customer')
            ->where('pocomos_acs_events.office_id', $request->office_id)->where('pocomos_acs_events.active', 1)
            ->select('pocomos_acs_events.*', 'ca.title as EmailLetter', 'sms.title as SMSLetter', 'va.title as VoiceLetter')->get()->toArray();

        $invoiceDue = PocomosAcsEvent::with('exception_tags.exception_tag_detail', 'acs_agreements.agreement_detail', 'acs_serice_types.service_type_detail', 'acs_tags.tag_detail')
            ->leftJoin('pocomos_form_letters as ca', 'ca.id', '=', 'pocomos_acs_events.form_letter_id')
            ->leftJoin('pocomos_sms_form_letters as sms', 'sms.id', '=', 'pocomos_acs_events.sms_form_letter_id')
            ->leftJoin('pocomos_voice_form_letters as va', 'va.id', '=', 'pocomos_acs_events.voice_form_letter_id')
            ->where('pocomos_acs_events.event_type', 'Payment Failed')
            ->where('pocomos_acs_events.office_id', $request->office_id)->where('pocomos_acs_events.active', 1)
            ->select('pocomos_acs_events.*', 'ca.title as EmailLetter', 'sms.title as SMSLetter', 'va.title as VoiceLetter')->get()->toArray();

        usort($jobSched, function ($a, $b) {
            $timeA = strtotime('+' . $a['amount_of_time'] . ' ' . $a['unit_of_time']);
            $timeB = strtotime('+' . $b['amount_of_time'] . ' ' . $b['unit_of_time']);
            if ($timeA === $timeB) {
                return 0;
            }
            return ($timeA > $timeB) ? 1 : -1;
        });

        usort($jobComp, function ($a, $b) {
            $timeA = strtotime('+' . $a['amount_of_time'] . ' ' . $a['unit_of_time']);
            $timeB = strtotime('+' . $b['amount_of_time'] . ' ' . $b['unit_of_time']);
            if ($timeA === $timeB) {
                return 0;
            }
            return ($timeA > $timeB) ? 1 : -1;
        });

        usort($newCust, function ($a, $b) {
            $timeA = strtotime('+' . $a['amount_of_time'] . ' ' . $a['unit_of_time']);
            $timeB = strtotime('+' . $b['amount_of_time'] . ' ' . $b['unit_of_time']);
            if ($timeA === $timeB) {
                return 0;
            }
            return ($timeA > $timeB) ? 1 : -1;
        });

        usort($invoiceDue, function ($a, $b) {
            $timeA = strtotime('+' . $a['amount_of_time'] . ' ' . $a['unit_of_time']);
            $timeB = strtotime('+' . $b['amount_of_time'] . ' ' . $b['unit_of_time']);
            if ($timeA === $timeB) {
                return 0;
            }
            return ($timeA > $timeB) ? 1 : -1;
        });

        $jobEvents =  array_merge($jobSched, $jobComp, $newCust, $invoiceDue);

        $count = count($jobEvents);

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];

        $jobEvents_test = array_slice($jobEvents, $perPage * ($page - 1), $perPage);

        $demo = 0;
        foreach ($jobEvents_test as $test) {
            $job_type_data = [];

            if (unserialize($test['job_type'])) {
                $job_type_data = unserialize($test['job_type']);
            }
            $jobEvents_test[$demo]['job_type_data'] = $job_type_data;
            $demo = $demo + 1;
        }

        return $this->sendResponse(true, 'List', [
            'acs_job_event' => $jobEvents_test,
            'count' => $count,
        ]);
    }

    /**
     * API for create of Job event
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'form_letter_id' => 'nullable|exists:pocomos_form_letters,id',
            'sms_form_letter_id' => 'nullable|exists:pocomos_sms_form_letters,id',
            'voice_form_letter_id' => 'nullable|exists:pocomos_voice_form_letters,id',
            'job_type' => 'array|in:Initial,Regular,Re-service,Inspection,Follow-up,Pickup Service',
            'service_type_id' => 'array|exists:pocomos_pest_contract_service_types,id',
            'agreement_id' => 'array|exists:pocomos_agreements,id',
            'tag_id' => 'array|exists:pocomos_tags,id',
            'exception_tags' => 'array|exists:pocomos_tags,id',
            'amount_of_time' => 'required|gt:0',
            'unit_of_time' => 'required|in:Minute,Hour,Day',
            'before_after' => 'required|in:Before,After',
            'event_type' => 'required|in:Job Scheduled,Job Completed,Payment Failed,New Customer',
            'autopay' => 'required|boolean',
            'customer_autopay' => 'required|boolean',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('office_id', 'amount_of_time', 'unit_of_time', 'before_after', 'event_type', 'autopay', 'customer_autopay', 'enabled');

        $input_details['job_type'] =  serialize($request->input('job_type'));

        $input_details['service_type_id'] =   null;

        $input_details['agreement_id'] =  null;

        $input_details['tag_id'] =  null;

        $input_details['form_letter_id'] = $request->input('form_letter_id');

        $input_details['sms_form_letter_id'] =  $request->input('sms_form_letter_id');

        $input_details['voice_form_letter_id'] =  $request->input('voice_form_letter_id');

        $PocomosAcsEvent =  PocomosAcsEvent::create($input_details);

        if ($request->service_type_id) {
            foreach ($request->service_type_id as $q) {
                PocomosAcsJobEventsServiceType::create([
                    'acs_event_id' => $PocomosAcsEvent->id,
                    'service_type_id' => $q
                ]);
            }
        }

        if ($request->tag_id) {
            foreach ($request->tag_id as $q) {
                PocomosAcsJobEventsTag::create([
                    'acs_event_id' => $PocomosAcsEvent->id,
                    'tag_id' => $q
                ]);
            }
        }

        if ($request->exception_tags) {
            foreach ($request->exception_tags as $q) {
                PocomosAcsJobEventsException::create([
                    'acs_event_id' => $PocomosAcsEvent->id,
                    'exception_id' => $q
                ]);
            }
        }

        if ($request->agreement_id) {
            foreach ($request->agreement_id as $q) {
                PocomosAcsJobEventsAgreement::create([
                    'acs_event_id' => $PocomosAcsEvent->id,
                    'agreement_id' => $q
                ]);
            }
        }

        $sql = 'SELECT j.id as id
                FROM pocomos_acs_events AS j
                WHERE j.office_id = ' . $request->office_id . '
                AND j.enabled = 1   AND j.Active = 1 AND j.id = ' . $PocomosAcsEvent->id . '';

        $jobEvents = DB::select(DB::raw($sql));

        foreach ($jobEvents as $jobEvent) {
            FacadesDB::beginTransaction();
            try {
                $event = PocomosAcsEvent::where('id', $jobEvent->id)->firstorfail();

                $this->processAcsJobEvent($event);
                FacadesDB::commit();
            } catch (\Exception $e) {
                FacadesDB::rollback();
                throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
            }
        }

        return $this->sendResponse(true, 'Job event created successfully.', $PocomosAcsEvent);
    }

    /**
     * API for update of Job event
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'event_id' => 'required|exists:pocomos_acs_events,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'form_letter_id' => 'nullable|exists:pocomos_form_letters,id',
            'sms_form_letter_id' => 'nullable|exists:pocomos_sms_form_letters,id',
            'voice_form_letter_id' => 'nullable|exists:pocomos_voice_form_letters,id',
            'job_type' => 'array|in:Initial,Regular,Re-service,Inspection,Follow-up,Pickup Service',
            'service_type_id' => 'array|exists:pocomos_pest_contract_service_types,id',
            'agreement_id' => 'array|exists:pocomos_agreements,id',
            'tag_id' => 'array|exists:pocomos_tags,id',
            'exception_tags' => 'array|exists:pocomos_tags,id',
            'amount_of_time' => 'required|gt:0',
            'unit_of_time' => 'required|in:Minute,Hour,Day',
            'before_after' => 'required|in:Before,After',
            'event_type' => 'required|in:Job Scheduled,Job Completed,Payment Failed,New Customer',
            'autopay' => 'required|boolean',
            'customer_autopay' => 'required|boolean',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosAcsEvent = PocomosAcsEvent::with('exception_tags', 'acs_agreements', 'acs_serice_types', 'acs_tags')
            ->where('office_id', $request->office_id)->where('id', $request->event_id)->where('active', 1)->first();

        if (!$PocomosAcsEvent) {
            return $this->sendResponse(false, 'Unable to find the ACS Job Event.');
        }

        $PocomosAcsEvent->exception_tags()->delete();
        $PocomosAcsEvent->acs_agreements()->delete();
        $PocomosAcsEvent->acs_serice_types()->delete();
        $PocomosAcsEvent->acs_tags()->delete();

        if ($request->service_type_id) {
            foreach ($request->service_type_id as $q) {
                PocomosAcsJobEventsServiceType::create([
                    'acs_event_id' => $PocomosAcsEvent->id,
                    'service_type_id' => $q
                ]);
            }
        }

        if ($request->tag_id) {
            foreach ($request->tag_id as $q) {
                PocomosAcsJobEventsTag::create([
                    'acs_event_id' => $PocomosAcsEvent->id,
                    'tag_id' => $q
                ]);
            }
        }

        if ($request->exception_tags) {
            foreach ($request->exception_tags as $q) {
                PocomosAcsJobEventsException::create([
                    'acs_event_id' => $PocomosAcsEvent->id,
                    'exception_id' => $q
                ]);
            }
        }

        if ($request->agreement_id) {
            foreach ($request->agreement_id as $q) {
                PocomosAcsJobEventsAgreement::create([
                    'acs_event_id' => $PocomosAcsEvent->id,
                    'agreement_id' => $q
                ]);
            }
        }

        $input_details = $request->only('office_id', 'amount_of_time', 'unit_of_time', 'before_after', 'event_type', 'autopay', 'customer_autopay', 'enabled');

        $input_details['job_type'] =  serialize($request->input('job_type'));

        $input_details['service_type_id'] =   null;

        $input_details['agreement_id'] =  null;

        $input_details['tag_id'] =  null;

        $input_details['form_letter_id'] = $request->form_letter_id ?? null;

        $input_details['sms_form_letter_id'] = $request->sms_form_letter_id ?? null;

        $input_details['voice_form_letter_id'] = $request->voice_form_letter_id ?? null;

        $PocomosAcsEvent->update($input_details);

        $sql = 'SELECT j.id as id
                FROM pocomos_acs_events AS j
                WHERE j.office_id = ' . $request->office_id . '
                AND j.enabled = 1   AND j.Active = 1 AND j.id = ' . $PocomosAcsEvent->id . '';

        $jobEvents = DB::select(DB::raw($sql));

        foreach ($jobEvents as $jobEvent) {
            FacadesDB::beginTransaction();
            try {
                $event = PocomosAcsEvent::where('id', $jobEvent->id)->firstorfail();

                $this->processAcsJobEvent($event);
                FacadesDB::commit();
            } catch (\Exception $e) {
                FacadesDB::rollback();
                throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
            }
        }

        return $this->sendResponse(true, 'Job event updated successfully.', $PocomosAcsEvent);
    }

    /* API for changeStatus of Job event */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'event_id' => 'required|exists:pocomos_acs_events,id',
            'enabled' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosAcsEvent = PocomosAcsEvent::where('office_id', $request->office_id)->where('id', $request->event_id)->where('active', 1)->first();

        if (!$PocomosAcsEvent) {
            return $this->sendResponse(false, ' Job event  not found');
        }

        $PocomosAcsEvent->update([
            'enabled' => $request->enabled
        ]);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Status']));
    }

    /**
     * API for delete of Job event
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'event_id' => 'required|exists:pocomos_acs_events,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosAcsEvent = PocomosAcsEvent::where('office_id', $request->office_id)->where('id', $request->event_id)->where('active', 1)->first();

        if (!$PocomosAcsEvent) {
            return $this->sendResponse(false, 'Job event not found.');
        }

        $PocomosAcsEvent->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Job event deleted successfully.');
    }

    /**
     * This function is created for only testing purpose of auto communication function.
     */

    public function acsjobeventmail(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::findOrFail($request->office_id);

        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company not found.');
        }

        // $sql = 'SELECT j.id as id
        //         FROM pocomos_acs_events AS j
        //         WHERE j.office_id = ' . $request->office_id . '
        //         AND j.enabled = 1   AND j.Active = 1';

        // $jobEvents = DB::select(DB::raw($sql));

        // foreach ($jobEvents as $jobEvent) {
        //     FacadesDB::beginTransaction();
        //     try {
        //         $event = PocomosAcsEvent::where('id', $jobEvent->id)->firstorfail();

        //         $this->processAcsJobEvent($event);
        //         FacadesDB::commit();
        //     } catch (\Exception $e) {
        //         FacadesDB::rollback();
        //         throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        //     }
        // }

        $notification = $this->getPendingNotification();

        foreach ($notification as $value) {
            $this->sendNotification($value);
        }

        return $this->sendResponse(true, 'Job notification updated successfully.');
    }
}
