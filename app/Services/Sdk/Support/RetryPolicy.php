<?php

namespace App\Services\Sdk\Support;

class RetryPolicy
{
    /**
     * @param array<int, int> $delays
     */
    public function __construct(
        public readonly int $maxAttempts = 2,
        public readonly array $delays = [250, 500],
    ) {
    }

    /**
     * @return array<int, int>|\Closure
     */
    public function laravelRetry(): array|\Closure
    {
        return $this->delays;
    }
}
