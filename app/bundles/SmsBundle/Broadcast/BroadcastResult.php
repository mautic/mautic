<?php

namespace Mautic\SmsBundle\Broadcast;

class BroadcastResult
{
    private int $sentCount = 0;

    private int $failedCount = 0;

    public function process(array $results): void
    {
        foreach ($results as $result) {
            if (isset($result['sent']) && true === $result['sent']) {
                $this->sent();
            } else {
                $this->failed();
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
}
