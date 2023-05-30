<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemoteCompletionCustomer extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $data;
    public $pest_contract_id;

    public function __construct($data, $pest_contract_id)
    {
        $this->data = $data;
        $this->pest_contract_id = $pest_contract_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // $server_name = $_SERVER['SERVER_NAME'];
        $server_name = 'http://15.206.7.200';
        // $ssl = "http://";

        // $ssl_check = @fsockopen( 'ssl://' . $server_name, 443, $errno, $errstr, 30 );
        // $res = !! $ssl_check;
        // if ( $ssl_check ) { fclose( $ssl_check ); }
        // if($res) $ssl = "https://";

        $hashed_entity = Crypt::encryptString($this->pest_contract_id);
        $redirect_url = $server_name.config('constants.CUSTOMER_REMOTE_COMPLETION_URL').$hashed_entity;
        $this->data->redirect_url = $redirect_url;

        return $this->subject(__('email_subject.remote_completion_customer'))->view('emails.remote_completion_customer', [$this->data]);
    }
}
