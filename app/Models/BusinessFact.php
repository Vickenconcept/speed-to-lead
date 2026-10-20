<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $business_id
 * @property string $category
 * @property string $label
 * @property string $value
 * @property string $source
 * @property string $confidence
 */
#[Fillable([
    'business_id',
    'category',
    'label',
    'value',
    'source',
    'confidence',
])]
class BusinessFact extends Model
{
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
