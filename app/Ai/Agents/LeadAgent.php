<?php

namespace App\Ai\Agents;

use App\Ai\Tools\BookAppointment;
use App\Ai\Tools\CheckAvailability;
use App\Ai\Tools\GetBusinessProfile;
use App\Models\Lead;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::OpenRouter)]
#[Model('nvidia/nemotron-3-super-120b-a12b:free')]
class LeadAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(public Lead $lead) {}

    public function instructions(): string
    {
        $known = $this->lead->business->profileForAgent();

        return <<<TEXT
        You are the person at this business who texts new leads. Speak as yourself, using "we" and "I".

        The notes below are what you know. Never say you read a profile, a page, or a website.

        Answer the latest message in one or two short sentences.
        You remember this conversation. If they ask what you said, say it again.
        If the message is not a real question or answer, ask them to say it again. Do not treat it as agreement, and do not offer times.
        Only say what you know. If you do not know, say so in your own words. Never invent a price, hours, or a time.
        When you know enough to offer a visit, call check_availability and offer only the times it returns.
        When they pick one of those times, call book_appointment with that starts_at value.
        If they are not a fit, say so plainly and set status to closed.

        What you know:
        {$known}
        TEXT;
    }

    public function tools(): iterable
    {
        return [
            new GetBusinessProfile($this->lead->business),
            new CheckAvailability($this->lead),
            new BookAppointment($this->lead),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'reply' => $schema->string()->required(),
            'status' => $schema->string()->enum(['waiting', 'qualified', 'booked', 'closed'])->required(),
            'service_requested' => $schema->string()->required(),
            'location' => $schema->string()->required(),
            'intent' => $schema->string()->required(),
            'urgency' => $schema->string()->enum(['low', 'medium', 'high', ''])->required(),
        ];
    }
}
