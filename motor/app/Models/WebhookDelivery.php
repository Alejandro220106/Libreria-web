<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One row per processed webhook delivery; delivery_id is unique. */
class WebhookDelivery extends Model
{
    protected $fillable = ['delivery_id', 'topic', 'processed_at'];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }
}
