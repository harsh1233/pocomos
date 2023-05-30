<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\Recruitement\OfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosRecruitStatus;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Orkestra\OrkestraAccount;
use DB;
use App\Exports\PaymentHistory;
use PDF;

class TransactionHistoryController extends Controller
{
    use Functions;


    /**
     * API for list payment history data
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function transactions(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|integer|min:1',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomer = PocomosCustomer::find($request->customer_id);

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if (!$profile) {
            return $this->sendResponse(false, 'Unable to locate Customer Sales Profile.');
        }

        $payment_history = "SELECT rsf.invoice_id,prc.*,prc2.external_account_id,prc2.alias,prc4.first_name,prc4.last_name
        FROM pocomos_customer_sales_profiles AS pr
         join pocomos_customers_accounts AS pca ON   pca.profile_id=  pr.id
         join orkestra_transactions AS prc ON  prc.account_id = pca.account_id
        JOIN pocomos_user_transactions AS rsf ON   rsf.transaction_id = prc.id
        left JOIN orkestra_accounts AS prc2 ON pca.account_id = prc2.id
        left JOIN orkestra_users AS prc4 ON rsf.user_id  = prc4.id
        WHERE pr.id = '$profile->id'
        GROUP BY rsf.transaction_id
        ";

        if ($request->search) {
            $search = "'%" . $request->search . "%'";
            $payment_history .= ' AND (rsf.invoice_id LIKE ' . $search . ' OR prc.date_created LIKE "%' . date('Y-m-d', strtotime($request->search)) . '%" OR prc.network LIKE ' . $search . ' OR prc.amount LIKE ' . $search . ' OR prc.type LIKE ' . $search . ' OR prc.status LIKE ' . $search . ' OR prc.description LIKE ' . $search . ' OR prc2.external_account_id LIKE ' . $search . ' OR prc4.first_name LIKE ' . $search . ' OR prc4.last_name LIKE ' . $search . ' OR prc2.alias LIKE ' . $search . ')';
        }

        /**For pagination */
        $count = count(DB::select(DB::raw($payment_history)));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $payment_history .= " LIMIT $perPage offset $page";

        $payment_history = DB::select(DB::raw(($payment_history)));

        $multipleInvoices = $this->getmultipleTransactionInvoices($payment_history);

        if ($request->is_export) {
            return Excel::download(new PaymentHistory($payment_history), 'PaymentHistory.csv');
        }

        return $this->sendResponse(true, 'List', [
            'PaymentHistory' => $payment_history,
            'count' => $count,
        ]);
    }
}
