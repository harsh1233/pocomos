<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportPhone implements FromView
{
    public $phone_data;

    public function __construct($accounts)
    {
        $this->phone_data = $accounts;
    }

    public function view(): View
    {
        return view('csv.phone_data', [
            'phone_data' => $this->phone_data
        ]);
    }
}
