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
    private int $hitId;
    private ?int $pageId;
    private ?int $leadId;
    private bool $isNew;
    private bool $isRedirect;

    /**
     * @param array|Request $request
     */
    public function __construct(
        int $hitId,
        ?int $pageId,
        $request,
        ?int $leadId,
        bool $isNew,
        bool $isRedirect,
        ?DateTime $eventTime = null
    ) {
        $this->setRequest($request);
        $this->setEventTime($eventTime);

        $this->hitId      = $hitId;
        $this->pageId     = $pageId;
        $this->leadId     = $leadId;
        $this->isNew      = $isNew;
        $this->isRedirect = $isRedirect;
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
