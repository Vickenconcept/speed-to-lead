<?php

namespace App\Http\Controllers;

use App\Http\LeadView;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $business = $request->user()->business;
        $leads = $business->leads()->with('appointment')->latest()->get();

        return Inertia::render('dashboard', [
            'business' => [
                'name' => $business->name,
            ],
            'counts' => [
                'waiting' => $leads->where('status', 'waiting')->count(),
                'qualified' => $leads->where('status', 'qualified')->count(),
                'booked' => $leads->where('status', 'booked')->count(),
            ],
            'leads' => $leads->map(fn ($lead) => LeadView::summary($lead))->values(),
        ]);
    }
}
