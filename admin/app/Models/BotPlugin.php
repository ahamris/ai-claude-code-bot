<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotPlugin extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'config' => 'array',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
