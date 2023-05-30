<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportNote implements FromView
{
    public $note_data;

    public function __construct($accounts)
    {
        $this->note_data = $accounts;
    }

    public function view(): View
    {
        return view('csv.note_data', [
            'note_data' => $this->note_data
        ]);
    }
}
