<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ReportGeneratorEvent
 *
 * @package Mautic\ReportBundle\Event
 */
class ReportGeneratorEvent extends Event
{
    /**
     * Event context
     *
     * @var string
     */
    private $context;

    /**
     * QueryBuilder object
     *
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * Constructor
     *
     * @param string $context Event context
     */
    public function __construct($context)
    {
        $this->context = $context;
    }

    /**
     * Retrieve the event context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Fetch the QueryBuilder object
     *
     * @return QueryBuilder
     *
     * @throws \RuntimeException
     */
    public function getQueryBuilder()
    {
        if ($this->queryBuilder instanceof QueryBuilder) {
            return $this->queryBuilder;
        }

        throw new \RuntimeException('QueryBuilder not set.');
    }

    /**
     * Set the QueryBuilder object
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return void
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }
}
