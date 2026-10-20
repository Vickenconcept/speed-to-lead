<?php

namespace App\Services\BusinessImport;

use App\Ai\Agents\BusinessProfileAgent;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class WebsiteImporter
{
    public function __construct(private PageReader $pages, private GoogleMapsReader $maps) {}

    public function import(User $user, string $url, string $source = 'website', bool $keepReady = false): Business
    {
        $url = Str::startsWith($url, ['http://', 'https://']) ? $url : 'https://'.$url;
        $place = null;

        if ($source === 'google') {
            $place = $this->maps->read($url);

            if ($place['name'] === '') {
                throw ValidationException::withMessages([
                    'url' => 'We could not open that Google link. Paste the public Maps or Business URL, or enter the details yourself.',
                ]);
            }

            $html = '<title>'.e($place['name']).'</title><meta name="description" content="'.e($place['text']).'">';
            $text = $place['text'];
        } else {
            try {
                $html = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'SpeedToLead/1.0'])
                    ->get($url)
                    ->throw()
                    ->body();
            } catch (Throwable) {
                throw ValidationException::withMessages([
                    'url' => 'We could not read that website. Check the address, or enter the details yourself.',
                ]);
            }

            $text = $this->pages->textFrom($html, $url);

            if (strlen($text) < 400) {
                $rendered = $this->pages->render($url);

                if ($rendered !== null) {
                    $html = $rendered;
                    $text = $this->pages->visibleText($rendered);
                }
            }
        }

        $extracted = $this->extract($html, $text, $url, $source);

        $business = $user->business()->first() ?? new Business([
            'user_id' => $user->id,
            'intake_token' => Str::random(40),
            'hours' => Business::defaultHours(),
        ]);

        if ($business->intake_token === null) {
            $business->intake_token = Str::random(40);
        }

        $business->fill([
            'name' => $extracted['name'] !== '' ? $extracted['name'] : 'Your business',
            'phone' => $extracted['phone'] ?: ($place['phone'] ?? null),
            'website' => $source === 'website' ? $url : ($business->website ?: null),
            'description' => $extracted['description'] ?: $business->description,
            'city' => $extracted['city'] ?: $business->city,
            'region' => $extracted['region'] ?: $business->region,
            'timezone' => ($place['timezone'] ?? '') !== '' ? $place['timezone'] : ($business->timezone ?: 'America/Chicago'),
            'hours' => $place['hours'] ?? ($business->hours ?: Business::defaultHours()),
        ]);

        if (! $keepReady) {
            $business->confirmed_at = null;
        }

        $business->save();

        $business->facts()->delete();

        foreach ($extracted['facts'] as $fact) {
            $business->facts()->create($fact);
        }

        if ($source === 'google') {
            $business->facts()->create([
                'category' => 'other',
                'label' => 'Google link',
                'value' => $url,
                'source' => 'google',
                'confidence' => 'high',
            ]);
        }

        return $business;
    }

    /**
     * @return array{name: string, phone: string, description: string, city: string, region: string, facts: array<int, array<string, string>>}
     */
    private function extract(string $html, string $text, string $url, string $source): array
    {
        $title = $this->match($html, '/<title[^>]*>(.*?)<\/title>/is');
        $description = $this->match($html, '/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i');
        $text = Str::limit($text, 12000, '');

        $base = [
            'name' => Str::limit(trim(html_entity_decode(strip_tags($title))), 120, ''),
            'phone' => '',
            'description' => trim(html_entity_decode($description)),
            'city' => '',
            'region' => '',
            'facts' => [],
        ];

        if ($base['name'] === '') {
            $base['name'] = parse_url($url, PHP_URL_HOST) ?: 'Your business';
        }

        $thin = strlen($text) < 400;

        if ($thin || ! filled(config('ai.providers.openrouter.key'))) {
            if ($base['description'] !== '') {
                $base['facts'][] = [
                    'category' => 'other',
                    'label' => 'Description',
                    'value' => $base['description'],
                    'source' => $source,
                    'confidence' => 'medium',
                ];
            }

            if ($thin && $source !== 'google' && filled(config('ai.providers.openrouter.key'))) {
                throw ValidationException::withMessages([
                    'url' => 'We opened the page, but it still did not show the business details. Enter them yourself.',
                ]);
            }

            return $base;
        }

        try {
            $response = (new BusinessProfileAgent)->prompt("Source: {$source}\nURL: {$url}\n\n{$text}");

            $name = trim((string) ($response['name'] ?? ''));
            $services = collect($response['services'] ?? [])->filter(fn ($service) => filled($service));

            $facts = $services->map(fn ($service) => [
                'category' => 'service',
                'label' => (string) $service,
                'value' => (string) $service,
                'source' => $source,
                'confidence' => 'high',
            ])->all();

            foreach ($response['faqs'] ?? [] as $faq) {
                if (! filled($faq['question'] ?? null)) {
                    continue;
                }

                $facts[] = [
                    'category' => 'faq',
                    'label' => (string) $faq['question'],
                    'value' => (string) ($faq['answer'] ?? ''),
                    'source' => $source,
                    'confidence' => 'medium',
                ];
            }

            foreach ($response['facts'] ?? [] as $fact) {
                if (! filled($fact['label'] ?? null) || ! filled($fact['value'] ?? null)) {
                    continue;
                }

                $facts[] = [
                    'category' => (string) ($fact['category'] ?? 'other'),
                    'label' => (string) $fact['label'],
                    'value' => (string) $fact['value'],
                    'source' => $source,
                    'confidence' => (string) ($fact['confidence'] ?? 'medium'),
                ];
            }

            return [
                'name' => $name !== '' ? $name : $base['name'],
                'phone' => trim((string) ($response['phone'] ?? '')),
                'description' => trim((string) ($response['description'] ?? '')) ?: $base['description'],
                'city' => trim((string) ($response['city'] ?? '')),
                'region' => trim((string) ($response['region'] ?? '')),
                'facts' => $facts,
            ];
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'url' => $this->failureMessage($exception),
            ]);
        }
    }

    private function outOfCredits(Throwable $exception): bool
    {
        $detail = $exception->getPrevious()?->getMessage() ?? $exception->getMessage();

        return str_contains($detail, 'no credits') || str_contains($detail, 'insufficient_quota');
    }

    private function failureMessage(Throwable $exception): string
    {
        $detail = strtolower($exception->getPrevious()?->getMessage() ?? $exception->getMessage());

        if (str_contains($detail, 'rate') || str_contains($detail, '429')) {
            return 'The free model is busy right now. Wait a moment and paste the link again, or enter the business yourself.';
        }

        if ($this->outOfCredits($exception)) {
            return 'That request needs paid credits. This app uses OpenRouter’s free model. Check OPENROUTER_API_KEY, then paste the link again.';
        }

        return 'We could not read that page. Check the link, or enter the business yourself.';
    }

    private function match(string $html, string $pattern): string
    {
        if (preg_match($pattern, $html, $matches) !== 1) {
            return '';
        }

        return trim(html_entity_decode(strip_tags($matches[1])));
    }
}
