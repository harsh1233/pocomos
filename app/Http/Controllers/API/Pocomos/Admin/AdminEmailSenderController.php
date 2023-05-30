<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosEmailSender;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class AdminEmailSenderController extends Controller
{
    use Functions;

    /**
     * API for list of Admin Sender
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
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosEmailSender = PocomosEmailSender::orderBy('id', 'desc');

        $status = 10;
        if (stripos('active', $request->search)  !== false) {
            $status = 1;
        } elseif (stripos('inactive', $request->search) !== false) {
            $status = 0;
        }

        if ($request->search) {
            $PocomosEmailSender->where('address', 'like', '%' . $request->search . '%')
                ->orWhere('active', 'like', '%' . $status . '%');
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosEmailSender->count();
        $PocomosEmailSender->skip($perPage * ($page - 1))->take($perPage);
        $PocomosEmailSender = $PocomosEmailSender->get();

        return $this->sendResponse(true, 'List of Admin Sender.', [
            'senders' => $PocomosEmailSender,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Admin Sender
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosEmailSender = PocomosEmailSender::find($id);
        if (!$PocomosEmailSender) {
            return $this->sendResponse(false, 'Admin Sender Not Found');
        }
        return $this->sendResponse(true, 'Admin Sender details.', $PocomosEmailSender);
    }

    /**
     * API for create of Admin Sender
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'address' => 'required|email',
            'active' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('address', 'active');

        $PocomosEmailSender =  PocomosEmailSender::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Admin Sender created successfully.', $PocomosEmailSender);
    }

    /**
     * API for update of Admin Sender
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'sms_email_sender_id' => 'required|exists:pocomos_email_senders,id',
            'address' => 'required|email',
            'active' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosEmailSender = PocomosEmailSender::find($request->sms_email_sender_id);

        if (!$PocomosEmailSender) {
            return $this->sendResponse(false, 'Admin Sender not found.');
        }

        $PocomosEmailSender->update(
            $request->only('address', 'active')
        );

        return $this->sendResponse(true, 'Admin Sender updated successfully.', $PocomosEmailSender);
    }
}
