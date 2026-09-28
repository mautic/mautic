<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Broadcast;

final class BroadcastResult
{
    private int $sentCount = 0;

    private int $failedCount = 0;

    /**
     * @var array<int, string>
     */
    private array $failedContacts = [];

    public function process(array $results): void
    {
        foreach ($results as $lead_id => $result) {
            if (isset($result['sent']) && true === $result['sent']) {
                $this->sent();
            } else {
                $this->failed();
                $this->failedContacts[$lead_id] = $result['status'];
            }
        }
    }

    public function sent(): void
    {
        ++$this->sentCount;
    }

    public function failed(): void
    {
        ++$this->failedCount;
    }

    public function getSentCount(): int
    {
        return $this->sentCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    /**
     * @return array<int, string>
     */
    public function getFailedContacts(): array
    {
        return $this->failedContacts;
    }
}
