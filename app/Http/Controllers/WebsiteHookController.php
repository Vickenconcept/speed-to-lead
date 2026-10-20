<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\Leads\LeadIntake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebsiteHookController extends Controller
{
    public function __invoke(Request $request, string $token, LeadIntake $intake): JsonResponse
    {
        $business = Business::query()
            ->where('intake_token', $token)
            ->whereNotNull('confirmed_at')
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $lead = $intake->receive($business, [
            ...$data,
            'source' => 'website',
        ]);

        return response()->json([
            'lead_id' => $lead->id,
            'status' => $lead->status,
            'conversation_url' => route('conversation.show', $lead->public_token),
        ]);
    }
}
