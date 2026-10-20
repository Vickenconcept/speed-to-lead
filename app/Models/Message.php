<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lead_id
 * @property string $role
 * @property string $body
 */
#[Fillable(['lead_id', 'role', 'body'])]
class Message extends Model
{
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
