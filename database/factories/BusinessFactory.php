<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'ABC Roofing',
            'phone' => '+1 512 555 0148',
            'email' => fake()->companyEmail(),
            'website' => 'https://abc-roofing.example',
            'description' => 'Residential roof repair, replacement, and inspections.',
            'city' => 'Austin',
            'region' => 'Texas',
            'timezone' => 'America/Chicago',
            'hours' => Business::defaultHours(),
            'slot_minutes' => 60,
            'notice_hours' => 2,
            'intake_token' => Str::random(40),
            'confirmed_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'confirmed_at' => null,
        ]);
    }
}
