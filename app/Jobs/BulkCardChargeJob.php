<?php

namespace App\Jobs;

use DB;
use Excel;
use Illuminate\Bus\Queueable;
use App\Exports\ExportCustomer;
use App\Models\Pocomos\PocomosTag;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosAlert;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosPestContractsTag;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosUserTransaction;
use App\Models\Pocomos\PocomosInvoiceTransaction;
use App\Models\Orkestra\OrkestraTransaction;
use App\Models\Orkestra\OrkestraAccount;

class BulkCardChargeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $invoiceIds;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($invoiceIds)
    {
        $this->invoiceIds = $invoiceIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Unpaid invoice Job Started For Export");

        // $encode = base64_encode(json_encode($this->invoiceIds));
// dd($this->invoiceIds);
        $month = date('m');;
        $year = date('Y');;

        foreach ($this->invoiceIds as $invoiceId) {
            $invoice = PocomosInvoice::with('contract.profile_details')->whereId($invoiceId)->first();
            $accountId = $invoice->autopay_account_id;
            $balance = $invoice->balance;

            $csp = $invoice->contract->profile_details;

            // dd($csp->getCardAccounts());

            $account = $csp->getCardAccounts()->filter(function ($account) use ($month, $year) {
                if (!$account->active) {
                    return false;
                }

                if ($account->card_exp_year < $year) {
                    return false;
                }

                if ($account->card_exp_year == $year && $account->card_exp_month < $month) {
                    return false;
                }

                return true;
            })->first();


            // dd($account);

            if (!$account) {
                $account = $csp->getBankAccounts()->filter(function ($account) {
                    if($account->active == 1){
                        return $account;
                    }
                })->first();
            }

            if (!$account) {
                $resultSet[] = array(
                    'customer' => $csp->customer->__toString(),
                    'customerId' => $csp->customer->id,
                    'invoiceId' => $invoice->id,
                    'amount' => $invoice->balance,
                    'account' => 'No valid Card or ACH account on file',
                    'status' => 'n/a',
                );

                continue;
            }

            if($account){
                $accountId = $account->id;
            }

            $generalValues['customer_id'] = $csp->customer_details->id ?? null;
            $generalValues['office_id'] = $csp->office_id;

            // dd($csp->account_details[0]->account_detail->id);
            
            // $accountId = $csp->account_details[0]->account_detail->id;

            $payment['amount'] = $invoice->balance;
            $payment['account_id'] = $accountId;
            $payment['method'] = 'card';
            $payment['description'] = '';

            // dd($invoice->balance);

            $transaction = $this->processPayment($invoice->id, $generalValues, $payment, auth()->user()->id);

        }



        Log::info("Unpaid invoice  Job End For Export");
    }
}
