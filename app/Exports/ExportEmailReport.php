<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportEmailReport implements FromView
{
    public $data;
    public $numberOfJobs;

    public function __construct($res)
    {
        $this->data = $res;
    }

    public function view(): View
    {
        return view('csv.email_report', [
            'data' => $this->data,
        ]);
    }
}
