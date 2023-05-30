<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemoteCompletionRecruitment extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $office;
    public $recruit_user;
    public $recruiter_user;
    public $recruit;
    public $redirect_url;

    public function __construct($office, $recruit_user, $recruiter_user, $recruit)
    {
        $this->office = $office;
        $this->recruit_user = $recruit_user;
        $this->recruiter_user = $recruiter_user;
        $this->recruit = $recruit;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $server_name = $_SERVER['SERVER_NAME'];
        $recruit_hashed = Crypt::encryptString($this->recruit->id);
        $user_hashed = Crypt::encryptString($this->recruit->user_id);
        $this->redirect_url = $server_name.config('constants.RECRUIT_REMOTE_COMPLETION_URL').$recruit_hashed.'/'.$user_hashed;

        return $this->subject(__('email_subject.remote_completion_recruitment', ['office' => $this->office]))->view('emails.remote_completion_recruitment', [$this->office, $this->recruit_user, $this->recruiter_user, $this->redirect_url]);
    }
}
