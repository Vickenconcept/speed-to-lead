<?php

namespace App\Services\BusinessImport;

use App\Models\Business;

class ProfileWriter
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function apply(Business $business, array $input, string $source = 'owner'): void
    {
        $opens = (string) ($input['opens'] ?? '08:00');
        $closes = (string) ($input['closes'] ?? '18:00');

        $business->fill([
            'name' => $input['name'],
            'phone' => $input['phone'] ?? null,
            'website' => $input['website'] ?? $business->website,
            'description' => $input['description'] ?? null,
            'city' => $input['city'] ?? null,
            'region' => $input['region'] ?? null,
            'timezone' => $input['timezone'] ?? $business->timezone ?? 'America/Chicago',
            'hours' => collect(range(1, 5))->map(fn (int $day) => [
                'day' => $day,
                'opens' => $opens,
                'closes' => $closes,
            ])->all(),
        ])->save();

        $business->facts()->where('category', 'service')->delete();

        foreach ($this->lines((string) ($input['services'] ?? '')) as $service) {
            $business->facts()->create([
                'category' => 'service',
                'label' => $service,
                'value' => $service,
                'source' => $source,
                'confidence' => 'high',
            ]);
        }

        $notes = trim((string) ($input['notes'] ?? ''));

        if ($notes !== '') {
            $business->facts()->updateOrCreate(
                ['category' => 'other', 'label' => 'Notes'],
                [
                    'value' => $notes,
                    'source' => $source,
                    'confidence' => 'high',
                ],
            );
        }
    }

    /**
     * @return array<int, string>
     */
    public function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
