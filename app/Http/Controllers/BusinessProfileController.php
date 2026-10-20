<?php

namespace App\Http\Controllers;

use App\Http\LeadView;
use App\Services\BusinessImport\ProfileWriter;
use App\Services\BusinessImport\WebsiteImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BusinessProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $business = $request->user()->business->load('facts');

        return Inertia::render('business/profile', [
            'business' => LeadView::business($business),
        ]);
    }

    public function update(Request $request, ProfileWriter $profiles): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'timezone' => ['required', 'timezone'],
            'opens' => ['required', 'date_format:H:i'],
            'closes' => ['required', 'date_format:H:i', 'after:opens'],
            'services' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $profiles->apply($request->user()->business, $data);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Business profile updated.']);

        return back();
    }

    public function website(Request $request, WebsiteImporter $importer): RedirectResponse
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:2000'],
        ]);

        $importer->import($request->user(), $data['url'], 'website', keepReady: true);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Read the website.']);

        return back();
    }

    public function google(Request $request, WebsiteImporter $importer): RedirectResponse
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:2000'],
        ]);

        if (preg_match('/google\.|goo\.gl|g\.page|maps\.app/i', $data['url']) !== 1) {
            throw ValidationException::withMessages([
                'url' => 'Paste a Google Maps or Google Business link.',
            ]);
        }

        $importer->import($request->user(), $data['url'], 'google', keepReady: true);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Read the Google profile.']);

        return back();
    }
}
