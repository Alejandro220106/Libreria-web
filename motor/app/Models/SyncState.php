<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Remembers when each kind of sync last finished well, so the next run can ask only for changes. */
class SyncState extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_succeeded_at' => 'datetime',
        ];
    }
}
