<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

/** Загружает тестовое приложение, исключая работу с пользовательской базой. */
abstract class TestCase extends BaseTestCase
{
    /** Проверяет изоляцию базы до выполнения миграций RefreshDatabase. */
    public function createApplication(): Application
    {
        $application = parent::createApplication();
        $driver = $application['config']->get('database.default');
        $database = $application['config']->get("database.connections.{$driver}.database");
        if (($driver === 'sqlite' && $database !== ':memory:') || ($driver !== 'sqlite' && (! is_string($database) || ! str_ends_with($database, '_test')))) {
            throw new RuntimeException('Тесты требуют SQLite :memory: или отдельную базу с суффиксом _test.');
        }
        if ($application['config']->get('cache.default') === 'redis') {
            // Отдельный префикс не позволяет лимитам соседних тестов влиять друг на друга.
            $application['config']->set('cache.prefix', 'capital-test:'.bin2hex(random_bytes(12)).':');
            $application['cache']->purge('redis');
        }

        return $application;
    }
}
