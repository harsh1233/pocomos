<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\Billing\Items\CompanyLineSubscription;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class LineSubscriptionController extends Controller
{
    use Functions;

    /**
     * API for list of Line Subscription
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $CompanyLineSubscription = CompanyLineSubscription::orderBy('id', 'desc')->where('active', 1);

        if ($request->search) {
            $CompanyLineSubscription->where(function ($CompanyLineSubscription) use ($request) {
                $CompanyLineSubscription->where('id', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('price', 'like', '%' . $request->search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $CompanyLineSubscription->count();
        $CompanyLineSubscription->skip($perPage * ($page - 1))->take($perPage);

        $CompanyLineSubscription = $CompanyLineSubscription->get();

        return $this->sendResponse(true, 'List of Line Subscription.', [
            'company_line_subscriptions' => $CompanyLineSubscription,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Line Subscription
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $CompanyLineSubscription = CompanyLineSubscription::find($id);
        if (!$CompanyLineSubscription) {
            return $this->sendResponse(false, 'Line Subscription Not Found');
        }
        return $this->sendResponse(true, 'Line Subscription details.', $CompanyLineSubscription);
    }

    /**
     * API for create of Line Subscription
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
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('name', 'description', 'price') + ['active' => 1];

        $CompanyLineSubscription =  CompanyLineSubscription::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Line Subscription created successfully.', $CompanyLineSubscription);
    }

    /**
     * API for update of Line Subscription
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'linesubscription_id' => 'required|exists:company_line_subscriptions,id',
            'name' => 'required',
            'description' => 'required',
            'price' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $CompanyLineSubscription = CompanyLineSubscription::find($request->linesubscription_id);

        if (!$CompanyLineSubscription) {
            return $this->sendResponse(false, 'Line Subscription not found.');
        }

        $CompanyLineSubscription->update(
            $request->only('name', 'description', 'price')
        );

        return $this->sendResponse(true, 'Line Subscription updated successfully.', $CompanyLineSubscription);
    }

    /**
     * API for Line Subscription.
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $CompanyLineSubscription = CompanyLineSubscription::find($id);
        if (!$CompanyLineSubscription) {
            return $this->sendResponse(false, 'Line Subscription not found.');
        }

        $update_data['active'] = 0;

        $CompanyLineSubscription->update($update_data);

        return $this->sendResponse(true, 'Line Subscription deleted successfully.');
    }
}
