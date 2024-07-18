<?php

use Illuminate\Support\Facades\Artisan;
use Vocalio\LaravelStarterKit\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function runUpdates(): void
{
    Artisan::call('db:update', [
        '--realpath' => __DIR__.'/Fixtures',
    ]);
}
