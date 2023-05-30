<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Jobs\OfficeBillingReportJob;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\Billing\CompanyBillingReport;
use App\Models\Pocomos\Billing\CompanyBillingProfile;

class OfficeBillingReportsController extends Controller
{
    use Functions;

    /* API for Office Billing Reports List */
    public function officeBillingReports(Request $request)
    {
        $v = validator($request->all(), [
            'monthSelection' => 'required|numeric|min:1|max:12',
            'yearSelection' => 'required|numeric',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $month = $request->monthSelection;
        $year = $request->yearSelection;

        $search = '%' . $request->search . '%';
        // $search = $request->search;

        //    return CompanyBillingReport::

        //    select([DB::raw('SUM(price) AS price_sum')])
        //          ->when($search, function($query, $search) { // Look here
        //             $query->having('price_sum' , 'like',[$search]);
        //         })

        // select(DB::raw('SUM(price) AS price_sum'))
        // ->join('company_line_subscriptions_to_reports as csr', 'company_billing_reports.id', 'csr.billing_report_id')
        // ->join('company_line_subscription_purchases as cs', 'csr.line_subscription_purchase_id', 'cs.id')
        // ->groupBy('company_billing_reports.id')
        // ->having('price_sum','like',$search)
        // ->get();

        $CompanyBillingReport = CompanyBillingReport::select('company_billing_reports.*', DB::raw('SUM(price) AS price_sum'))->join('company_line_subscriptions_to_reports as csr', 'company_billing_reports.id', 'csr.billing_report_id')
            ->join('company_line_subscription_purchases as cs', 'csr.line_subscription_purchase_id', 'cs.id')->groupBy('company_billing_reports.id')->with(['subscription_purchases.sub_purchase', 'office' => function ($q) {
                $q->whereActive(true);

                // $q->where(function($query)
                // {
                //     $query->whereParentId(null);
                //     $query->orWhere('billed_separately', 1);
                // });
            }])
            ->where('report_year', $year)->where('report_month', $month);

        if ($request->search) {
            $search = '%' . $request->search . '%';

            // $CompanyBillingReport->having('price_sum','like' ,$search);

            $ids = PocomosCompanyOffice::where('name', 'like', $search)->pluck('id');

            $CompanyBillingReport->where(function ($query) use ($search, $ids) {
                $query
                    ->whereIn('company_billing_reports.office_id', $ids)
                    ->orWhere(DB::raw('((active_customer_count * price_per_active_customer) / 100)'), 'like', $search)
                    ->orWhere(DB::raw('((active_user_count * price_per_active_user) / 100)'), 'like', $search)
                    ->orWhere('sent_sms_count', 'like', $search)
                    ->orWhere('received_sms_count', 'like', $search)
                    ->orWhere(DB::raw('((sent_sms_count + received_sms_count) * price_per_sent_sms) + phone_number_price'), 'like', $search)
                    ->orWhere(DB::raw('(((sent_sms_count + received_sms_count) * price_per_sent_sms) + phone_number_price) / 100'), 'like', $search)

                    // ->orWhere('price_sum', 'like' ,$search)
                    // ->orWhere(DB::raw('price_sum'), 'like' ,$search)

                    // ->having('price_sum','like' ,$search)
                    ->orWhere(DB::raw('(total_price) / 100'), 'like', $search);
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $CompanyBillingReport->count();
        $CompanyBillingReport->skip($perPage * ($page - 1))->take($perPage);

        $CompanyBillingReport = $CompanyBillingReport->get();

        $data = [
            'CompanyBillingReport' => $CompanyBillingReport,
            'count' => $count
        ];

        return $this->sendResponse(true, 'Office Billing Reports Details.', $data);
    }

    /**Regenerate report date for office */
    public function regenerateReportDate(Request $request)
    {
        $v = validator($request->all(), [
            'month' => 'required|numeric|min:1|max:12',
            'year' => 'required|numeric',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        DB::beginTransaction();

        try {
            $billingDate = $request->year . '-' . $request->month . '-01';

            $notifyAdminUsers = true;
            $offices = DB::select(DB::raw("SELECT o.*
                FROM pocomos_company_offices AS o
                LEFT JOIN pocomos_company_offices AS p ON o.parent_id = p.id
                WHERE (o.active = true AND o.parent_id IS NULL) OR ('o.active = true AND p.active = true')"));

            foreach ($offices as $office) {
                $office = PocomosCompanyOffice::findOrFail($request->office_id);
                $officeBillingProfile = CompanyBillingProfile::where('office_id', $request->office_id)->where('active', true)->first();

                if (!$officeBillingProfile) {
                    $officeBillingProfile = new CompanyBillingProfile();
                    $officeBillingProfile->office_id = $request->office_id;
                    $officeBillingProfile->save();
                }
                $this->updateOfficeBillingProfileNumbers($officeBillingProfile);

                $isParent = $office->parent_id === null;
                $isGrownup = $office->billed_separately;

                if ($isParent || $isGrownup) {
                    $args['id'] = $office->id;
                    $args['billingDate'] = $billingDate;
                    OfficeBillingReportJob::dispatch($args);
                }
            }

            if ($notifyAdminUsers) {
                $this->notifyAdminUsersWithBillingUpdates();
            }
            DB::commit();
        } catch (\Exception $e) {
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
            DB::rollback();
        }
        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Report date regenerated']));
    }
}
