<?php

namespace App\Services\Leads;

use App\Models\Business;
use App\Models\Lead;
use App\Models\Message;
use Illuminate\Support\Str;

class LeadIntake
{
    public function __construct(private LeadConversation $conversation) {}

    /**
     * @param  array{name: string, email?: string|null, phone?: string|null, message: string, source?: string}  $input
     */
    public function receive(Business $business, array $input): Lead
    {
        $lead = Lead::query()->create([
            'business_id' => $business->id,
            'public_token' => Str::random(40),
            'name' => $input['name'],
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'source' => $input['source'] ?? 'website',
            'message' => $input['message'],
            'status' => 'waiting',
        ]);

        Message::query()->create([
            'lead_id' => $lead->id,
            'role' => 'customer',
            'body' => $input['message'],
        ]);

        $this->conversation->respond($lead);

        return $lead->refresh();
    }

    public function customerReply(Lead $lead, string $body): Lead
    {
        Message::query()->create([
            'lead_id' => $lead->id,
            'role' => 'customer',
            'body' => $body,
        ]);

        $this->conversation->respond($lead);

        return $lead->refresh();
    }
}
