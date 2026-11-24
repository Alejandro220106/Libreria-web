<?php

namespace Src\Orders\Infrastructure\Persistence\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// The model lives outside App\Models, so its factory is named explicitly.
/** A store order copied into the engine. Customer personal data is never stored here. */
#[UseFactory(OrderFactory::class)]
class Order extends Model
{
    use HasFactory;

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
