<?php

namespace App\Http;

use App\Models\Business;
use App\Models\Lead;

class LeadView
{
    /**
     * @return array<string, mixed>
     */
    public static function summary(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone,
            'service' => $lead->service_requested,
            'status' => $lead->status,
            'status_label' => $lead->statusLabel(),
            'appointment' => $lead->appointment?->label(),
            'created' => $lead->created_at?->diffForHumans(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(Lead $lead): array
    {
        return [
            ...self::summary($lead),
            'email' => $lead->email,
            'location' => $lead->location,
            'intent' => $lead->intent,
            'urgency' => $lead->urgency,
            'source' => $lead->source,
            'message' => $lead->message,
            'conversation_url' => route('conversation.show', $lead->public_token),
            'messages' => $lead->messages->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'body' => $message->body,
                'at' => $message->created_at?->format('g:i A'),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function business(Business $business): array
    {
        $window = collect($business->hours)->first();

        return [
            'name' => $business->name,
            'phone' => $business->phone ?? '',
            'website' => $business->website ?? '',
            'description' => $business->description ?? '',
            'city' => $business->city ?? '',
            'region' => $business->region ?? '',
            'timezone' => $business->timezone,
            'opens' => $window['opens'] ?? '08:00',
            'closes' => $window['closes'] ?? '18:00',
            'hours_label' => $business->hoursLabel(),
            'serving' => $business->servingLabel(),
            'services' => $business->facts->where('category', 'service')->pluck('label')->implode("\n"),
            'notes' => (string) ($business->facts->firstWhere('label', 'Notes')?->value ?? ''),
            'facts' => $business->facts
                ->reject(fn ($fact) => $fact->category === 'service' || $fact->label === 'Notes')
                ->map(fn ($fact) => [
                    'id' => $fact->id,
                    'category' => $fact->category,
                    'label' => $fact->label,
                    'value' => $fact->value,
                    'source' => $fact->source,
                    'confidence' => $fact->confidence,
                ])
                ->values()
                ->all(),
        ];
    }
}
