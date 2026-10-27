<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A store order copied into the engine. Customer personal data is never stored here. */
class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'lines' => 'array',
            'address_normalized' => 'array',
            'connector_updated_at' => 'datetime',
            'ordered_at' => 'datetime',
            'source_updated_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
