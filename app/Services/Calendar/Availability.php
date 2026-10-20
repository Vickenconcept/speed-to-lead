<?php

namespace App\Services\Calendar;

use App\Models\Appointment;
use App\Models\Business;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class Availability
{
    /**
     * Next morning opening and next afternoon opening.
     *
     * @return array<int, array{starts_at: string, label: string}>
     */
    public function nextSlots(Business $business, int $count = 2): array
    {
        $timezone = $business->timezone ?: 'UTC';
        $earliest = $this->align(now($timezone)->addHours($business->notice_hours));
        $morning = null;
        $afternoon = null;
        $day = $earliest->copy()->startOfDay();

        for ($i = 0; $i < 21 && ($morning === null || $afternoon === null); $i++) {
            $window = collect($business->hours)->firstWhere('day', $day->dayOfWeek);

            if ($window !== null) {
                $open = $day->copy()->setTimeFromTimeString($window['opens']);
                $close = $day->copy()->setTimeFromTimeString($window['closes']);
                $noon = $day->copy()->setTime(12, 0);

                if ($morning === null) {
                    $candidate = $this->align($open->greaterThan($earliest) ? $open : $earliest);

                    if ($candidate->lt($noon) && $candidate->copy()->addMinutes($business->slot_minutes)->lte($close) && ! $this->overlaps($business, $candidate)) {
                        $morning = $candidate;
                    }
                }

                if ($afternoon === null) {
                    $start = $noon->greaterThan($earliest) ? $noon : $earliest;
                    $candidate = $this->align($start);

                    if ($candidate->lt($close) && $candidate->copy()->addMinutes($business->slot_minutes)->lte($close) && ! $this->overlaps($business, $candidate)) {
                        $afternoon = $candidate;
                    }
                }
            }

            $day = $day->addDay();
        }

        return collect([$morning, $afternoon])
            ->filter()
            ->take($count)
            ->map(fn (CarbonInterface $start) => [
                'starts_at' => $start->utc()->toIso8601String(),
                'label' => $this->label($start),
            ])
            ->values()
            ->all();
    }

    public function label(CarbonInterface $start): string
    {
        $day = $start->isTomorrow() ? 'Tomorrow' : ($start->isToday() ? 'Today' : $start->format('D, M j'));

        return $day.' '.$start->format('g:i A');
    }

    /**
     * @param  array<int, array{starts_at: string, label: string}>  $slots
     * @return array{starts_at: string, label: string}|null
     */
    public function matchChoice(string $message, array $slots, string $timezone): ?array
    {
        $text = strtolower(trim($message));

        foreach ($slots as $slot) {
            $start = Carbon::parse($slot['starts_at'])->timezone($timezone);
            $hour = $start->format('g');
            $minutes = $start->format('i');
            $clock = strtolower($start->format('g:i a'));

            if (str_contains($text, strtolower($slot['label'])) || str_contains($text, $clock)) {
                return $slot;
            }

            if (preg_match('/\b'.preg_quote($hour, '/').'(?::'.$minutes.')?\b/', $text) === 1) {
                return $slot;
            }
        }

        return null;
    }

    private function align(CarbonInterface $moment): CarbonInterface
    {
        if ($moment->minute === 0 && $moment->second === 0) {
            return $moment;
        }

        return $moment->addHour()->startOfHour();
    }

    private function overlaps(Business $business, CarbonInterface $start): bool
    {
        $end = $start->copy()->addMinutes($business->slot_minutes);

        return Appointment::query()
            ->where('business_id', $business->id)
            ->where('status', 'booked')
            ->where('starts_at', '<', $end->utc())
            ->where('ends_at', '>', $start->utc())
            ->exists();
    }
}
