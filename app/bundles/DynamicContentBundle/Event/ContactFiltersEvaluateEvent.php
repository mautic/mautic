<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

final class ContactFiltersEvaluateEvent extends Event
{
    private bool $isEvaluated = false;

    private bool $isMatched   = false;

    /**
     * @param mixed[] $filters
     */
    public function __construct(
        private array $filters,
        private Lead $contact,
    ) {
    }

    public function isMatch(): bool
    {
        return $this->isEvaluated() && $this->isMatched;
    }

    public function isEvaluated(): bool
    {
        return $this->isEvaluated;
    }

    public function setIsEvaluated(bool $evaluated): ContactFiltersEvaluateEvent
    {
        $this->isEvaluated = $evaluated;

        return $this;
    }

    public function getContact(): Lead
    {
        return $this->contact;
    }

    public function setContact(Lead $contact): ContactFiltersEvaluateEvent
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function isMatched(): bool
    {
        return $this->isMatched;
    }

    public function setIsMatched(bool $isMatched): ContactFiltersEvaluateEvent
    {
        $this->isMatched = $isMatched;

        return $this;
    }
}
