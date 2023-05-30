<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportTechnicianReport implements FromView
{
    public $data;

    public function __construct($res)
    {
        $this->data = $res;
    }

    public function view(): View
    {
        // dd($this->data);
        return view('csv.technician_report', [
            'data' => $this->data,
        ]);
    }
}
