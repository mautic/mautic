<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class PreFetchEmailEvent extends Event
{
    /**
     * @var mixed[]
     */
    private array $criteriaRequests = [];

    /**
     * @var mixed[]
     */
    private array $markAsSeen = [];

    /**
     * Set a criteria request for filtering fetched mail.
     *
     * @param string|string[] $folderKeys
     * @param string $criteria   Should be a string using combinations of Mautic\EmailBundle\MonitoredEmail\Mailbox::CRITERIA_* constants
     * @param bool   $markAsSeen Mark the message as read after being processed
     */
    public function setCriteriaRequest(string $bundleKey, string|array $folderKeys, $criteria, bool $markAsSeen = true): void
    {
        if (!is_array($folderKeys)) {
            $folderKeys = [$folderKeys];
        }

        foreach ($folderKeys as $folderKey) {
            $key = $bundleKey.'_'.$folderKey;

            $this->criteriaRequests[$key] = $criteria;
            $this->markAsSeen[$key]       = $markAsSeen;
        }
    }

    public function getCriteriaRequests(): array
    {
        return $this->criteriaRequests;
    }

    public function getMarkAsSeenInstructions(): array
    {
        return $this->markAsSeen;
    }
}
