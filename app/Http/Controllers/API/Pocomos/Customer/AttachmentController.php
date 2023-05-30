<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use PDF;
use Excel;
use DateTime;
use App\Jobs\SearchStateJob;
use Illuminate\Http\Request;
use App\Jobs\ExportCustomers;
use App\Jobs\ContractStateJob;
use App\Jobs\CustomerStateJob;
use App\Exports\ExportQrReport;
use App\Models\Pocomos\PocomosJob;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosLead;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosPest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosEmail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SendEmailCustomerExport;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\PocomosCustomersPhone;
use Illuminate\Support\Facades\Crypt;
use App\Http\Requests\CustomerRequest;
use App\Mail\RemoteCompletionCustomer;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosLeadNote;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Pocomos\PocomosLeadQuote;
use App\Mail\RemoteCompletionRecruitment;
use App\Models\Pocomos\PocomosCustomField;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosFormVariable;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosLeadQuoteTag;
use App\Models\Pocomos\PocomosLeadQuotPest;
use App\Models\Pocomos\PocomosLeadReminder;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosCustomersFile;
use App\Models\Pocomos\PocomosCustomersNote;
use App\Models\Pocomos\PocomosCustomerState;
use App\Models\Pocomos\PocomosMissionConfig;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Http\Requests\CustomerAdvanceRequest;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosPestContractsPest;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosPestContractsInvoice;
use App\Models\Pocomos\PocomosCustomersWorkorderNote;
use App\Models\Pocomos\PocomosLeadQuoteSpecialtyPest;
use App\Models\Pocomos\PocomosPestContractServiceType;
use App\Models\Pocomos\PocomosCustomersNotifyMobilePhone;
use Illuminate\Support\Facades\Response as Download;

class AttachmentController extends Controller
{
    use Functions;

