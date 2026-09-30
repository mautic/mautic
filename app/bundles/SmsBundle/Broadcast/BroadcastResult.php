<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Broadcast;

final class BroadcastResult
{
    private const SCHEDULED_STATUS = 'mautic.sms.timeline.status.scheduled';

    private const UNKNOWN_FAILURE_STATUS = 'mautic.sms.timeline.event.failed';

    private int $processedCount = 0;

    private int $submittedCount = 0;

    private int $scheduledCount = 0;

    private int $failedCount = 0;

    private int $executionFailureCount = 0;

    private int $remainingCount = 0;

    /**
     * @var array<int, string>
     */
    private array $failedContacts = [];

    public function process(array $results, ?int $processedCount = null): void
    {
        $processedCount ??= count($results);
        $this->processedCount += max($processedCount, count($results));
        $this->failedCount += max(0, $processedCount - count($results));

        foreach ($results as $leadId => $result) {
            if (isset($result['sent']) && true === $result['sent']) {
                $this->sent();
            } elseif (self::SCHEDULED_STATUS === ($result['status'] ?? null)) {
                $this->scheduled();
            } else {
                $this->failed();
                $this->failedContacts[(int) $leadId] = is_string($result['status'] ?? null)
                    ? $result['status']
                    : self::UNKNOWN_FAILURE_STATUS;
            }
        }
    }

    public function sent(): void
    {
        ++$this->submittedCount;
    }

    public function scheduled(): void
    {
        ++$this->scheduledCount;
    }

    public function failed(): void
    {
        ++$this->failedCount;
    }

    public function executionFailed(): void
    {
        ++$this->executionFailureCount;
    }

    public function merge(self $result): void
    {
        $this->processedCount += $result->processedCount;
        $this->submittedCount += $result->submittedCount;
        $this->scheduledCount += $result->scheduledCount;
        $this->failedCount += $result->failedCount;
        $this->executionFailureCount += $result->executionFailureCount;
        $this->remainingCount         = $result->remainingCount;
        $this->failedContacts         = array_replace($this->failedContacts, $result->failedContacts);
    }

    public function setRemainingCount(int $remainingCount): void
    {
        $this->remainingCount = max(0, $remainingCount);
    }

    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }

    public function getSubmittedCount(): int
    {
        return $this->submittedCount;
    }

    public function getScheduledCount(): int
    {
        return $this->scheduledCount;
    }

    public function getSuccessfulCount(): int
    {
        return $this->submittedCount + $this->scheduledCount;
    }

    public function getSentCount(): int
    {
        return $this->submittedCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount + $this->executionFailureCount;
    }

    public function getExecutionFailureCount(): int
    {
        return $this->executionFailureCount;
    }

    public function getRemainingCount(): int
    {
        return $this->remainingCount;
    }

    /**
     * @return array<int, string>
     */
    public function getFailedContacts(): array
    {
        return $this->failedContacts;
    }
}
