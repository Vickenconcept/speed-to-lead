<?php

namespace App\Services\BusinessImport;

use Illuminate\Support\Facades\Http;
use Throwable;

class GoogleMapsReader
{
    /**
     * Public place details from a Maps or Business link.
     *
     * @return array{name: string, phone: string, timezone: string, hours: array<int, array{day: int, opens: string, closes: string}>|null, text: string}
     */
    public function read(string $url): array
    {
        $empty = [
            'name' => '',
            'phone' => '',
            'timezone' => '',
            'hours' => null,
            'text' => '',
        ];

        try {
            $html = $this->fetch($url);
        } catch (Throwable) {
            return $empty;
        }

        $preview = $this->previewUrl($html);

        if ($preview === null) {
            return $empty;
        }

        try {
            $payload = $this->fetch($preview);
        } catch (Throwable) {
            return $empty;
        }

        return $this->describe($payload);
    }

    private function fetch(string $url): string
    {
        $response = Http::timeout(25)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'en',
                'Cookie' => 'CONSENT=YES+; SOCS=CAESHAgBEhJnd3NfMjAyMzA4MTAtMF9SQzIaAmVuIAEaBgiA_LyaBg',
            ])
            ->get($url)
            ->throw();

        $effective = (string) $response->effectiveUri();
        $host = strtolower((string) parse_url($effective, PHP_URL_HOST));

        if (! str_contains($host, 'consent.google.')) {
            return $response->body();
        }

        $query = parse_url($effective, PHP_URL_QUERY) ?: '';
        parse_str($query, $params);
        $continue = (string) ($params['continue'] ?? '');
        $continueHost = strtolower((string) parse_url($continue, PHP_URL_HOST));

        if (! str_ends_with($continueHost, 'google.com')) {
            return $response->body();
        }

        return Http::timeout(25)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'en',
                'Cookie' => 'CONSENT=YES+; SOCS=CAESHAgBEhJnd3NfMjAyMzA4MTAtMF9SQzIaAmVuIAEaBgiA_LyaBg',
            ])
            ->get($continue)
            ->throw()
            ->body();
    }

    private function previewUrl(string $html): ?string
    {
        if (preg_match('/href="(\/maps\/preview\/place\?[^"]+)"/', $html, $matches) !== 1) {
            return null;
        }

        return 'https://www.google.com'.html_entity_decode($matches[1]);
    }

    /**
     * @return array{name: string, phone: string, timezone: string, hours: array<int, array{day: int, opens: string, closes: string}>|null, text: string}
     */
    private function describe(string $payload): array
    {
        $json = substr($payload, (int) strpos($payload, '['));
        $data = json_decode($json, true);
        $place = is_array($data) ? $this->placeNode($data) : null;

        if ($place === null) {
            return [
                'name' => '',
                'phone' => '',
                'timezone' => '',
                'hours' => null,
                'text' => '',
            ];
        }

        $name = $this->name($place);
        $categories = $this->categories($place, $name);
        $phone = $this->phone($place);
        $timezone = $this->timezone($place);
        $description = $this->description($place);
        $openAllDay = $this->openAllDay($place);
        $hours = $openAllDay ? $this->allDay() : null;

        $lines = array_filter([
            $name !== '' ? 'Name: '.$name : '',
            $categories !== [] ? 'Categories: '.implode(', ', $categories) : '',
            $phone !== '' ? 'Phone: '.$phone : '',
            $openAllDay ? 'Hours: Open 24 hours' : '',
            $timezone !== '' ? 'Timezone: '.$timezone : '',
            $description !== '' ? 'Description: '.$description : '',
        ]);

        return [
            'name' => $name,
            'phone' => $phone,
            'timezone' => $timezone,
            'hours' => $hours,
            'text' => implode("\n", $lines),
        ];
    }

    /**
     * @param  array<mixed>  $node
     * @return array<mixed>|null
     */
    private function placeNode(array $node): ?array
    {
        foreach ($node as $child) {
            if (is_string($child) && preg_match('/^0x[0-9a-f]+:0x[0-9a-f]+$/i', $child) === 1) {
                return $node;
            }
        }

        foreach ($node as $child) {
            if (! is_array($child)) {
                continue;
            }

            $found = $this->placeNode($child);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $place
     */
    private function name(array $place): string
    {
        $afterId = false;

        foreach ($place as $value) {
            if (is_string($value) && preg_match('/^0x[0-9a-f]+:0x[0-9a-f]+$/i', $value) === 1) {
                $afterId = true;

                continue;
            }

            if ($afterId && is_string($value) && $this->readable($value)) {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param  array<mixed>  $place
     * @return array<int, string>
     */
    private function categories(array $place, string $name): array
    {
        $afterName = $name === '';

        foreach ($place as $value) {
            if (! $afterName) {
                $afterName = $value === $name;

                continue;
            }

            if (! is_array($value) || array_is_list($value) === false || $value === []) {
                continue;
            }

            $labels = array_values(array_filter($value, fn (mixed $item) => is_string($item) && $this->readable($item) && ! str_contains($item, '/')));

            if (count($labels) === count($value) && count($labels) <= 6) {
                return $labels;
            }
        }

        return [];
    }

    /**
     * @param  array<mixed>  $place
     */
    private function phone(array $place): string
    {
        $numbers = [];
        $this->walk($place, function (string $value) use (&$numbers): void {
            if (preg_match('/^\+\d[\d\s\-()]{8,}$/', $value) === 1 || str_starts_with($value, 'tel:')) {
                $numbers[] = str_starts_with($value, 'tel:') ? substr($value, 4) : $value;
            }
        });

        foreach ($numbers as $number) {
            if (str_starts_with($number, '+')) {
                return $number;
            }
        }

        return $numbers[0] ?? '';
    }

    /**
     * @param  array<mixed>  $place
     */
    private function timezone(array $place): string
    {
        $found = '';
        $this->walk($place, function (string $value) use (&$found): void {
            if ($found === '' && in_array($value, timezone_identifiers_list(), true)) {
                $found = $value;
            }
        });

        return $found;
    }

    /**
     * @param  array<mixed>  $place
     */
    private function description(array $place): string
    {
        $best = '';
        $this->walk($place, function (string $value) use (&$best): void {
            if (strlen($value) > strlen($best) && strlen($value) > 40 && str_contains($value, '. ') && ! str_contains($value, 'http')) {
                $best = $value;
            }
        });

        return $best;
    }

    /**
     * @param  array<mixed>  $place
     */
    private function openAllDay(array $place): bool
    {
        $encoded = json_encode($place) ?: '';

        return str_contains($encoded, 'Open 24 hours')
            && preg_match('/Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday/', $encoded) === 1;
    }

    /**
     * @return array<int, array{day: int, opens: string, closes: string}>
     */
    private function allDay(): array
    {
        return collect(range(0, 6))
            ->map(fn (int $day) => [
                'day' => $day,
                'opens' => '00:00',
                'closes' => '23:59',
            ])
            ->all();
    }

    private function readable(string $value): bool
    {
        return strlen($value) >= 2
            && strlen($value) <= 120
            && preg_match('/[A-Za-z]/', $value) === 1
            && ! str_contains($value, 'http')
            && preg_match('/^[A-Za-z0-9_\-]{18,}$/', $value) !== 1;
    }

    /**
     * @param  array<mixed>  $node
     */
    private function walk(array $node, callable $onString): void
    {
        foreach ($node as $value) {
            if (is_string($value)) {
                $onString($value);
            } elseif (is_array($value)) {
                $this->walk($value, $onString);
            }
        }
    }
}
