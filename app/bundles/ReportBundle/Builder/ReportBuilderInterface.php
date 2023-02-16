<?php

namespace Mautic\ReportBundle\Builder;

/**
 * Interface ReportBuilderInterface.
 */
interface ReportBuilderInterface
{
    /**
     * Gets the query instance with default parameters.
     *
     * @param array $options Options array
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQuery(array $options);
}
