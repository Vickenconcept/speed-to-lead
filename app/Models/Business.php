<?php

namespace App\Models;

use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $description
 * @property string|null $city
 * @property string|null $region
 * @property string $timezone
 * @property array<int, array{day: int, opens: string, closes: string}> $hours
 * @property int $slot_minutes
 * @property int $notice_hours
 * @property string $intake_token
 * @property Carbon|null $confirmed_at
 */
#[Fillable([
    'user_id',
    'name',
    'phone',
    'email',
    'website',
    'description',
    'city',
    'region',
    'timezone',
    'hours',
    'slot_minutes',
    'notice_hours',
    'intake_token',
    'confirmed_at',
])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hours' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function facts(): HasMany
    {
        return $this->hasMany(BusinessFact::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function isReady(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function servingLabel(): string
    {
        return collect([$this->city, $this->region])->filter()->implode(', ');
    }

    public function hoursLabel(): string
    {
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $groups = [];

        foreach ($this->hours as $window) {
            $key = $window['opens'].'-'.$window['closes'];
            $groups[$key][] = $days[$window['day']] ?? '?';
        }

        if ($groups === []) {
            return 'Hours not set';
        }

        return collect($groups)
            ->map(function (array $names, string $key) {
                [$opens, $closes] = explode('-', $key);

                return implode(', ', $names).' '.Carbon::parse($opens)->format('g:i A').' – '.Carbon::parse($closes)->format('g:i A');
            })
            ->implode(' · ');
    }

    public function profileForAgent(): string
    {
        $facts = $this->facts()
            ->orderBy('category')
            ->get()
            ->map(fn (BusinessFact $fact) => "{$fact->category}: {$fact->label} — {$fact->value} (source: {$fact->source}, confidence: {$fact->confidence})")
            ->implode("\n");

        return trim(<<<TEXT
        Business: {$this->name}
        Phone: {$this->phone}
        Website: {$this->website}
        Description: {$this->description}
        Serving: {$this->servingLabel()}
        Hours: {$this->hoursLabel()}
        Appointment length: {$this->slot_minutes} minutes
        Facts:
        {$facts}
        TEXT);
    }

    /**
     * @return array<int, array{day: int, opens: string, closes: string}>
     */
    public static function defaultHours(): array
    {
        return collect(range(1, 5))
            ->map(fn (int $day) => [
                'day' => $day,
                'opens' => '08:00',
                'closes' => '18:00',
            ])
            ->all();
    }
}
