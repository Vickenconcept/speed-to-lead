<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentBooked extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->appointment->lead;

        return (new MailMessage)
            ->subject('Appointment booked: '.$lead->name)
            ->greeting('A lead just booked')
            ->line($lead->name.' — '.($lead->service_requested ?: 'New request'))
            ->line($this->appointment->label())
            ->action('Open the lead', route('leads.show', $lead));
    }
}
