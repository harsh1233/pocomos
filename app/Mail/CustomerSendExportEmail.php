<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerSendExportEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $request_data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($request_data)
    {
        $this->request_data = $request_data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data['body'] = $this->request_data['body'];
        return $this->subject($this->request_data['subject'])->view('emails.customer_export_details_send', $data)
            ->attach(storage_path('app/public/pdf/Customers.csv'), [
                'as' => 'Customers.csv',
                'mime' => 'application/csv',
           ]);
    }
}
