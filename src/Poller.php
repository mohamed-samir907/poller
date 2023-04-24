<?php

namespace Mosamirzz\Poller;

use Closure;
use Exception;
use Throwable;

final class Poller
{
    /**
     * The number of times the poll occurs.
     */
    private int $count = 0;

    /**
     * Create new instance.
     */
    public function __construct(
        /**
         * The process that will be run.
         */
        private Closure $do,
        /**
         * Poll every number of seconds.
         */
        private int $every = 5,
        /**
         * The number of times the poll stops working and fails when it is reached.
         */
        private int $limit = 5,
        /**
         * The action that will stop the polling.
         */
        private ?Closure $stopWhen = null,
        /**
         * The action that will happen when the process fails.
         */
        private ?Closure $onFail = null,
    ) {
    }

    /**
     * The process that will be run
     */
    public static function do(callable $callback): self
    {
        return new static(Closure::fromCallable($callback));
    }

    /**
     * Poll every number of seconds.
     */
    public function every(int $seconds): self
    {
        $this->every = $seconds;
        return $this;
    }

    /**
     * The action that will stop the polling.
     */
    public function stopWhen(callable $callback): self
    {
        $this->stopWhen = Closure::fromCallable($callback);
        return $this;
    }

    /**
     * The number of times the poll stops working and fails when it is reached.
     */
    public function failAfter(int $attempts): self
    {
        $this->limit = $attempts;
        return $this;
    }

    /**
     * The action that will happen when the process fails.
     */
    public function onFail(callable $callback)
    {
        $this->onFail = Closure::fromCallable($callback);
        return $this;
    }

    /**
     * Start the polling and return the result.
     */
    public function start(): mixed
    {
        try {
            $result = ($this->do)();

            $stopped = ($this->stopWhen)($result);

            if ($stopped) {
                return $result;
            }

            if ($this->count >= $this->limit) {
                $e = new Exception("Poll limit exceeded");
                return ($this->onFail)($e);
            }

            $this->count++;

            sleep($this->every);

            return $this->start();
        } catch (Throwable $e) {
            return ($this->onFail)($e);
        }
    }
}
