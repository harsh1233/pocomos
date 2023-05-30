<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportCustomer implements FromView
{
    public $heading;
    public $data;

    public function __construct($heading, $res, $exported_columns)
    {
        $this->heading = $heading;
        $this->data = $res;
        $this->exported_columns = $exported_columns;
    }

    public function view(): View
    {
        return view('csv.customers_data', [
            'heading' => $this->heading,
            'data' => $this->data,
            'exported_columns' => $this->exported_columns
        ]);
    }
}
