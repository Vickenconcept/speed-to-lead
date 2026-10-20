<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\Leads\LeadIntake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicIntakeController extends Controller
{
    public function show(string $token): Response
    {
        $business = $this->business($token);

        return Inertia::render('public/intake', [
            'business' => [
                'name' => $business->name,
                'city' => $business->servingLabel(),
                'description' => $business->description,
            ],
        ]);
    }

    public function store(Request $request, string $token, LeadIntake $intake): RedirectResponse
    {
        $business = $this->business($token);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $lead = $intake->receive($business, [
            ...$data,
            'source' => 'website',
        ]);

        return redirect()->route('conversation.show', $lead->public_token);
    }

    private function business(string $token): Business
    {
        return Business::query()
            ->where('intake_token', $token)
            ->whereNotNull('confirmed_at')
            ->firstOrFail();
    }
}
