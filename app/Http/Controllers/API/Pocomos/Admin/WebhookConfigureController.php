<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosWebhook;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class WebhookConfigureController extends Controller
{
    use Functions;

    /**
     * API for list of WebHook
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosWebhook = PocomosWebhook::where('office_id', $request->office_id)->orderBy('id', 'desc')
            ->get();

        return $this->sendResponse(true, 'List of WebHook.', $PocomosWebhook);
    }

    /**
     * API for create of WebHook
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'webhook_url' => 'required',
            'send_on' => 'required|in:Job Completion,Sale Completed',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('webhook_url', 'send_on', 'office_id') + ['active' => 1];

        $PocomosWebhook =  PocomosWebhook::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'WebHook created successfully.', $PocomosWebhook);
    }

    /**
     * API for WebHook.
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request)
    {
        $v = validator($request->all(), [
            'webhook_id' => 'required|exists:pocomos_webhooks,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosWebhook = PocomosWebhook::find($request->webhook_id);
        if (!$PocomosWebhook) {
            return $this->sendResponse(false, 'WebHook not found.');
        }

        $PocomosWebhook->delete();

        return $this->sendResponse(true, 'WebHook deleted successfully.');
    }
}
