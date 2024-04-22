<?php

namespace App\Notifications;

use Ichtrojan\Otp\Models\Otp as ModelsOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Ichtrojan\Otp\Otp;
use PHPMailer\PHPMailer\PHPMailer;

class RegisterNotification extends Notification
{
    use Queueable;
    public $message;
    public $subject;
    private $otp;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->message = 'Use the below code for verification process ';
        $this->subject = 'Verification Needed';
        $this->otp = new Otp;
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
        $latestOTP = ModelsOtp::where('identifier', $notifiable->email)->latest()->first();
        if ($latestOTP) {
            $otp = $this->otp->generate($notifiable->email,'numeric', 6, $latestOTP->validity);
            return (new MailMessage)
                ->mailer('smtp')
                ->subject($this->subject)
                ->greeting('Welcome you in ' .env('MAIL_FROM_NAME'))
                ->line($this->message)
                ->line('code: '. $otp->token);
        }
        else{
            $otp = $this->otp->generate($notifiable->email,'numeric', 6, 5);
            return (new MailMessage)
                ->mailer('smtp')
                ->subject($this->subject)
                ->greeting('Welcome you in ' .env('MAIL_FROM_NAME'))
                ->line($this->message)
                ->line('code: '. $otp->token);
        }
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
