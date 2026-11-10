<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\RefreshDatabaseAsOwner;

uses(RefreshDatabaseAsOwner::class);

it('connects with a role that has no elevated attributes', function () {
    $role = DB::selectOne('select rolsuper, rolbypassrls, rolcreatedb, rolcreaterole from pg_roles where rolname = current_user');

    expect($role)->not->toBeNull()
        ->and(array_values((array) $role))->toBe([false, false, false, false]);
});

it('connects with a role that owns no table', function () {
    $owned = DB::scalar("select count(*) from pg_tables where schemaname = 'public' and tableowner = current_user");

    expect($owned)->toBe(0);
});

it('reads and writes the tables the owner created', function () {
    DB::table('cache')->insert(['key' => 'role-check', 'value' => 'ok', 'expiration' => 0]);

    expect(DB::table('cache')->where('key', 'role-check')->value('value'))->toBe('ok');
});

it('cannot change the schema', function () {
    DB::statement('create table forbidden (id integer)');
})->throws(QueryException::class);
