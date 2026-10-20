<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $business_id
 * @property string $public_token
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string $source
 * @property string $message
 * @property string|null $service_requested
 * @property string|null $location
 * @property string|null $intent
 * @property string|null $urgency
 * @property string $status
 * @property string|null $awaiting
 * @property array<int, array{starts_at: string, label: string}>|null $offered_slots
 * @property string|null $qualification_notes
 */
#[Fillable([
    'business_id',
    'public_token',
    'name',
    'email',
    'phone',
    'source',
    'message',
    'service_requested',
    'location',
    'intent',
    'urgency',
    'status',
    'awaiting',
    'offered_slots',
    'qualification_notes',
])]
class Lead extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'offered_slots' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class)->latestOfMany();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'qualified' => 'Qualified',
            'booked' => 'Appointment booked',
            'closed' => 'Closed',
            default => 'Waiting for response',
        };
    }

    public function firstName(): string
    {
        return str($this->name)->before(' ')->toString() ?: $this->name;
    }
}