    /**
     * API for uplaod File of Customer
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function uploadCustomerFile(Request $request)
    {
        $v = validator($request->all(), [
            'file' => 'required|mimes:pdf,doc,docx,xls,xlsx,jpeg,png,gif|max:20480',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'file_description' => 'nullable',
            'show_to_customer' => 'required|boolean',
            'user_id' => 'required|exists:orkestra_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomer = PocomosCustomer::find($request->customer_id);

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $file = $request['file'];

        if ($file) {
            //store your file into database
            $file_name = $file->getClientOriginalName();
            $file_name = explode('.', $file_name);
            $file_name = ($file_name[0]??'file').'_'.strtotime(date('Y-m-d H:i:s')).'.'.($file_name[1]??'png');

            $customer_detail['filename'] = $file_name;
            $customer_detail['mime_type'] = $file->getMimeType();
            $customer_detail['file_size'] = $file->getSize();
            $customer_detail['active'] = 1;
            $customer_detail['file_description'] = $request['file_description']  ?? null;
            $customer_detail['show_to_customer'] = $request['show_to_customer']  ?? null;
            $customer_detail['md5_hash'] =  md5_file($file->getRealPath());
            $customer_detail['user_id'] = $request['user_id'];

            $url = "Customer" . "/" . $customer_detail['filename'];
            Storage::disk('s3')->put($url, file_get_contents($file));
            $customer_detail['path'] = Storage::disk('s3')->url($url);

            $file =  OrkestraFile::create($customer_detail);

            if ($file) {
                $attachment_id  = $file->id;
            }

            $input_details['customer_id'] = $request->customer_id;
            $input_details['file_id'] = $attachment_id ?? null;
            PocomosCustomersFile::create($input_details);
        }

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'The file has been uploaded']));
    }


    /**
     * API for list File of Customer
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function listCustomerFile(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomersFile = PocomosCustomersFile::where('customer_id', $request['customer_id'])->pluck('file_id')->toArray();

        $results = OrkestraFile::whereIn('id', $PocomosCustomersFile)->with('user_details_name');

        if ($request->search) {
            $search = $request->search;
            $results->where(function ($query) use ($search) {
                $query->where('filename', 'like', '%' . $search . '%')
                    ->orwhere('file_description', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $results->count();
        $results->skip($perPage * ($page - 1))->take($perPage);

        $results = $results->orderBy('id', 'desc')->get();

        return $this->sendResponse(true, 'List', [
            'CustomerFile' => $results,
            'count' => $count,
        ]);
    }


    /**
     * API for edit File of Customer
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function editCustomerFile(Request $request)
    {
        $v = validator($request->all(), [
            'file' => 'mimes:pdf,doc,docx,xls,xlsx,jpeg,png,gif|max:20480',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'file_description' => 'nullable',
            'show_to_customer' => 'required|boolean',
            'user_id' => 'required|exists:orkestra_users,id',
            'file_id' => 'required|exists:orkestra_files,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomer = PocomosCustomer::find($request->customer_id);

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $OrkestraFile = OrkestraFile::find($request->file_id);

        if (!$OrkestraFile) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'File']));
        }

        $customer_detail['file_description'] = $request['file_description']  ?? null;
        $customer_detail['show_to_customer'] = $request['show_to_customer']  ?? null;
        $customer_detail['user_id'] = $request['user_id'];

        if (isset($request->file)) {
            $file = $request['file'];

            //store your file into database
            $customer_detail['filename'] = $file->getClientOriginalName();
            $customer_detail['mime_type'] = $file->getMimeType();
            $customer_detail['file_size'] = $file->getSize();
            $customer_detail['md5_hash'] =  md5_file($file->getRealPath());

            $url = "Customer" . "/" . $customer_detail['filename'];
            Storage::disk('s3')->put($url, file_get_contents($file));
            $customer_detail['path'] = Storage::disk('s3')->url($url);
        }

        $file = $OrkestraFile->update($customer_detail);

        return $this->sendResponse(true, __('strings.sucess', ['name' => 'The file has been uploaded']));
    }


    /**
     * API for delete files of Customer
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function deleteCustomerfile(Request $request)
    {
        $v = validator($request->all(), [
            'file_id' => 'required|exists:orkestra_files,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $OrkestraFile = OrkestraFile::find($request->file_id);
        if (!$OrkestraFile) {
            return $this->sendResponse(false, 'File not found.');
        }

        $PocomosCustomersFile = PocomosCustomersFile::where('file_id', $request->file_id)->count();

        if ($PocomosCustomersFile) {
            PocomosCustomersFile::where('file_id', $request->file_id)->delete();

            PocomosContract::where('signature_id', $request->file_id)->update(['signature_id' => null]);
            PocomosContract::where('autopay_signature_id', $request->file_id)->update(['autopay_signature_id' => null]);

            $OrkestraFile->delete();
        }

        return $this->sendResponse(true, 'File deleted successfully.');
    }


    /**
     * Send file to user emails
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function resendAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'emails' => 'array',
            'file_id' => 'required|exists:orkestra_files,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'user_id' => 'required|exists:orkestra_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = PocomosCustomer::findOrFail($request->customer_id);

        if (!$customer) {
            return $this->sendResponse(false, 'Unable to find Customer entity.');
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to find the Customer Profile.');
        }

        $OrkestraFile = OrkestraFile::find($request->file_id);

        if (!$OrkestraFile) {
            return $this->sendResponse(false, 'File not found.');
        }

        $OrkestraUser = OrkestraUser::find($request->user_id);

        $office = PocomosCompanyOffice::where('id', $request->office_id)->first();
        $office_email = unserialize($office->email);

        if (isset($office_email[0])) {
            $from = $office_email[0];
        } else {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        foreach ($request->emails as $emails) {
            $data = array(
                'customer' => $customer,
                'office' => $office,
                'file' => $OrkestraFile,
                'user' => $OrkestraUser,
            );

            $subject = 'Customer File';

            Mail::send('emails.customer_file_email_template', ['data' => $data], function ($message) use ($subject, $emails, $from) {
                $message->from($from);
                $message->to($emails);
                $message->subject($subject);
            });
        }

        return $this->sendResponse(true, 'The file has been sent to selected emails successfully.');
    }
}
