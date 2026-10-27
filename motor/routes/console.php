<?php

use App\Models\IntegrationLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Delete integration-log records past the retention period (#78).
Schedule::command('model:prune', ['--model' => [IntegrationLog::class]])->daily();
