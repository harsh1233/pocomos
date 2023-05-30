<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportNyUsageReport implements FromView
{
    public $data;
    public $startDate;
    public $endDate;

    public function __construct($res, $startDate, $endDate)
    {
        $this->data = $res;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        return view('csv.ny_usage_report', [
            'data' => $this->data,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }
}
