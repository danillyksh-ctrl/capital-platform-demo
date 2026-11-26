<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Подтверждает использование реальных сервисов вместо тестовых подмен. */
class InfrastructureTest extends TestCase
{
    /** Проверяет PostgreSQL, Redis-кеш и сохранение сессий через Redis. */
    public function test_postgresql_and_redis_round_trips(): void
    {
        if (! filter_var(getenv('RUN_INFRA_TESTS'), FILTER_VALIDATE_BOOLEAN)) {
            $this->markTestSkipped('Нужны RUN_INFRA_TESTS=true, PostgreSQL и Redis.');
        }
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame(1, (int) DB::selectOne('select 1 as value')->value);
        $this->assertSame('redis', config('cache.default'));
        $this->assertNotFalse(Redis::connection()->ping());
        $key = 'infrastructure-test:'.Str::uuid();
        try {
            Cache::put($key, 'round-trip', 30);
            $this->assertSame('round-trip', Cache::get($key));
            $session = app('session')->driver('redis');
            $session->setId(Str::random(40));
            $session->start();
            $session->put('infrastructure_probe', 'stored');
            $session->save();
            $session->flush();
            $session->start();
            $this->assertSame('stored', $session->get('infrastructure_probe'));
            $session->getHandler()->destroy($session->getId());
        } finally {
            Cache::forget($key);
        }
    }
}
