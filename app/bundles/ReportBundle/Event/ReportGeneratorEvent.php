<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ReportGeneratorEvent
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
     * contentTemplate
     *
     * @var string
     */
    private $contentTemplate;

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

    /**
     * Fetch the ContentTemplate path
     *
     * @return QueryBuilder
     * @throws \RuntimeException
     */
    public function getContentTemplate()
    {
        if ($this->contentTemplate) {
            return $this->contentTemplate;
        }

        // Default content template
        return 'MauticReportBundle:Report:details_data.html.php';
    }

    /**
     * Set the ContentTemplate path
     *
     * @param string $contentTemplate
     *
     * @return void
     */
    public function setContentTemplate($contentTemplate)
    {
        $this->contentTemplate = $contentTemplate;
    }

    /**
     * Add category left join
     *
     * @param QueryBuilder $queryBuilder
     * @param              $prefix
     */
    public function addCategoryLeftJoin(QueryBuilder &$queryBuilder, $prefix, $categoryPrefix = 'c')
    {
        $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX . 'categories', $categoryPrefix, 'c.id = ' . $prefix . '.category_id');
    }

    /**
     * Add lead left join
     *
     * @param QueryBuilder $queryBuilder
     * @param              $prefix
     * @param string       $leadPrefix
     */
    public function addLeadLeftJoin(QueryBuilder &$queryBuilder, $prefix, $leadPrefix = 'l')
    {
        $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX . 'leads', $leadPrefix, 'l.id = ' . $prefix . '.lead_id');
    }

    /**
     * Add IP left join
     *
     * @param QueryBuilder $queryBuilder
     * @param              $prefix
     * @param string       $ipPrefix
     */
    public function addIpAddressLeftJoin(QueryBuilder &$queryBuilder, $prefix, $ipPrefix = 'i')
    {
        $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX . 'ip_addresses', $ipPrefix, 'i.id = ' . $prefix . '.ip_id');
    }
}
