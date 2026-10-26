<?php

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
| Feature tests boot the Laravel application. Every test runs offline:
| any HTTP call that is not faked with Http::fake() throws an error.
*/
pest()->extend(TestCase::class)
    ->beforeEach(fn () => Http::preventStrayRequests())
    ->in('Feature');
