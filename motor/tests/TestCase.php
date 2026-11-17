<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PDO;

abstract class TestCase extends BaseTestCase
{
    private static bool $testDatabaseReady = false;

    /**
     * Runs after the application boots and before RefreshDatabase migrates.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        if (! self::$testDatabaseReady) {
            $this->createTestDatabaseIfMissing();
            self::$testDatabaseReady = true;
        }

        return parent::setUpTraits();
    }

    /**
     * A fresh clone only has the server: create the test database (phpunit.xml) once,
     * so `php artisan test` needs no manual step (brief 6.2.2 and 6.2.4).
     */
    private function createTestDatabaseIfMissing(): void
    {
        $config = config('database.connections.pgsql_admin');
        $name = (string) $config['database'];

        $server = new PDO(
            "pgsql:host={$config['host']};port={$config['port']};dbname=postgres",
            (string) $config['username'],
            (string) $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $exists = $server->prepare('select 1 from pg_database where datname = ?');
        $exists->execute([$name]);

        if ($exists->fetchColumn() === false) {
            $server->exec('create database "'.str_replace('"', '""', $name).'"');
        }
    }
}
