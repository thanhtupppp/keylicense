<?php

namespace App\Actions\Licensing;

use App\Services\Licensing\OfflineChallengeService;
use App\Services\Sdk\Dto\ChallengeResult;

class RequestOfflineChallengeAction
{
    public function __construct(
        private readonly OfflineChallengeService $offlineChallengeService,
    ) {
    }

    public function execute(array $data): ChallengeResult
    {
        return $this->offlineChallengeService->request($data);
    }
}
