<?php

namespace Mautic\SmsBundle\Broadcast;

class BroadcastResult
{
    /**
     * @var int
     */
    private $sentCount = 0;

    /**
     * @var int
     */
    private $failedCount = 0;

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

    /**
     * @return int
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }

    /**
     * @return int
     */
    public function getFailedCount()
    {
        return $this->failedCount;
    }
}
