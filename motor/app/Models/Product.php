<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
