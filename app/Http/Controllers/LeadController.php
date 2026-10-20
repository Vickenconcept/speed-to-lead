<?php

namespace App\Http\Controllers;

use App\Http\LeadView;
use App\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function show(Request $request, Lead $lead): Response
    {
        abort_unless($lead->business_id === $request->user()->business?->id, 404);

        $lead->load(['messages', 'appointment']);

        return Inertia::render('leads/show', [
            'lead' => LeadView::detail($lead),
        ]);
    }
}
