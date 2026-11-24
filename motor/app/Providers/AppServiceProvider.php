<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Settings for the whole engine. Each module connects its own ports in its
 * service provider (src/<Module>/Infrastructure/<Module>ServiceProvider.php).
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dates never change in place: addDay() returns a new instance.
        Date::use(CarbonImmutable::class);

        // Outside production, fail on lazy loading (N+1), unknown attributes and blocked mass assignment.
        Model::shouldBeStrict(! $this->app->isProduction());

        $this->runMigrationsAsSchemaOwner();
    }

    /**
     * The application role cannot create tables, so `php artisan migrate --seed` (the
     * documented startup step) switches to the schema owner by itself. Passing
     * --database still wins. Only the console migrate commands change; requests
     * always use the application role.
     */
    private function runMigrationsAsSchemaOwner(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if (str_starts_with((string) $event->command, 'migrate')
                && ! $event->input->hasParameterOption('--database')
                && config('database.default') === 'pgsql') {
                config(['database.default' => 'pgsql_admin']);
            }
        });
    }
}
