<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * This file was originally distributed as part of VelvelReportBundle (C) 2012 Velvel IT Solutions
 * and distributed under the GNU Lesser General Public License version 3.
 */

namespace Mautic\ReportBundle\Builder;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Mautic Report Builder class
 */
final class MauticReportBuilder implements ReportBuilderInterface
{
    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    private $securityContext;

    /**
     * @var \Mautic\ReportBundle\Entity\Report
     */
    private $entity;

    /**
     * @var string
     */
    private $contentTemplate;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Security\Core\SecurityContextInterface $securityContext Symfony Core Security Context
     * @param \Mautic\ReportBundle\Entity\Report                        $entity          Report entity
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function __construct(SecurityContextInterface $securityContext, Report $entity)
    {
        $this->securityContext = $securityContext;
        $this->entity          = $entity;
    }

    /**
     * Gets the QueryBuilder instance with the report query prepared
     *
     * @param array $options Options array
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws InvalidReportQueryException
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function getQuery(array $options)
    {
        $queryBuilder = $this->configureBuilder($options);

        if ($queryBuilder->getType() !== QueryBuilder::SELECT) {
            throw new InvalidReportQueryException('Only SELECT statements are valid');
        }

        return $queryBuilder;
    }

    /**
     * Gets the getContentTemplate path
     *
     * @return string
     */
    public function getContentTemplate()
    {
        return $this->contentTemplate;
    }

    /**
     * Configures builder
     *
     * This method configures the ReportBuilder. It has to return
     * a configured Doctrine DBAL QueryBuilder.
     *
     * @param array $options Options array
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function configureBuilder(array $options)
    {
        $source   = $this->entity->getSource();
        $fields   = $this->entity->getColumns();

        $selectColumns = array();

        foreach ($fields as $field) {
            $selectColumns[] = $field;
        }

        // Trigger the REPORT_ON_GENERATE event to initialize the QueryBuilder
        /** @type \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher $dispatcher */
        $dispatcher = $options['dispatcher'];

        $event = new ReportGeneratorEvent($source);
        $dispatcher->dispatch(ReportEvents::REPORT_ON_GENERATE, $event);
        $queryBuilder = $event->getQueryBuilder();

        // Set Content Template
        $this->contentTemplate = $event->getContentTemplate();

        if ($selectColumns) {
            $queryBuilder->select(implode(', ', $selectColumns));
        } else {
            $queryBuilder->select('*');
        }
        
        // Add filters as AND values to the WHERE clause if present
        $filters = $this->entity->getFilters();

        if (count($filters)) {
            $expr = $queryBuilder->expr();
            $and  = $expr->andX();

            foreach ($filters as $filter) {
                if ($filter['condition'] == 'notEmpty') {
                    $and->add(
                        $expr->isNotNull($filter['column'])
                    );
                    $and->add(
                        $expr->neq($filter['column'], $expr->literal(''))
                    );
                } elseif ($filter['condition'] == 'empty') {
                    $and->add(
                        $expr->isNull($filter['column'])
                    );
                    $and->add(
                        $expr->eq($filter['column'], $expr->literal(''))
                    );
                } else {
                    $and->add(
                        $expr->{$filter['condition']}($filter['column'], $expr->literal($filter['value']))
                    );
                }
            }

            $queryBuilder->where($and);
        }

        // TODO - We might not always want to apply these options, expand the array to make the options optional
        if ($options['orderBy'] != '' && $options['orderByDir'] != '') {
            $queryBuilder->orderBy($options['orderBy'], $options['orderByDir']);
        } elseif (isset($selectColumns[0])) {
            $queryBuilder->orderBy($selectColumns[0], 'ASC');
        }

        if ($options['limit'] > 0) {
            $queryBuilder->setFirstResult($options['start'])
                ->setMaxResults($options['limit']);
        }

        return $queryBuilder;
    }
}
