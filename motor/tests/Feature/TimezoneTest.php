<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

const COSTA_RICA_TIMEZONE = 'America/Costa_Rica';

it('uses the Costa Rica timezone in the configuration', function () {
    expect(config('app.timezone'))->toBe(COSTA_RICA_TIMEZONE);
});

it('uses the Costa Rica timezone in PHP', function () {
    expect(date_default_timezone_get())->toBe(COSTA_RICA_TIMEZONE)
        ->and(now()->timezoneName)->toBe(COSTA_RICA_TIMEZONE);
});

it('uses the Costa Rica timezone in the database session', function () {
    expect(DB::scalar('show timezone'))->toBe(COSTA_RICA_TIMEZONE);
});
