<?php

namespace App\Http\Controllers;

use App\Services\Leads\LeadIntake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteSourceController extends Controller
{
    public function show(Request $request): Response
    {
        $business = $request->user()->business;

        return Inertia::render('sources/website', [
            'form_url' => route('intake.show', $business->intake_token),
            'webhook_url' => route('hooks.website', $business->intake_token),
        ]);
    }

    public function sample(Request $request, LeadIntake $intake): RedirectResponse
    {
        $lead = $intake->receive($request->user()->business, [
            'name' => 'John Smith',
            'phone' => '+1 512 555 0199',
            'email' => 'john@example.com',
            'message' => 'My roof is leaking and I need someone to come look at it.',
            'source' => 'website',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sample lead received.']);

        return redirect()->route('leads.show', $lead);
    }
}
