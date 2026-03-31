<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringWebService extends Model
{
    protected $guarded = [];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
