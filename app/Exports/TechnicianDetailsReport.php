<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class TechnicianDetailsReport implements FromView
{
    public $techDetails;

    public function __construct($techDetails)
    {
        $this->techDetails = $techDetails;
    }

    public function view(): View
    {
        return view('csv.technician_details_report', [
            'data' => $this->techDetails
        ]);
    }
}
