<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAutomatedEmailsReport;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use Illuminate\Support\Facades\DB;

class AutomatedReportController extends Controller
{
    use Functions;

    /**
     * API for list of AutomatedReport
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

        $PocomosAutomatedEmailsReport = PocomosAutomatedEmailsReport::with('user_details_name')->where('office_id', $request->office_id)->where('deleted', 0);

        if ($request->search) {
            $search = $request->search;


            $sql = "SELECT pt.user_id
            FROM pocomos_automated_emails_reports AS pt
            JOIN orkestra_users AS ou ON pt.user_id = ou.id
            WHERE (ou.first_name LIKE '%$search%' OR ou.last_name LIKE '%$search%')";
            
            
            $tempTechIds = DB::select(DB::raw($sql));
            $techIds = array_map(function ($value) {
                return $value->user_id;
            }, $tempTechIds);
            
            if(in_array($search,['Enabled','enabled','Disabled','disabled'])){
                if($search == 'Enabled' || $search == 'enabled'){
                    $status = 1;
    
                }
                if($search == 'Disabled' || $search == 'disabled'){
                    $status = 0;
                }
                $PocomosAutomatedEmailsReport = $PocomosAutomatedEmailsReport->where('active',$status);
            }else{
                $PocomosAutomatedEmailsReport = $PocomosAutomatedEmailsReport->where(function ($q) use ($search, $techIds) {
                    $q->where('name', 'like', '%' . $search . '%');
                    $q->orWhere('report_selected', 'like', '%' . $search . '%');
                    $q->orWhere('frequency', 'like', '%' . $search . '%');
                    $q->orWhere('next_scheduled_date_time', 'like', '%' . $search . '%');
                    $q->orWhereIn('user_id', $techIds);
                });
            }
            
           
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosAutomatedEmailsReport->count();
        $PocomosAutomatedEmailsReport = $PocomosAutomatedEmailsReport->skip($perPage * ($page - 1))->take($perPage)->get();

        $PocomosAutomatedEmailsReport->map(function ($status) use ($request) {
            $status->branch_data = [];
            if (unserialize($status->branch_selected)) {
                $status->branch_data = PocomosCompanyOffice::whereIn('id', unserialize($status->branch_selected))->select('name', 'id')->get();
            }
        });

        $data = [
            'AutomatedReport' => $PocomosAutomatedEmailsReport,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'AutomatedReport']), $data);
    }

    /**
     * API for get  AutomatedReport details
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosAutomatedEmailsReport = PocomosAutomatedEmailsReport::find($id);
        if (!$PocomosAutomatedEmailsReport) {
            return $this->sendResponse(false, 'AutomatedReport Not Found');
        }
        return $this->sendResponse(true, 'AutomatedReport details.', $PocomosAutomatedEmailsReport);
    }

    /**
     * API for create of AutomatedReport
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required|unique:pocomos_automated_emails_reports',
            'report_selected' => 'required|in:Summary Payment Report,Detailed Payment Report',
            'branch_selected' => 'required|array|exists:pocomos_company_offices,id',
            'date_range' => 'required|in:Current Day,Yesterday,Current Week,Previous Week,Current Month,Previous Month,Current Year,Previous Year',
            'frequency' => 'required|in:Daily,Weekly,Monthly,Quarterly,Annually',
            'sent_day' => 'nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'week_of_month' => 'required|in:1,2,3,4',
            'time_begin' => 'required',
            'email_subject' => 'required',
            'email_body' => 'nullable',
            'email_address' => 'required|email',
            'secondary_emails' => 'nullable|email',
            'active' => 'required|boolean',
            'user_id' => 'required|exists:orkestra_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input = $request->only('office_id', 'name', 'report_selected', 'date_range', 'frequency', 'sent_day', 'week_of_month', 'time_begin', 'email_subject', 'email_body', 'email_address', 'secondary_emails', 'active', 'user_id');

        $input['branch_selected'] = serialize($request->input('branch_selected'));

        $input['next_scheduled_date_time'] =  "2023-08-07 07:00:00";

        $PocomosAutomatedEmailsReport = PocomosAutomatedEmailsReport::create($input);

        /**End manage trail */
        return $this->sendResponse(true, 'AutomatedReport created successfully.', $PocomosAutomatedEmailsReport);
    }

    /**
     * API for update of AutomatedReport
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'report_id' => 'required|exists:pocomos_automated_emails_reports,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'report_selected' => 'required|in:Summary Payment Report,Detailed Payment Report',
            'branch_selected' => 'required|array|exists:pocomos_company_offices,id',
            'date_range' => 'required|in:Current Day,Yesterday,Current Week,Previous Week,Current Month,Previous Month,Current Year,Previous Year',
            'frequency' => 'required|in:Daily,Weekly,Monthly,Quarterly,Annually',
            'sent_day' => 'nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'week_of_month' => 'required|in:1,2,3,4',
            'time_begin' => 'required',
            'email_subject' => 'required',
            'email_body' => 'nullable',
            'email_address' => 'required|email',
            'secondary_emails' => 'nullable|email',
            'active' => 'required|boolean',
            'user_id' => 'required|exists:orkestra_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosAutomatedEmailsReport = PocomosAutomatedEmailsReport::where('id', $request->report_id)->where('office_id', $request->office_id)->first();

        if (!$PocomosAutomatedEmailsReport) {
            return $this->sendResponse(false, 'AutomatedReport not found.');
        }

        $input = $request->only('office_id', 'name', 'report_selected', 'date_range', 'frequency', 'sent_day', 'week_of_month', 'time_begin', 'email_subject', 'email_body', 'email_address', 'secondary_emails', 'active', 'user_id');

        $input['branch_selected'] = serialize($request->input('branch_selected'));

        $result =  $PocomosAutomatedEmailsReport->update($input);

        return $this->sendResponse(true, 'AutomatedReport updated successfully.', $PocomosAutomatedEmailsReport);
    }

    /**
     * API for delete of AutomatedReport
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosAutomatedEmailsReport = PocomosAutomatedEmailsReport::find($id);
        if (!$PocomosAutomatedEmailsReport) {
            return $this->sendResponse(false, 'AutomatedReport not found.');
        }

        $PocomosAutomatedEmailsReport->update(['deleted' => 1]);

        return $this->sendResponse(true, 'AutomatedReport deleted successfully.');
    }


    /* API for changeStatus of  AutomatedReport */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'AutomatedReport_id' => 'required|exists:pocomos_automated_emails_reports,id',
            'active' => 'boolean|required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosAutomatedEmailsReport = PocomosAutomatedEmailsReport::find($request->AutomatedReport_id);
        if (!$PocomosAutomatedEmailsReport) {
            return $this->sendResponse(false, 'AutomatedReport type not found');
        }

        $PocomosAutomatedEmailsReport->update([
            'active' => $request->active
        ]);

        return $this->sendResponse(true, 'Status changed successfully.');
    }
}
