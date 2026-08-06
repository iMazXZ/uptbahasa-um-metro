<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        $this->assertSafeTestingDatabase($app);

        return $app;
    }

    protected function assertSafeTestingDatabase($app): void
    {
        $environment = (string) $app->environment();
        $defaultConnection = (string) $app['config']->get('database.default');
        $databaseName = (string) $app['config']->get("database.connections.{$defaultConnection}.database", '');

        if ($environment !== 'testing') {
            throw new RuntimeException(sprintf(
                'Refusing to run tests outside APP_ENV=testing. Current APP_ENV=%s.',
                $environment !== '' ? $environment : '(empty)',
            ));
        }

        $normalizedDatabase = strtolower(trim($databaseName));
        $safeDatabase = $normalizedDatabase === ':memory:'
            || $normalizedDatabase === 'testing'
            || str_ends_with($normalizedDatabase, '_test')
            || str_ends_with($normalizedDatabase, '_testing');

        if (! $safeDatabase) {
            throw new RuntimeException(sprintf(
                'Unsafe test database detected for connection [%s]: [%s]. Tests may only run against :memory:, testing, *_test, or *_testing databases.',
                $defaultConnection !== '' ? $defaultConnection : '(unknown)',
                $databaseName !== '' ? $databaseName : '(empty)',
            ));
        }
    }
}
