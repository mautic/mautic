<?php

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\FailedLeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class PendingEvent extends AbstractLogCollectionEvent
{
    use ContextTrait;

    private ArrayCollection $failures;

    private ArrayCollection $successful;

    /**
     * @var string|null
     */
    private $channel;

    /**
     * @var int|null
     */
    private $channelId;

    private \DateTimeInterface $now;

    /**
     * @throws \Exception
     */
    public function __construct(AbstractEventAccessor $config, Event $event, ArrayCollection $logs)
    {
        $this->failures   = new ArrayCollection();
        $this->successful = new ArrayCollection();
        $this->now        = new \DateTime();

        parent::__construct($config, $event, $logs);
    }

    /**
     * @return LeadEventLog[]|ArrayCollection
     */
    public function getPending()
    {
        return $this->logs;
    }

    /**
     * @param string $reason
     */
    public function fail(LeadEventLog $log, $reason, \DateInterval $rescheduleInterval = null): void
    {
        if (!$failedLog = $log->getFailedLog()) {
            $failedLog = new FailedLeadEventLog();
        }

        $log->setRescheduleInterval($rescheduleInterval);

        $failedLog->setLog($log)
            ->setDateAdded(new \DateTime())
            ->setReason($reason);

        // Used by the UI
        $metadata = $log->getMetadata();
        $metadata = array_merge(
            $metadata,
            [
                'failed' => 1,
                'reason' => $reason,
            ]
        );
        $log->setMetadata($metadata);

        $this->logChannel($log);

        $this->failures->set($log->getId(), $log);
    }

    /**
     * @param string $reason
     */
    public function failAll($reason): void
    {
        foreach ($this->logs as $log) {
            $this->fail($log, $reason);
        }
    }

    /**
     * Fail all that have not passed yet.
     *
     * @param string $reason
     */
    public function failRemaining($reason): void
    {
        foreach ($this->logs as $log) {
            if (!$this->successful->contains($log)) {
                $this->fail($log, $reason);
            }
        }
    }

    /**
     * Fail all that have not passed or failed yet.
     *
     * @param string $reason
     */
    public function failRemainingPending($reason): void
    {
        foreach ($this->logs as $log) {
            if (!$this->failures->contains($log) && !$this->successful->contains($log)) {
                $this->fail($log, $reason);
            }
        }
    }

    /**
     * @param LeadEventLog[]|ArrayCollection $logs
     * @param string                         $reason
     */
    public function failLogs(ArrayCollection $logs, $reason): void
    {
        foreach ($logs as $log) {
            $this->fail($log, $reason);
        }
    }

    public function pass(LeadEventLog $log): void
    {
        $metadata = $log->getMetadata();
        unset($metadata['errors']);
        if (isset($metadata['failed'])) {
            unset($metadata['failed'], $metadata['reason']);
        }
        $log->setMetadata($metadata);

        $this->passLog($log);
    }

    /**
     * @param string $error
     */
    public function passWithError(LeadEventLog $log, $error): void
    {
        $log->appendToMetadata(
            [
                'failed' => 1,
                'reason' => $error,
            ]
        );

        $this->passLog($log);
    }

    /**
     * @param string $error
     */
    public function passAllWithError($error): void
    {
        /** @var LeadEventLog $log */
        foreach ($this->logs as $log) {
            $this->passWithError($log, $error);
        }
    }

    /**
     * Pass all remainging logs that have not failed failed nor suceeded yet.
     */
    public function passRemainingWithError(string $error): void
    {
        foreach ($this->logs as $log) {
            if (!$this->failures->contains($log) && !$this->successful->contains($log)) {
                $this->passWithError($log, $error);
            }
        }
    }

    /**
     * Pass all pending.
     */
    public function passAll(): void
    {
        /** @var LeadEventLog $log */
        foreach ($this->logs as $log) {
            $this->pass($log);
        }
    }

    /**
     * @param LeadEventLog[]|ArrayCollection $logs
     */
    public function passLogs(ArrayCollection $logs): void
    {
        foreach ($logs as $log) {
            $this->pass($log);
        }
    }

    /**
     * Pass all that have not failed yet.
     */
    public function passRemaining(): void
    {
        foreach ($this->logs as $log) {
            if (!$this->failures->contains($log)) {
                $this->pass($log);
            }
        }
    }

    /**
     * @return LeadEventLog[]|ArrayCollection
     */
    public function getFailures()
    {
        return $this->failures;
    }

    /**
     * @return LeadEventLog[]|ArrayCollection
     */
    public function getSuccessful()
    {
        return $this->successful;
    }

    /**
     * @param string   $channel
     * @param int|null $channelId
     */
    public function setChannel($channel, $channelId = null): void
    {
        $this->channel   = $channel;
        $this->channelId = $channelId;
    }

    private function passLog(LeadEventLog $log): void
    {
        if ($failedLog = $log->getFailedLog()) {
            // Delete existing entries
            $failedLog->setLog(null);
            $log->setFailedLog(null);
        }
        $this->logChannel($log);
        $log->setIsScheduled(false)
            ->setDateTriggered($this->now);

        $this->successful->set($log->getId(), $log);
    }

    private function logChannel(LeadEventLog $log): void
    {
        if ($this->channel) {
            $log->setChannel($this->channel);
            $log->setChannelId($this->channelId);
        }
    }
}
