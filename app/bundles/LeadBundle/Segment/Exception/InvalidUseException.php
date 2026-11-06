<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Exception;

/**
 * This exception is risen if functionality requested does not belong to give FilterQueryBuilder.
 */
final class InvalidUseException extends SegmentQueryException
{
}
