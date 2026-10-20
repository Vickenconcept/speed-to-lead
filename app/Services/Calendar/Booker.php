<?php

namespace App\Services\Calendar;

use App\Models\Appointment;
use App\Models\Lead;
use App\Notifications\AppointmentBooked;
use Illuminate\Support\Carbon;
use RuntimeException;

class Booker
{
    public function book(Lead $lead, string $startsAt, string $label): Appointment
    {
        $lead->loadMissing('business.user');

        if ($lead->appointment()->where('status', 'booked')->exists()) {
            throw new RuntimeException('This lead already has an appointment.');
        }

        $start = Carbon::parse($startsAt);
        $end = $start->copy()->addMinutes($lead->business->slot_minutes);

        $overlaps = Appointment::query()
            ->where('business_id', $lead->business_id)
            ->where('status', 'booked')
            ->where('starts_at', '<', $end->utc())
            ->where('ends_at', '>', $start->utc())
            ->exists();

        if ($overlaps) {
            throw new RuntimeException('That time is no longer available.');
        }

        $appointment = Appointment::query()->create([
            'business_id' => $lead->business_id,
            'lead_id' => $lead->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'status' => 'booked',
            'summary' => $lead->service_requested ?: 'Appointment',
        ]);

        $lead->forceFill([
            'status' => 'booked',
            'awaiting' => null,
            'offered_slots' => null,
        ])->save();

        $lead->business->user?->notify(new AppointmentBooked($appointment));

        return $appointment;
    }
}
