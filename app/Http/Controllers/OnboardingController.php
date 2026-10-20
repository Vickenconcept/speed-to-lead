<?php

namespace App\Http\Controllers;

use App\Http\LeadView;
use App\Models\Business;
use App\Services\BusinessImport\ProfileWriter;
use App\Services\BusinessImport\WebsiteImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->business?->isReady()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('onboarding/index', [
            'ai' => filled(config('ai.providers.openrouter.key')),
        ]);
    }

    public function website(Request $request, WebsiteImporter $importer): RedirectResponse
    {
        abort_if($request->user()->business?->isReady(), 404);

        $data = $request->validate([
            'url' => ['required', 'string', 'max:2000'],
        ]);

        $importer->import($request->user(), $data['url'], 'website');

        return redirect()->route('onboarding.review');
    }

    public function google(Request $request, WebsiteImporter $importer): RedirectResponse
    {
        abort_if($request->user()->business?->isReady(), 404);

        $data = $request->validate([
            'url' => ['required', 'string', 'max:2000'],
        ]);

        $this->assertGoogleLink($data['url']);
        $importer->import($request->user(), $data['url'], 'google');

        return redirect()->route('onboarding.review');
    }

    public function manual(Request $request, ProfileWriter $profiles): RedirectResponse
    {
        abort_if($request->user()->business?->isReady(), 404);

        $data = $this->validatedProfile($request);
        $business = $this->draft($request);
        $profiles->apply($business, $data);

        return redirect()->route('onboarding.review');
    }

    public function review(Request $request): Response|RedirectResponse
    {
        $business = $request->user()->business;

        if ($business === null) {
            return redirect()->route('onboarding.show');
        }

        if ($business->isReady()) {
            return redirect()->route('dashboard');
        }

        $business->load('facts');

        return Inertia::render('onboarding/review', [
            'business' => LeadView::business($business),
        ]);
    }

    public function update(Request $request, ProfileWriter $profiles): RedirectResponse
    {
        $business = $request->user()->business;

        abort_if($business === null, 404);

        $profiles->apply($business, $this->validatedProfile($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile saved.']);

        return back();
    }

    public function confirm(Request $request, ProfileWriter $profiles): RedirectResponse
    {
        $business = $request->user()->business;

        abort_if($business === null, 404);

        $profiles->apply($business, $this->validatedProfile($request));
        $business->forceFill(['confirmed_at' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Your business is ready.']);

        return redirect()->route('sources.website');
    }

    private function assertGoogleLink(string $url): void
    {
        if (preg_match('/google\.|goo\.gl|g\.page|maps\.app/i', $url) !== 1) {
            throw ValidationException::withMessages([
                'url' => 'Paste a Google Maps or Google Business link.',
            ]);
        }
    }

    private function draft(Request $request): Business
    {
        return $request->user()->business()->first() ?? $request->user()->business()->create([
            'name' => 'Your business',
            'hours' => Business::defaultHours(),
            'intake_token' => Str::random(40),
            'timezone' => 'America/Chicago',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProfile(Request $request): array
    {
        return $request->validate([
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
    }
}
