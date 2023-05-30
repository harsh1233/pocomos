<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAddNotification extends Notification
{
    use Queueable;
    public $body;
    public $user;
    public $sender;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($body, $user, $sender)
    {
        $this->body = $body;
        $this->user = $user;
        $this->sender =  $sender;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('You have been assigned a task')
            ->view('emails.task_notification', ['body'=>$this->body,'user'=>$this->user,'sender'=>$this->sender]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
