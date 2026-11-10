<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Runs before every other migration, as the schema owner (`--database=pgsql_admin`),
 * so the application role can read and write the tables without owning them.
 */
return new class extends Migration
{
    /** Grant data access on the tables and sequences that exist now and on every future one. */
    public function up(): void
    {
        $role = $this->applicationRole();

        DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO {$role}");
        DB::statement("GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO {$role}");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO {$role}");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO {$role}");
    }

    /** Stop granting privileges on future objects and remove the current ones. */
    public function down(): void
    {
        $role = $this->applicationRole();

        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE SELECT, INSERT, UPDATE, DELETE ON TABLES FROM {$role}");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE USAGE, SELECT ON SEQUENCES FROM {$role}");
        DB::statement("REVOKE SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public FROM {$role}");
        DB::statement("REVOKE USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public FROM {$role}");
    }

    /** Name of the role the application connects with, quoted as a PostgreSQL identifier. */
    private function applicationRole(): string
    {
        $name = config('database.connections.pgsql.username');

        return '"'.str_replace('"', '""', is_string($name) ? $name : '').'"';
    }
};
