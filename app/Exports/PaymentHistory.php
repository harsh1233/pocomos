<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PaymentHistory implements FromView
{
    public $payment_history;

    public function __construct($accounts)
    {
        $this->payment_history = $accounts;
    }

    public function view(): View
    {
        return view('csv.payment_history', [
            'payment_history' => $this->payment_history
        ]);
    }
}
