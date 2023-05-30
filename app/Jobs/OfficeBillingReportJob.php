<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Pocomos\PocomosOfficeSetting;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\PocomosReportsOfficeState;
use App\Models\Pocomos\Billing\CompanyBillingReport;
use App\Models\Pocomos\Billing\CompanyBillingProfile;
use App\Models\Pocomos\Billing\CompanyLineSubscriptionsToReport;
use App\Models\Pocomos\Billing\Items\CompanyLineSubscriptionHistory;
use App\Models\Pocomos\Billing\Purchase\CompanyLineSubscriptionPurchase;

class OfficeBillingReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $args;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($args)
    {
        $this->args = $args;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("OfficeBillingReportJob Job Started");

        //defaults
        $dependants = [];
        $grownUps = [];
        $dependantOffices = [];
        $totalPrice = 0;
        $grownUpNumbers = [
            'users' => 0,
            'customers' => 0,
            'salespeople' => 0,
            'sms' => 0
        ];
        $phoneNumberPrice = config('constants.phoneNumberPrice');

        $officeId = $this->args['id'];
        $this->billingDate = new \DateTime($this->args['billingDate']);

        //Repos
        $officeBillingProfile = CompanyBillingProfile::whereOfficeId($officeId)->firstOrFail();
        $currentOfficeState = PocomosReportsOfficeState::whereOfficeId($officeId)->firstOrFail();

        $lineSubPurchaseRepo = CompanyLineSubscriptionPurchase::query();
        $office = $officeBillingProfile->office_details;

        $currentYear = $this->billingDate->format('Y');
        $currentMonth = $this->billingDate->format('m');
        $currentMonthLastDay = $this->billingDate->format('t');

        $thisMonthStart = \DateTime::createFromFormat('Y-m-d H:i:s', "$currentYear-$currentMonth-01 00:00:00");
        $thisMonthEnd = \DateTime::createFromFormat('Y-m-d H:i:s', "$currentYear-$currentMonth-$currentMonthLastDay 00:00:00");

        $officeBillingReport = CompanyBillingReport::whereOfficeId($officeId)->where('report_month', $currentMonth)->where('report_year', $currentYear)->latest('date_created')->first();

        if (!$officeBillingReport) {
            $officeBillingReport = new CompanyBillingReport();
            $officeBillingReport->office_id = $officeId;
            $officeBillingReport->report_month = $currentMonth;
            $officeBillingReport->report_year = $currentYear;
            $officeBillingReport->report_date = $thisMonthEnd;
            $officeBillingReport->received_sms_count = 0;
            $officeBillingReport->phone_number_price = 0;
            $officeBillingReport->save();
        }

        $isParent = $office->parent_id === null;
        $kids = $office->getChildOffices;

        foreach ($kids as $kid) {
            if ($kid->billed_separately) {
                $grownUps[] = $kid->id;
            } else {
                $dependants[] = $kid->id;
                $dependantOffices[] = $kid;
            }
        }

        $sentSMSCount = $this->getSMSCount([$officeId], $this->billingDate, /* inbound */ false);
        $receivedSMSCount = $this->getSMSCount([$officeId], $this->billingDate, /* inbound */ true);
        //move pricing to Profile

        $noGrownUps = empty($grownUps);

        if ($isParent && !$noGrownUps) {
            //Yes. Datas. shhh
            $sql = 'SELECT ROUND(AVG(os.customers)) AS `customers`, ROUND(AVG(os.users)) AS `users`, ROUND(AVG(os.salespeople)) AS `salespeople`
            FROM pocomos_reports_office_states os
            WHERE os.office_id IN ('.$this->convertArrayInStrings($grownUps).') AND os.type = \'Snapshot\' AND os.date_created BETWEEN "'.$this->billingDate->format('Y-m-d H:i:s').'" AND "'.$this->billingDate->format('Y-m-t 23:59:59').'"
            GROUP BY os.office_id';

            $grownUpDatas = DB::select(DB::raw($sql));

            foreach ($grownUpDatas as $grownUpData) {
                $grownUpNumbers['users'] = $grownUpNumbers['users'] + $grownUpData->users;
                $grownUpNumbers['salespeople'] = $grownUpNumbers['salespeople'] + $grownUpData->salespeople;
                $grownUpNumbers['customers'] = $grownUpNumbers['customers'] + $grownUpData->customers;
            }
            $grownUpNumbers['sms'] = $this->getSMSCount($grownUps, $this->billingDate);
            $sentSMSCount += $this->getSMSCount($dependants, $this->billingDate, /* inbound */ false);
            $receivedSMSCount += $this->getSMSCount($dependants, $this->billingDate, /* inbound */ true);
            //Now that we have grownUp stats - lets put them to good use, shall we?
        }

        //Apply DATAZ For Office.
        $officeBillingReport->active_user_count = $currentOfficeState->users - $grownUpNumbers['users'];
        $officeBillingReport->active_customer_count = $currentOfficeState->customers - $grownUpNumbers['customers'];
        $officeBillingReport->sales_user_count = $currentOfficeState->salespeople - $grownUpNumbers['salespeople'];
        $officeBillingReport->sent_sms_count = $sentSMSCount;
        $officeBillingReport->received_sms_count = $receivedSMSCount;

        $officeBillingReport->price_per_active_user = $officeBillingProfile->price_per_active_user;
        $officeBillingReport->price_per_sales_user = $officeBillingProfile->price_per_sales_user;
        $officeBillingReport->price_per_active_customer = $officeBillingProfile->price_per_active_customer;
        $officeBillingReport->price_per_sent_sms = $officeBillingProfile->price_per_sent_sms;

        $officeBillingReport->price_per_active_customer_override = $officeBillingProfile->price_per_active_customer_override;
        $officeBillingReport->price_per_active_user_override = $officeBillingProfile->price_per_active_user_override;
        $officeBillingReport->price_per_sales_user_override = $officeBillingProfile->price_per_sales_user_override;
        $officeBillingReport->price_per_sent_sms_override = $officeBillingProfile->price_per_sent_sms_override;

        //Calculating Prices for the active users and etc
        $totalPrice += ($officeBillingReport->active_user_count * $officeBillingReport->price_per_active_user);
        $totalPrice += ($officeBillingReport->active_customer_count * $officeBillingReport->price_per_active_customer);
        $totalPrice += ($officeBillingReport->sales_user_count * $officeBillingReport->price_per_sales_user);
        $allSms = $officeBillingReport->sent_sms_count + $officeBillingReport->received_sms_count;
        $totalPrice += ($allSms * $officeBillingReport->price_per_sent_sms);

        $officeConfiguration = PocomosOfficeSetting::whereOfficeId($office->id)->latest('date_created')->first();

        // Add phone number price
        if ($officeConfiguration && $officeConfiguration->sender_phone_details) {
            $officeBillingReport->phone_number_price = $phoneNumberPrice;
            $totalPrice += $phoneNumberPrice;
        } else {
            $officeBillingReport->phone_number_price = 0;
        }

        if ($isParent) {
            //Papa pays for his kiddos.
            $lineOffices = array_merge([$office], $dependantOffices);
        } else {
            //Papa is proud of his grown up children and isn't paying for them.
            $lineOffices = [$office];
        }
        $lineOfficesIds = array();
        foreach ($lineOffices as $value) {
            $lineOfficesIds[] = $value->id;
        }


        $subs = $lineSubPurchaseRepo->where('active', true)
        ->whereIn('office_id', $lineOfficesIds)
        ->where('sub_start_date', '<=', $thisMonthEnd)
        ->where('sub_end_date', null)
        ->orWhere('sub_end_date', '>=', $thisMonthEnd)
        ->get();

        $officeBillingReport = $officeBillingReport->save();

        $officeBillingReport = CompanyBillingReport::whereOfficeId($officeId)->where('report_month', $currentMonth)->where('report_year', $currentYear)->latest('date_created')->first();

        foreach ($subs as $sub) {
            $lineSub = $sub;
            $totalPrice += $lineSub->price;

            CompanyLineSubscriptionsToReport::create(['line_subscription_purchase_id' => $lineSub->id, 'billing_report_id' => $officeBillingReport->id]);

            $lineSubscriptionHistory = new CompanyLineSubscriptionHistory();
            $lineSubscriptionHistory->office_id = $lineSub->office_id;
            $lineSubscriptionHistory->name = $lineSub->name;
            $lineSubscriptionHistory->description = $lineSub->description;
            $lineSubscriptionHistory->price = $lineSub->price;
            $lineSubscriptionHistory->save();

            if ($lineSub->kill_at_EOM && $this->billingDate->format('Y-m-t') === date('Y-m-t')) {
                $lineSub->sub_end_date = $thisMonthEnd;
                $lineSub->save();
            }
        }

        if ($officeBillingProfile->discount_type === config('constants.FLAT')) {
            $totalPrice = $totalPrice - $officeBillingProfile->discount_amount;
        } elseif ($officeBillingProfile->discount_type === config('constants.PERCENTAGE')) {
            $discAmount = $officeBillingProfile->discount_amount;
            if (!($discAmount > 100)) {
                $totalPrice = $totalPrice - ($totalPrice * ($discAmount / 100));
            }
        }

        $officeBillingReport->total_price = $totalPrice;
        $officeBillingReport->comment = $officeBillingProfile->comment;
        $officeBillingReport->save();

        Log::info("OfficeBillingReportJob Job End");
    }

    /** Get MS count based on office */
    private function getSMSCount($offices, $billingDate, $inbound = null)
    {
        $offices = $this->convertArrayInStrings($offices);
        $sql = 'SELECT COUNT(u.id) as SMSCOUNT
            FROM pocomos_sms_usage u
            JOIN pocomos_company_offices o ON u.office_id = o.id
            WHERE u.office_id IN ('.$offices.') AND u.date_created BETWEEN "'.$billingDate->format('Y-m-d H:i:s').'" AND "'.$billingDate->format('Y-m-t 23:59:59').'"';

        if ($inbound !== null) {
            $inbound = !$inbound ? 0 : 1;
            $sql .= ' AND u.inbound = '.$inbound.'';
        }
        $sql .= ' GROUP BY o.id';

        $finalCount = 0;

        $counts = DB::select(DB::raw($sql));

        foreach ($counts as $count) {
            $finalCount += $count->SMSCOUNT;
        }
        return $finalCount;
    }
}
