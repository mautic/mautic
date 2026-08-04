<?php

namespace Mautic\EmailBundle\MonitoredEmail\Organizer;

use Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor;

final class MailboxContainer
{
    private array $criteria = [];

    private bool $markAsSeen = true;

    public function __construct(
        private readonly ConfigAccessor $config,
    ) {
    }

    public function addCriteria($criteria, $mailbox): void
    {
        if (!isset($this->criteria[$criteria])) {
            $this->criteria[$criteria] = [];
        }

        $this->criteria[$criteria][] = $mailbox;
    }

    /**
     * Keep the messages in this mailbox as unseen.
     */
    public function keepAsUnseen(): void
    {
        $this->markAsSeen = false;
    }

    public function shouldMarkAsSeen(): bool
    {
        return $this->markAsSeen;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->config->getPath();
    }

    public function getCriteria(): array
    {
        return $this->criteria;
    }
}
