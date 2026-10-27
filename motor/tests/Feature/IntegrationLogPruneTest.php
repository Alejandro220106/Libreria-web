<?php

use App\Models\IntegrationLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function logAt(string $when): IntegrationLog
{
    return IntegrationLog::create([
        'direction' => 'outgoing',
        'system' => 'store',
        'operation' => 'products.list',
        'status' => 'success',
        'logged_at' => $when,
    ]);
}

it('deletes records older than the retention period and keeps recent ones', function () {
    config(['integration_log.retention_days' => 30]);
    $old = logAt(now()->subDays(31)->toDateTimeString());
    $recent = logAt(now()->subDays(29)->toDateTimeString());

    $this->artisan('model:prune', ['--model' => [IntegrationLog::class]])->assertSuccessful();

    expect(IntegrationLog::find($old->id))->toBeNull()
        ->and(IntegrationLog::find($recent->id))->not->toBeNull();
});

it('reads the retention period from configuration', function () {
    config(['integration_log.retention_days' => 5]);
    $old = logAt(now()->subDays(6)->toDateTimeString());

    $this->artisan('model:prune', ['--model' => [IntegrationLog::class]]);

    expect(IntegrationLog::find($old->id))->toBeNull();
});

it('schedules the prune every day', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains($event->command, 'model:prune'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 0 * * *');
});
