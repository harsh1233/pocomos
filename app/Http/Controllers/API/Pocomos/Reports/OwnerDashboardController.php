<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosStaticDashboardState;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class OwnerDashboardController extends Controller
{
    use Functions;

    /**
     * API for list of Admin Sender
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function getData(Request $request)
    {
        $dashboardState = PocomosStaticDashboardState::whereOfficeId($request->office_id)
            ->whereActive(1)->get()->take(1);

        // return $dashboardState;

        if (count($dashboardState) == 0) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Dashboard state']));
        }

        $dashboardState_count = $dashboardState->count();

        $dashboardState = $dashboardState->map(function ($dashboardState) {
            $dashboardState->revenue_last_twelve = unserialize($dashboardState->revenue_last_twelve);
            $dashboardState->new_customers_last_twelve = unserialize($dashboardState->new_customers_last_twelve);
            $dashboardState->jobs_completed_last_twelve = unserialize($dashboardState->jobs_completed_last_twelve);
            $dashboardState->cancellations_last_twelve = unserialize($dashboardState->cancellations_last_twelve);
            $dashboardState->accounts_receivable_last_twelve = unserialize($dashboardState->accounts_receivable_last_twelve);
            $dashboardState->reservices_last_twelve = unserialize($dashboardState->reservices_last_twelve);
            $dashboardState->revenue_by_service_type_ytd = unserialize($dashboardState->revenue_by_service_type_ytd);
            $dashboardState->revenue_by_payment_type_ytd = unserialize($dashboardState->revenue_by_payment_type_ytd);
            $dashboardState->revenue_by_marketing_type_ytd = unserialize($dashboardState->revenue_by_marketing_type_ytd);
            $dashboardState->accounts_receivable_by_age_ytd = unserialize($dashboardState->accounts_receivable_by_age_ytd);
            $dashboardState->upcoming_revenue_by_month = unserialize($dashboardState->upcoming_revenue_by_month);
            $dashboardState->upcoming_jobs_by_month = unserialize($dashboardState->upcoming_jobs_by_month);
            $dashboardState->customers_by_account_type = unserialize($dashboardState->customers_by_account_type);
            $dashboardState->revenue_by_customers_account_type_ytd = unserialize($dashboardState->revenue_by_customers_account_type_ytd);
            $dashboardState->average_job_value_by_marketing_type_ytd = unserialize($dashboardState->average_job_value_by_marketing_type_ytd);
            $dashboardState->average_job_value_by_service_type_ytd = unserialize($dashboardState->average_job_value_by_service_type_ytd);
            $dashboardState->average_job_value_by_agreement_type_ytd = unserialize($dashboardState->average_job_value_by_agreement_type_ytd);
            $dashboardState->average_job_value_by_county_ytd = unserialize($dashboardState->average_job_value_by_county_ytd);
            $dashboardState->average_job_value_by_job_type_ytd = unserialize($dashboardState->average_job_value_by_job_type_ytd);
            $dashboardState->cancellations_by_reason_ytd = unserialize($dashboardState->cancellations_by_reason_ytd);
            $dashboardState->cancellations_by_account_type_ytd = unserialize($dashboardState->cancellations_by_account_type_ytd);
            $dashboardState->cancellations_by_marketing_type_ytd = unserialize($dashboardState->cancellations_by_marketing_type_ytd);
            $dashboardState->cancellations_by_agreement_type_ytd = unserialize($dashboardState->cancellations_by_agreement_type_ytd);

            return $dashboardState;
        });


        // revenue_last_twelve
        $i = 0;
        foreach ($dashboardState[0]->revenue_last_twelve as $q) {
            $months = array_keys($dashboardState[0]->revenue_last_twelve);

            $revenue_last_twelve[$i]['month'] = $months[$i];
            $revenue_last_twelve[$i]['value'] = $q;
            $i++;
        }

        // new_customers_last_twelve
        $i = 0;
        foreach ($dashboardState[0]->new_customers_last_twelve as $q) {
            $months = array_keys($dashboardState[0]->new_customers_last_twelve);

            $new_customers_last_twelve[$i]['month'] = $months[$i];
            $new_customers_last_twelve[$i]['value'] = $q;
            $i++;
        }

        // jobs_completed_last_twelve
        $i = 0;
        foreach ($dashboardState[0]->jobs_completed_last_twelve as $q) {
            $months = array_keys($dashboardState[0]->jobs_completed_last_twelve);

            $jobs_completed_last_twelve[$i]['month'] = $months[$i];
            $jobs_completed_last_twelve[$i]['value'] = $q;
            $i++;
        }

        // cancellations_last_twelve
        $i = 0;
        foreach ($dashboardState[0]->cancellations_last_twelve as $q) {
            $months = array_keys($dashboardState[0]->cancellations_last_twelve);

            $cancellations_last_twelve[$i]['month'] = $months[$i];
            $cancellations_last_twelve[$i]['value'] = $q;
            $i++;
        }

        //accounts_receivable_last_twelve
        $i = 0;
        foreach ($dashboardState[0]->accounts_receivable_last_twelve as $q) {
            $months = array_keys($dashboardState[0]->accounts_receivable_last_twelve);

            $accounts_receivable_last_twelve[$i]['month'] = $months[$i];
            $accounts_receivable_last_twelve[$i]['value'] = $q;
            $i++;
        }

        //reservices_last_twelve
        $i = 0;
        foreach ($dashboardState[0]->reservices_last_twelve as $q) {
            $months = array_keys($dashboardState[0]->reservices_last_twelve);

            $reservices_last_twelve[$i]['month'] = $months[$i];
            $reservices_last_twelve[$i]['value'] = $q;
            $i++;
        }

        //revenue_by_service_type_ytd
        $i = 0;
        foreach ($dashboardState[0]->revenue_by_service_type_ytd as $q) {
            $names = array_keys($dashboardState[0]->revenue_by_service_type_ytd);

            $revenue_by_service_type_ytd[$i]['name'] = $names[$i];
            $revenue_by_service_type_ytd[$i]['value'] = $q;
            $i++;
        }

        //revenue_by_payment_type_ytd
        $i = 0;
        foreach ($dashboardState[0]->revenue_by_payment_type_ytd as $q) {
            $names = array_keys($dashboardState[0]->revenue_by_payment_type_ytd);

            $revenue_by_payment_type_ytd[$i]['name'] = $names[$i];
            $revenue_by_payment_type_ytd[$i]['value'] = $q;
            $i++;
        }

        //revenue_by_marketing_type_ytd
        $i = 0;
        foreach ($dashboardState[0]->revenue_by_marketing_type_ytd as $q) {
            $names = array_keys($dashboardState[0]->revenue_by_marketing_type_ytd);

            $revenue_by_marketing_type_ytd[$i]['name'] = $names[$i];
            $revenue_by_marketing_type_ytd[$i]['value'] = $q;
            $i++;
        }

        //accounts_receivable_by_age_ytd
        $i = 0;
        foreach ($dashboardState[0]->accounts_receivable_by_age_ytd as $q) {
            $months = array_keys($dashboardState[0]->accounts_receivable_by_age_ytd);

            $accounts_receivable_by_age_ytd[$i]['days'] = $months[$i];
            $accounts_receivable_by_age_ytd[$i]['value'] = $q;
            $i++;
        }
        //upcoming_revenue_by_month
        $i = 0;
        foreach ($dashboardState[0]->upcoming_revenue_by_month as $q) {
            $months = array_keys($dashboardState[0]->upcoming_revenue_by_month);

            $upcoming_revenue_by_month[$i]['month'] = $months[$i];
            $upcoming_revenue_by_month[$i]['value'] = $q;
            $i++;
        }

        //upcoming_jobs_by_month
        $i = 0;
        foreach ($dashboardState[0]->upcoming_jobs_by_month as $q) {
            $months = array_keys($dashboardState[0]->upcoming_jobs_by_month);

            $upcoming_jobs_by_month[$i]['month'] = $months[$i];
            $upcoming_jobs_by_month[$i]['value'] = $q;
            $i++;
        }

        //customers_by_account_type
        if ($dashboardState[0]->customers_by_account_type) {
            $i = 0;
            foreach ($dashboardState[0]->customers_by_account_type as $q) {
                $months = array_keys($dashboardState[0]->customers_by_account_type);

                $customers_by_account_type[$i]['month'] = $months[$i];
                $customers_by_account_type[$i]['value'] = $q;
                $i++;
            }
        }

        if ($dashboardState[0]->average_job_value_by_marketing_type_ytd) {
            //average_job_value_by_marketing_type_ytd
            $i = 0;
            foreach ($dashboardState[0]->average_job_value_by_marketing_type_ytd as $q) {
                $months = array_keys($dashboardState[0]->average_job_value_by_marketing_type_ytd);

                $average_job_value_by_marketing_type_ytd[$i]['month'] = $months[$i];
                $average_job_value_by_marketing_type_ytd[$i]['value'] = $q;
                $i++;
            }
        }

        if ($dashboardState[0]->average_job_value_by_service_type_ytd) {
            //average_job_value_by_service_type_ytd
            $i = 0;
            foreach ($dashboardState[0]->average_job_value_by_service_type_ytd as $q) {
                $months = array_keys($dashboardState[0]->average_job_value_by_service_type_ytd);

                $average_job_value_by_service_type_ytd[$i]['month'] = $months[$i];
                $average_job_value_by_service_type_ytd[$i]['value'] = $q;
                $i++;
            }
        }

        if ($dashboardState[0]->average_job_value_by_agreement_type_ytd) {
            //average_job_value_by_agreement_type_ytd
            $i = 0;
            foreach ($dashboardState[0]->average_job_value_by_agreement_type_ytd as $q) {
                $months = array_keys($dashboardState[0]->average_job_value_by_agreement_type_ytd);

                $average_job_value_by_agreement_type_ytd[$i]['month'] = $months[$i];
                $average_job_value_by_agreement_type_ytd[$i]['value'] = $q;
                $i++;
            }
        }

        if ($dashboardState[0]->average_job_value_by_county_ytd) {
            //average_job_value_by_county_ytd
            $i = 0;
            foreach ($dashboardState[0]->average_job_value_by_county_ytd as $q) {
                $months = array_keys($dashboardState[0]->average_job_value_by_county_ytd);

                $average_job_value_by_county_ytd[$i]['month'] = $months[$i];
                $average_job_value_by_county_ytd[$i]['value'] = $q;
                $i++;
            }
        }

        if ($dashboardState[0]->average_job_value_by_job_type_ytd) {
            //average_job_value_by_job_type_ytd
            $i = 0;
            foreach ($dashboardState[0]->average_job_value_by_job_type_ytd as $q) {
                $months = array_keys($dashboardState[0]->average_job_value_by_job_type_ytd);

                $average_job_value_by_job_type_ytd[$i]['month'] = $months[$i];
                $average_job_value_by_job_type_ytd[$i]['value'] = $q;
                $i++;
            }
        }

        if ($dashboardState[0]->cancellations_by_reason_ytd) {
            //cancellations_by_reason_ytd
            $i = 0;
            foreach ($dashboardState[0]->cancellations_by_reason_ytd as $q) {
                $months = array_keys($dashboardState[0]->cancellations_by_reason_ytd);

                $cancellations_by_reason_ytd[$i]['month'] = $months[$i];
                $cancellations_by_reason_ytd[$i]['value'] = $q;
                $i++;
            }
        }

        if ($dashboardState[0]->cancellations_by_account_type_ytd) {
            //cancellations_by_account_type_ytd
            $i = 0;
            foreach ($dashboardState[0]->cancellations_by_account_type_ytd as $q) {
                $months = array_keys($dashboardState[0]->cancellations_by_account_type_ytd);

                $cancellations_by_account_type_ytd[$i]['month'] = $months[$i];
                $cancellations_by_account_type_ytd[$i]['value'] = $q;
                $i++;
            }
        }

        if ($dashboardState[0]->cancellations_by_marketing_type_ytd) {
            //cancellations_by_marketing_type_ytd
            $i = 0;
            foreach ($dashboardState[0]->cancellations_by_marketing_type_ytd as $q) {
                $months = array_keys($dashboardState[0]->cancellations_by_marketing_type_ytd);

                $cancellations_by_marketing_type_ytd[$i]['month'] = $months[$i];
                $cancellations_by_marketing_type_ytd[$i]['value'] = $q;
                $i++;
            }
        }

        if ($dashboardState[0]->cancellations_by_agreement_type_ytd) {
            //cancellations_by_agreement_type_ytd
            $i = 0;
            foreach ($dashboardState[0]->cancellations_by_agreement_type_ytd as $q) {
                $months = array_keys($dashboardState[0]->cancellations_by_agreement_type_ytd);

                $cancellations_by_agreement_type_ytd[$i]['month'] = $months[$i];
                $cancellations_by_agreement_type_ytd[$i]['value'] = $q;
                $i++;
            }
        }

        // $data = [
        //         'dashboard_state' => $dashboardState,
        //         'count' => $dashboardState_count
        //     ];

        return $this->sendResponse(true, 'Dashboard states', [
            'dashboard_state' => $dashboardState ?? [],
            'count' => $dashboardState_count ?? [],
            'revenue_last_twelve' => $revenue_last_twelve ?? [],
            'new_customers_last_twelve' => $new_customers_last_twelve ?? [],
            'jobs_completed_last_twelve' => $jobs_completed_last_twelve ?? [],
            'cancellations_last_twelve' => $cancellations_last_twelve ?? [],
            'accounts_receivable_last_twelve'   => $accounts_receivable_last_twelve ?? [],
            'reservices_last_twelve'    => $reservices_last_twelve ?? [],
            'revenue_by_service_type_last_twelve_months'    => $revenue_by_service_type_ytd ?? [],
            'revenue_by_payment_type_last_twelve_months'    => $revenue_by_payment_type_ytd ?? [],
            'revenue_by_marketing_type_last_twelve_months'    => $revenue_by_marketing_type_ytd ?? [],
            'accounts_receivable_by_age'    => $accounts_receivable_by_age_ytd ?? [],
            'upcoming_revenue_by_month' => $upcoming_revenue_by_month ?? [],
            'upcoming_jobs_by_month'    => $upcoming_jobs_by_month ?? [],
            'customers_by_account_type'    => $customers_by_account_type ?? [],
            'revenue_by_customers_account_type_ytd'    => $revenue_by_customers_account_type_ytd ?? [],
            'average_job_value_by_marketing_type_ytd'    => $average_job_value_by_marketing_type_ytd ?? [],
            'average_job_value_by_service_type_ytd'    => $average_job_value_by_service_type_ytd ?? [],
            'average_job_value_by_agreement_type_ytd'    => $average_job_value_by_agreement_type_ytd ?? [],
            'average_job_value_by_county_ytd'    => $average_job_value_by_county_ytd ?? [],
            'average_job_value_by_job_type_ytd'    => $average_job_value_by_job_type_ytd ?? [],
            'cancellations_by_reason_ytd'    => $cancellations_by_reason_ytd ?? [],
            'cancellations_by_account_type_ytd'    => $cancellations_by_account_type_ytd ?? [],
            'cancellations_by_marketing_type_ytd'    => $cancellations_by_marketing_type_ytd ?? [],
            'cancellations_by_agreement_type_ytd'    => $cancellations_by_agreement_type_ytd ?? [],
        ]);
    }

    /**
     * API for details of Admin Sender
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    // public function get($id)
    // {
    //     $PocomosPhoneNumber = PocomosPhoneNumber::find($id);
    //     if (!$PocomosPhoneNumber) {
    //         return $this->sendResponse(false, 'Admin Sender Not Found');
    //     }
    //     return $this->sendResponse(true, 'Admin Sender details.', $PocomosPhoneNumber);
    // }
}
