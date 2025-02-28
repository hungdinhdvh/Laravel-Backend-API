<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationNotification extends Notification
{
    use Queueable;

    protected $url;
    /**
     * Create a new notification instance.
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
   public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Verify Email Address')
                    ->greeting('Hello!')
                    ->line('Thank you for signing up with Our App! To ensure you have the best experience, please verify your email address.')
                    ->line("Dear $notifiable->name")
                    ->action('Verify Email', $this->url)
                    ->line('If you did not sign up for this account, please ignore this email.')
                    ->line('Thank you for using our application!')
                    ->line('If you have any questions, feel free to contact our support team at support@ourapp.com.');
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
