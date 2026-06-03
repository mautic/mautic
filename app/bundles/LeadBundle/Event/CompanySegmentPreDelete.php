<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\DependencyErrorEventInterface;
use Mautic\CoreBundle\Event\DependencyErrorEventTrait;

class CompanySegmentPreDelete extends CompanySegmentEvent implements DependencyErrorEventInterface
{
    use DependencyErrorEventTrait;
}
