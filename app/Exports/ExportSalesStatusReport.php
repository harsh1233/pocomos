<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportSalesStatusReport implements FromView
{
    public $data;

    public function __construct($res)
    {
        $this->data = $res;
    }

    public function view(): View
    {
        return view('csv.sales_status_report', [
            'data' => $this->data,
        ]);
    }
}
