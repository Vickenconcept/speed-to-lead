<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\Leads\LeadIntake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicConversationController extends Controller
{
    public function show(string $token): Response
    {
        $lead = $this->lead($token);

        return Inertia::render('public/conversation', [
            'business' => [
                'name' => $lead->business->name,
            ],
            'lead' => [
                'name' => $lead->firstName(),
                'status' => $lead->status,
                'appointment' => $lead->appointment?->label(),
            ],
            'messages' => $lead->messages->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'body' => $message->body,
            ])->values(),
        ]);
    }

    public function store(Request $request, string $token, LeadIntake $intake): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $intake->customerReply($this->lead($token), $data['body']);

        return back();
    }

    private function lead(string $token): Lead
    {
        return Lead::query()
            ->where('public_token', $token)
            ->with(['business', 'messages', 'appointment'])
            ->firstOrFail();
    }
}
