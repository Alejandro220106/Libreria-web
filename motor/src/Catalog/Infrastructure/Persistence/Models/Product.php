<?php

namespace Src\Catalog\Infrastructure\Persistence\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// The model lives outside App\Models, so its factory is named explicitly.
#[UseFactory(ProductFactory::class)]
class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'price' => 'integer',
            'source_updated_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
