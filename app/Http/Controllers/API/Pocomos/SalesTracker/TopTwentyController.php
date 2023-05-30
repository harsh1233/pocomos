<?php

namespace App\Http\Controllers\API\Pocomos\SalesTracker;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTopTwenty;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class TopTwentyController extends Controller
{
    use Functions;

    /**
     * API for list of Office Bonuse
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $dateRange = $request->date_range ? $request->date_range : 'today';

        $PocomosTopTwenty = PocomosTopTwenty::whereDateRange($dateRange)->whereOfficeId($request->office_id)->whereActive(true)->first();

        $data = [
            'serviced_rookies'      => isset($PocomosTopTwenty->serviced_rookies) ? $PocomosTopTwenty->serviced_rookies : null ,
            'scheduled_rookies'     => isset($PocomosTopTwenty->scheduled_rookies) ? $PocomosTopTwenty->scheduled_rookies : null ,
            'serviced_veterans'     => isset($PocomosTopTwenty->serviced_veterans) ? $PocomosTopTwenty->serviced_veterans : null ,
            'scheduled_veterans'    => isset($PocomosTopTwenty->scheduled_veterans) ? $PocomosTopTwenty->scheduled_veterans : null ,
            'rev_rookies'           => isset($PocomosTopTwenty->rev_rookies) ? $PocomosTopTwenty->rev_rookies : null ,
            'rev_veterans'          => isset($PocomosTopTwenty->rev_veterans) ? $PocomosTopTwenty->rev_veterans : null ,
            'svd_rev_rookies'       => isset($PocomosTopTwenty->svd_rev_rookies) ? $PocomosTopTwenty->svd_rev_rookies : null ,
            'svd_rev_veterans'      => isset($PocomosTopTwenty->svd_rev_veterans) ? $PocomosTopTwenty->svd_rev_veterans : null ,
        ];

        return $this->sendResponse(true, 'Top Twenty', $data);
    }
}
