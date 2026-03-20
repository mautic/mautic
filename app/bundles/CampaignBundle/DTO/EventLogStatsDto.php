<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\DTO;

final readonly class EventLogStatsDto
{
    public function __construct(
        public int $totalExecutions = 0,
        public int $uniqueExecutions = 0,
        public int $pendingExecutions = 0,
        public int $maxRotations = 0,
        public int $negativePathCount = 0,
        public int $positivePathCount = 0,
        public ?\DateTimeImmutable $firstExecutionDate = null,
        public ?\DateTimeImmutable $lastExecutionDate = null,
    ) {
    }
}
