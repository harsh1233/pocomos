<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportAccount implements FromView
{
    public $accounts_data;

    public function __construct($accounts)
    {
        $this->accounts_data = $accounts;
    }

    public function view(): View
    {
        return view('csv.accounts_data', [
            'accounts_data' => $this->accounts_data
        ]);
    }
}
