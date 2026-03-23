<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

/**
 * Event dispatched when companies are removed from a segment during rebuild.
 */
class CompanySegmentRebuildRemoveEvent extends CompanySegmentRebuildChangeEvent
{
}
