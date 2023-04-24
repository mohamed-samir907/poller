<?php

namespace Mosamirzz\Poller\Tests;

use Throwable;
use Mosamirzz\Poller\Poller;
use PHPUnit\Framework\TestCase;

final class PollerTest extends TestCase
{
    public function testItWillReturnAResult()
    {
        $result = Poller::do(fn () => ["name" => "mohamed"])
            ->every(seconds: 5)
            ->failAfter(attempts: 2)
            ->stopWhen(fn (mixed $result): bool => $result["name"] == "mohamed")
            ->onFail(function (Throwable $e) {
                return ["name" => ""];
            })
            ->start();

        $this->assertSame($result["name"], "mohamed");
    }

    public function testItFailsAfterTheGivenAttemptsIfWeDoNotGetAResult()
    {
        $every = 2;
        $attempts = 2;

        $this->expectExceptionMessage("Poll limit exceeded");

        $before = time();

        $result = Poller::do(fn () => ["name" => "mohamed"])
            ->every(seconds: $every)
            ->failAfter(attempts: $attempts)
            ->stopWhen(fn (mixed $result): bool => $result["name"] == "ahmed")
            ->onFail(function (Throwable $e) {
                throw $e;
            })->start();

        $after = time();

        $this->assertSame($after - $before, $every * $attempts);
    }

    public function testItCanReturnACustomResultOnFail()
    {
        $result = Poller::do(fn () => ["name" => "mohamed"])
            ->every(seconds: 2)
            ->failAfter(attempts: 1)
            ->stopWhen(fn (mixed $result): bool => $result["name"] == "ahmed")
            ->onFail(fn (Throwable $e) => ["name" => "ali"])
            ->start();

        $this->assertSame($result["name"], "ali");
    }
}
