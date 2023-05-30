<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\Billing\Purchase\CompanyLineSubscriptionPurchase;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\Billing\Items\CompanyLineSubscription;

class LineSubscriptionPurchasingController extends Controller
{
    use Functions;

    /**
     * API for list of Line Subscription Purchases
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = CompanyLineSubscriptionPurchase::where('office_id', $request->office_id)
                ->where('active', 1);


        if ($request->search) {
            $search = '%'.$request->search.'%';

            $query->where(function ($query) use ($search, $date) {
                $query->where('id', 'like', $search)
                ->orWhere('name', 'like', $search)
                ->orWhere('price', 'like', $search)
                ->orWhere('sub_start_date', 'like', $search);
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $query->count();
        $query->skip($perPage * ($page - 1))->take($perPage);

        $CompanyLineSubscriptionPurchase = $query->get();

        return $this->sendResponse(true, 'List of Line Subscription Purchases.', [
            'company_subscriptions' => $CompanyLineSubscriptionPurchase,
            'count' => $count,
        ]);
    }


    /**
     * API for list of Line Subscription Purchases
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function linesubscriptionlist(Request $request)
    {
        $CompanyLineSubscriptionPurchase = CompanyLineSubscription::where('active', 1)->orderBy('name', 'ASC')->get();

        return $this->sendResponse(true, 'List of Line Subscription Purchases.', $CompanyLineSubscriptionPurchase);
    }

    /**
     * API for list of office
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function getData(Request $request)
    {
        $offices = PocomosCompanyOffice::all();

        return $this->sendResponse(true, 'List of all offices.', $offices);
    }

    public function get(Request $request)
    {
        $v = validator($request->all(), [
            'line_subscription_id' => 'required|exists:company_line_subscriptions,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = CompanyLineSubscription::find($request->line_subscription_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find Line Subscription Purchases.');
        }

        return $this->sendResponse(true, 'Line Subscription Purchase.', $PocomosCompanyOffice);
    }

    /**
     * API for create of Line Subscription Purchases
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'description' => 'required',
            'price' => 'nullable',
            'line_subscription_id' => 'required|exists:company_line_subscriptions,id',
            'kill_at_EOM' => 'boolean|required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::find($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $input_details = $request->only('name', 'description', 'office_id', 'line_subscription_id', 'kill_at_EOM') + ['active' => 1, 'price' => $request->price];

        $input_details['sub_start_date'] = date("Y-m-d H:i:s");

        $CompanyLineSubscriptionPurchase =  CompanyLineSubscriptionPurchase::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Line Subscription Purchases created successfully.', $CompanyLineSubscriptionPurchase);
    }

    /**
     * API for update of Line Subscription Purchases
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'line_purchase_id' => 'required|exists:company_line_subscription_purchases,id',
            'name' => 'required',
            'description' => 'required',
            'price' => 'nullable',
            'line_subscription_id' => 'required|exists:company_line_subscriptions,id',
            'kill_at_EOM' => 'boolean|required',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCompanyOffice = PocomosCompanyOffice::find($request->office_id);
        if (!$PocomosCompanyOffice) {
            return $this->sendResponse(false, 'Unable to find the Office.');
        }

        $CompanyLineSubscriptionPurchase = CompanyLineSubscriptionPurchase::find($request->line_purchase_id);

        if (!$CompanyLineSubscriptionPurchase) {
            return $this->sendResponse(false, 'Line Subscription Purchases not found.');
        }

        $update_data = $request->only('name', 'description', 'office_id', 'line_subscription_id', 'kill_at_EOM') + ['price' => $request->price];

        $input_details['sub_start_date'] = date("Y-m-d H:i:s");

        $CompanyLineSubscriptionPurchase->update($update_data);

        return $this->sendResponse(true, 'Line Subscription Purchases updated successfully.', $CompanyLineSubscriptionPurchase);
    }

    /**
     * API for Line Subscription Purchases.
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request)
    {
        $v = validator($request->all(), [
            'line_purchase_id' => 'required|exists:company_line_subscription_purchases,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $CompanyLineSubscriptionPurchase = CompanyLineSubscriptionPurchase::find($request->line_purchase_id);

        if (!$CompanyLineSubscriptionPurchase) {
            return $this->sendResponse(false, 'Line Subscription Purchases not found.');
        }

        $update_data['active'] = 0;

        $CompanyLineSubscriptionPurchase->update($update_data);

        return $this->sendResponse(true, 'Line Subscription Purchases deleted successfully.');
    }
}
