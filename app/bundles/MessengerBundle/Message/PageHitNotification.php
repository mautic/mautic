<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message;

use Mautic\MessengerBundle\Message\Traits\MessageRequestTrait;
use Symfony\Component\HttpFoundation\Request;

final class PageHitNotification
{
    use MessageRequestTrait;

    /**
     * @var array<string,mixed>
     */
    private array $request;

    /**
     * @param array<string,mixed>|Request $request
     */
    public function __construct(
        private int $hitId,
        private ?int $pageId,
        array|Request $request,
        private ?int $leadId,
        private bool $isNew,
        private bool $isRedirect,
        \DateTimeInterface $eventTime = null
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
