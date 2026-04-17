<?php

namespace App\Actions\Licensing\Activation;

use App\Services\Licensing\OfflineChallengeService;
use App\Services\Sdk\Dto\ChallengeResult;

class ConfirmOfflineChallengeAction
{
    public function __construct(
        private readonly OfflineChallengeService $offlineChallengeService,
    ) {
    }

    public function execute(array $data): ChallengeResult
    {
        return $this->offlineChallengeService->confirm($data);
    }
}
