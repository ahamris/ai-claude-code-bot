<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthCheck extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reachable' => 'boolean',
            'latency_ms' => 'decimal:2',
            'checked_at' => 'datetime',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
