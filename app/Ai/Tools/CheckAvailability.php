<?php

namespace App\Ai\Tools;

use App\Models\Lead;
use App\Services\Calendar\Availability;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class CheckAvailability implements Tool
{
    public function __construct(public Lead $lead) {}

    public function name(): string
    {
        return 'check_availability';
    }

    public function description(): string
    {
        return 'List the next real appointment openings. Offer only times returned by this tool.';
    }

    public function handle(Request $request): string
    {
        $slots = app(Availability::class)->nextSlots($this->lead->business);

        $this->lead->forceFill([
            'offered_slots' => $slots,
            'status' => $this->lead->status === 'booked' ? 'booked' : 'qualified',
            'awaiting' => 'time',
        ])->save();

        return $slots === []
            ? 'No openings in the next three weeks.'
            : json_encode($slots, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
