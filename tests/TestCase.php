<?php

namespace Vocalio\LaravelStarterKit\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Vocalio\LaravelStarterKit\StarterKitServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            StarterKitServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $migration = include __DIR__.'/../database/migrations/create_database_updates_table.php.stub';
        $migration->up();
    }
}
