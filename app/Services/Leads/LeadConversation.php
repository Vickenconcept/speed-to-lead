<?php

namespace App\Services\Leads;

use App\Ai\Agents\LeadAgent;
use App\Models\Lead;
use App\Models\Message;
use Throwable;

class LeadConversation
{
    public function respond(Lead $lead): void
    {
        $lead->load(['business.facts', 'messages']);
        $latest = $lead->messages->last();
        $customerText = $latest?->body ?? $lead->message;

        Message::query()->create([
            'lead_id' => $lead->id,
            'role' => 'assistant',
            'body' => $this->reply($lead, $customerText),
        ]);
    }

    public function aiEnabled(): bool
    {
        return filled(config('ai.providers.openrouter.key'));
    }

    private function reply(Lead $lead, string $customerText): string
    {
        if (! $this->aiEnabled()) {
            return 'Thanks for the message. We will reply shortly.';
        }

        try {
            $response = (new LeadAgent($lead))
                ->continueLastConversation($lead)
                ->prompt($customerText, timeout: 45);

            $lead->refresh();

            $lead->forceFill([
                'service_requested' => $this->filled($response['service_requested'] ?? null) ?? $lead->service_requested,
                'location' => $this->filled($response['location'] ?? null) ?? $lead->location,
                'intent' => $this->filled($response['intent'] ?? null) ?? $lead->intent,
                'urgency' => $this->filled($response['urgency'] ?? null) ?? $lead->urgency,
                'status' => $lead->status === 'booked'
                    ? 'booked'
                    : (string) ($response['status'] ?? $lead->status),
            ])->save();

            $reply = trim((string) ($response['reply'] ?? ''));

            return $reply !== '' ? $reply : 'Sorry, send that once more.';
        } catch (Throwable $exception) {
            report($exception);

            return 'Sorry, send that once more.';
        }
    }

    private function filled(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
