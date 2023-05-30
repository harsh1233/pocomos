<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecruitmentAgreement extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $agreement;
    public $path;
    public $rec_id;

    public function __construct($agreement, $path, $rec_id)
    {
        $this->agreement = $agreement;
        $this->path = $path;
        $this->rec_id = $rec_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->agreement)->view('emails.recruitement_agreement_send')
        ->attach(storage_path('app/public').'/pdf/agreement_'.$this->rec_id.'.pdf', [
            'as' => 'agreement.pdf',
            'mime' => 'application/pdf',
       ]);
    }
}
