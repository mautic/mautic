<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message;

use DateTime;
use Mautic\MessengerBundle\Message\Traits\MessageRequestTrait;
use Symfony\Component\HttpFoundation\Request;

class PageHitNotification
{
    use MessageRequestTrait;

    private array $request;

    /**
     * @param array|Request $request
     */
    public function __construct(
        private int $hitId,
        private ?int $pageId,
        $request,
        private ?int $leadId,
        private bool $isNew,
        private bool $isRedirect,
        ?DateTime $eventTime = null
    ) {
        $this->setRequest($request);
        $this->setEventTime($eventTime);
    }

    public function getHitId(): int
    {
        return $this->hitId;
    }

    public function getPageId(): ?int
    {
        return $this->pageId;
    }

    public function getLeadId(): ?int
    {
        return $this->leadId;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function isRedirect(): bool
    {
        return $this->isRedirect;
    }
}
