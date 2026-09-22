<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Builder;

use Mautic\CoreBundle\Doctrine\Query\QueryBuilder;

interface ReportBuilderInterface
{
    /**
     * Gets the query instance with default parameters.
     *
     * @param array $options Options array
     */
    public function getQuery(array $options): QueryBuilder;
}
