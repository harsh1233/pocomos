<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosStandardPrice;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class StandardPriceController extends Controller
{
    use Functions;

    /**
     * API for list of Standard Price
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $PocomosStandardPrice = PocomosStandardPrice::where('active', 1)->orderBy('id', 'desc');

        $status = 10;
        if (stripos('enabled', $request->search)  !== false) {
            $status = 1;
        } elseif (stripos('disabled', $request->search) !== false) {
            $status = 0;
        }

        if ($request->search) {
            $PocomosStandardPrice->where(function ($PocomosStandardPrice) use ($request, $status) {
                $PocomosStandardPrice->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('price', 'like', '%' . $request->search . '%')
                    ->orWhere('min_customer_number', 'like', '%' . $request->search . '%')
                    ->orWhere('max_customer_number', 'like', '%' . $request->search . '%')
                    ->orWhere('enabled', 'like', '%' . $status . '%')
                    ->orWhere('date_created', 'like', '%' . $request->search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosStandardPrice->count();
        $PocomosStandardPrice->skip($perPage * ($page - 1))->take($perPage);

        $PocomosStandardPrice = $PocomosStandardPrice->get();

        return $this->sendResponse(true, 'List of Standard Price.', [
            'standard_prices' => $PocomosStandardPrice,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Standard Price
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosStandardPrice = PocomosStandardPrice::find($id);
        if (!$PocomosStandardPrice) {
            return $this->sendResponse(false, 'Standard Price Not Found');
        }
        return $this->sendResponse(true, 'Standard Price details.', $PocomosStandardPrice);
    }

    /**
     * API for create of Standard Price
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'price' => 'required',
            'min_customer_number' => 'required',
            'max_customer_number' => 'required',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('name', 'price', 'min_customer_number', 'max_customer_number', 'enabled') + ['active' => 1];

        $PocomosStandardPrice =  PocomosStandardPrice::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Standard Price created successfully.', $PocomosStandardPrice);
    }

    /**
     * API for update of Standard Price
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'standard_price_id' => 'required|exists:pocomos_standard_pricing,id',
            'name' => 'required',
            'price' => 'required',
            'min_customer_number' => 'required',
            'max_customer_number' => 'required',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosStandardPrice = PocomosStandardPrice::find($request->standard_price_id);

        if (!$PocomosStandardPrice) {
            return $this->sendResponse(false, 'Standard Price not found.');
        }

        $PocomosStandardPrice->update(
            $request->only('name', 'price', 'min_customer_number', 'max_customer_number', 'enabled')
        );

        return $this->sendResponse(true, 'Standard Price updated successfully.', $PocomosStandardPrice);
    }

    /* API for changeStatus of  Standard Price */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'standard_price_id' => 'required|exists:pocomos_standard_pricing,id',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosStandardPrice = PocomosStandardPrice::find($request->standard_price_id);

        if (!$PocomosStandardPrice) {
            return $this->sendResponse(false, 'Unable to find Standard Price');
        }

        $PocomosStandardPrice->update([
            'enabled' => $request->enabled
        ]);

        return $this->sendResponse(true, 'Status changed successfully.', $PocomosStandardPrice);
    }
}
