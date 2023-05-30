<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportContract implements FromView
{
    public $contract_data;

    public function __construct($accounts)
    {
        $this->contract_data = $accounts;
    }

    public function view(): View
    {
        return view('csv.contract_data', [
            'contract_data' => $this->contract_data
        ]);
    }
}
