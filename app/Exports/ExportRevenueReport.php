<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportRevenueReport implements FromView
{
    public $data;

    public function __construct($res)
    {
        $this->data = $res;
    }

    public function view(): View
    {
        return view('csv.revenue_report', [
            'data' => $this->data,
        ]);
    }
}
