<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportUsageReport implements FromView
{
    public $data;
    public $numberOfJobs;

    public function __construct($res, $numberOfJobs, $startDate, $endDate)
    {
        $this->data = $res;
        $this->numberOfJobs = $numberOfJobs;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        return view('csv.usage_report', [
            'data' => $this->data,
            'numberOfJobs' => $this->numberOfJobs,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }
}
