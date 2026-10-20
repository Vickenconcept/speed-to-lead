<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $business_id
 * @property int $lead_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string $status
 * @property string $summary
 */
#[Fillable([
    'business_id',
    'lead_id',
    'starts_at',
    'ends_at',
    'status',
    'summary',
])]
class Appointment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function label(): string
    {
        $start = $this->starts_at->timezone($this->business->timezone);
        $day = $start->isTomorrow() ? 'Tomorrow' : $start->format('D, M j');

        return $day.' '.$start->format('g:i A');
    }
}
