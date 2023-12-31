<?php

namespace App\Notifications;

use App\Models\Check;
use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SiteStatusChanged extends Notification
{
    use Queueable;

    public $site;

    public $check;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Site $site, Check $check)
    {
        $this->site = $site;
        $this->check = $check;
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
        $subject = $this->check->successful()
            ? "Your site {$this->site->url} is online again"
            : "Your site {$this->site->url} is offline";

        $message = $this->check->successful()
            ? "We are just informing that just now, {$this->check->created_at}, the site {$this->site->url} is now online."
            : "We are just informing that just now, {$this->check->created_at}, the site {$this->site->url} went online.";

        return (new MailMessage)
                    ->subject($subject)
                    ->line("Hello {$notifiable->name},")
                    ->line($message)
                    ->action('See Site', route('sites.show', $this->site));
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
