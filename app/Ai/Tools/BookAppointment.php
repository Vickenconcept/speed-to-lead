<?php

namespace App\Ai\Tools;

use App\Models\Lead;
use App\Services\Calendar\Booker;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Throwable;

class BookAppointment implements Tool
{
    public function __construct(public Lead $lead) {}

    public function name(): string
    {
        return 'book_appointment';
    }

    public function description(): string
    {
        return 'Book one of the offered appointment times for this lead.';
    }

    public function handle(Request $request): string
    {
        try {
            $appointment = app(Booker::class)->book(
                $this->lead,
                (string) $request['starts_at'],
                (string) ($request['label'] ?? 'the selected time'),
            );

            return 'Booked '.$appointment->label();
        } catch (Throwable $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'starts_at' => $schema->string()->required(),
            'label' => $schema->string()->required(),
        ];
    }
}
