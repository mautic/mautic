<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;

final class LeadListFiltersDecoratorDelegateEvent extends CommonEvent
{
    private ?FilterDecoratorInterface $decorator = null;

    public function __construct(
        private ContactSegmentFilterCrate $crate,
    ) {
    }

    public function getDecorator(): ?FilterDecoratorInterface
    {
        return $this->decorator;
    }

    public function setDecorator(FilterDecoratorInterface $decorator): self
    {
        $this->decorator = $decorator;

        return $this;
    }

    public function getCrate(): ContactSegmentFilterCrate
    {
        return $this->crate;
    }
}
