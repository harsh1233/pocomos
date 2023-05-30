<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Orkestra\OrkestraTransaction;
use App\Models\Pocomos\PocomosUserTransaction;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\Recruitement\OfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosRecruitStatus;

class AccountController extends Controller
{
    use Functions;


    /**
     * API for list payment account
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|integer|min:1|exists:pocomos_customers,id',
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

        $add = DB::select(DB::raw("SELECT SUM((amount)/100) as amount FROM orkestra_transactions where account_id='$profile->points_account_id' AND type = 'Credit'"));

        $remove = DB::select(DB::raw("SELECT SUM((amount)/100) as amount FROM orkestra_transactions where account_id='$profile->points_account_id' AND type = 'Sale'"));

        $result = $add[0]->amount - $remove[0]->amount;

        $account_data = PocomosCustomersAccount::where('profile_id', $profile->id)->select('account_id')->get()->toArray();

        $PocomosCustomersAccount = OrkestraAccount::whereIn('id', ($account_data))->whereIn('type', ['CardAccount', 'BankAccount'])->where('active', 1);

        // for cash/check, token(method) (id to be passed will be same)
        // for processed outside (method) > ext. a/c
        $simpleAccounts = OrkestraAccount::whereIn('id', ($account_data))
            ->whereType('SimpleAccount')
            ->where('active', 1)
            ->get();

        // for a/c credit
        $pointAccounts = OrkestraAccount::whereIn('id', ($account_data))
            ->whereType('PointsAccount')
            ->where('active', 1)
            ->get();

        $OrkestraTransaction = OrkestraTransaction::where('account_id', $profile->points_account_id)->first();

        if ($request->search) {
            $search = $request->search;
            $PocomosCustomersAccount->where(function ($query) use ($search) {
                $query->where('alias', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('date_modified', 'like', '%' . date('Y-m-d', strtotime($search)) . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosCustomersAccount->count();
        $PocomosCustomersAccount->skip($perPage * ($page - 1))->take($perPage);

        $PocomosCustomersAccount = $PocomosCustomersAccount->get();

        return $this->sendResponse(true, 'List of Payment Account Data', [
            'Payment_Account_Data' => $PocomosCustomersAccount,
            'simple_accounts' => $simpleAccounts,
            'ac_accounts' => $pointAccounts,
            'count' => $count,
            'Balance' => $result,
            'Balance_Last_modified' => $OrkestraTransaction->date_created ?? null
        ]);
    }

    /**
     * API for create of payment account
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|integer|min:1|exists:pocomos_customers,id',
            'alias' => 'required',
            'type' => 'required|in:CardAccount,BankAccount',
            'account_number' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomer = PocomosCustomer::find($request->customer_id);

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->firstOrFail();

        $PocomosCustomersAccount = PocomosCustomersAccount::where('profile_id', $profile->id)->firstOrFail();

        $OrkestraAccount = OrkestraAccount::findOrFail($PocomosCustomersAccount->account_id);

        $input_details['ip_address'] = '';
        $input_details['alias'] = $request->alias ?? null;
        $input_details['type'] =  $request->type ?? null;
        $input_details['account_number'] = $request->account_number ?? null;
        $input_details['card_exp_month'] = $request->card_exp_month ?? null;
        $input_details['card_exp_year'] = $request->card_exp_year ?? null;
        $input_details['ach_routing_number'] = $request->ach_routing_number ?? null;
        $input_details['name'] = $OrkestraAccount->name;
        $input_details['address'] = $OrkestraAccount->address;
        $input_details['city'] = $OrkestraAccount->city;
        $input_details['region'] = $OrkestraAccount->region;
        $input_details['country'] = $OrkestraAccount->country;
        $input_details['postal_code'] = $OrkestraAccount->postal_code;
        $input_details['phoneNumber'] = $OrkestraAccount->phoneNumber;
        $input_details['active'] = 1;
        $input_details['email_address'] = $OrkestraAccount->email_address;
        $input_details['external_person_id'] = $OrkestraAccount->external_person_id;
        $input_details['external_account_id'] = $OrkestraAccount->external_account_id;

        $Account =  OrkestraAccount::create($input_details);

        PocomosCustomersAccount::insert([
            'profile_id' => $profile->id, 'account_id' => $Account->id,
        ]);

        if (isset($request->autopay) && $request->autopay == 1) {
            $profile->update([
                'autopay_account_id' => $Account->id,
                'autopay' => 1,
            ]);
        }

        return $this->sendResponse(true, 'Payment Account created successfully.', $Account);
    }

    /**
     * API for update of Payment Account
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|integer|min:1|exists:pocomos_customers,id',
            'account_id' => 'required|integer|min:1|exists:orkestra_accounts,id',
            'alias' => 'nullable',
            'type' => 'nullable|in:CardAccount,BankAccount',
            'account_number' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosCustomer = PocomosCustomer::find($request->customer_id);

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->firstOrFail();

        $OrkestraAccount = OrkestraAccount::find($request->account_id);

        $input_details = [];

        if ($request->alias) {
            $input_details['alias'] = $request->alias;
        }

        $input_details['date_modified'] = date("Y-m-d");

        if ($request->account_number) {
            $input_details['account_number'] = $request->account_number;
        }

        if ($request->ach_routing_number) {
            $input_details['ach_routing_number'] = $request->ach_routing_number;
        }

        if ($OrkestraAccount) {
            $OrkestraAccount->update($input_details);
        }

        if (isset($request->autopay) && $request->autopay == 1) {
            $profile->update([
                'autopay_account_id' => $request->account_id,
                'autopay' => 1,
            ]);
        }

        if ($request->autopay == 0) {
            $profile->update([
                'autopay_account_id' => null,
                'autopay' => 0,
            ]);
        }

        return $this->sendResponse(true, 'Payment Account Edited successfully.', $OrkestraAccount);
    }

    /**
     * API for delete of Recruiting Status
     .
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete(Request $request)
    {
        $v = validator($request->all(), [
            'account_id' => 'required|integer|min:1',
            'customer_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }


        $PocomosCustomer = PocomosCustomer::find($request->customer_id);

        if (!$PocomosCustomer) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Customer']));
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        if ($profile->autopay_account_id == $request->account_id) {
            return $this->sendResponse(false, 'This Payment account can not be delete.');
        }

        $OrkestraAccount = OrkestraAccount::find($request->account_id);
        if (!$OrkestraAccount) {
            return $this->sendResponse(false, 'Payment Account not found.');
        }

        $OrkestraAccount->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Payment Account deleted successfully.');
    }


    /**
     * Create add/ remove credit
     *
     * @param Request $request
     */

    public function creditCreate(Request $request)
    {
        $v = validator($request->all(), [
            'add_credit' => 'required|boolean',
            'amount' => 'required',
            'description' => 'required',
            'customer_id' => 'required|exists:pocomos_customers,id',
            'contract_id' => 'required|exists:pocomos_contracts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $profile = PocomosCustomerSalesProfile::where('customer_id', $request->customer_id)->first();

        $account = $profile->points_account;

        if (!($profile->points_account_id)) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Points Account.']));
        }

        $amount = $request->amount;
        $description = $request->description;
        $addCredit = $request->add_credit;

        try {
            DB::beginTransaction();
            if ($addCredit) {
                $result = $this->addCredit($profile, $amount, null, $description);
            } else {
                $result = $this->removeCredit($profile, $amount, null, $description);
            }

            $account->balance = $account->balance + ($amount * 100);
            $account->save();

            DB::commit();
            $status = true;
            $message = __('strings.message', ['message' => sprintf('Credit has been %s successfully.', $amount < 0 ? 'reduced' : 'issued')]);
        } catch (\RuntimeException $e) {
            DB::rollback();
            $status = false;
            $message = $e->getMessage();
        }

        return $this->sendResponse($status, $message);
    }
}
