<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Exception;

use Doctrine\DBAL\Query\QueryException;

class SegmentQueryException extends QueryException
{
}
