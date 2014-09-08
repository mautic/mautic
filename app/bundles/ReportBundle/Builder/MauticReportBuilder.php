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
use Doctrine\ORM\EntityManager;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Mautic Report Builder class
 */
final class MauticReportBuilder implements ReportBuilderInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    private $securityContext;

    /**
     * @var \Mautic\ReportBundle\Entity\Report
     */
    private $entity;

    /**
     * Constructor
     *
     * @param \Doctrine\ORM\EntityManager                               $entityManager   Doctrine ORM Entity Manager
     * @param \Symfony\Component\Security\Core\SecurityContextInterface $securityContext Symfony Core Security Context
     * @param \Mautic\ReportBundle\Entity\Report                        $entity          Report entity
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function __construct(EntityManager $entityManager, SecurityContextInterface $securityContext, Report $entity)
    {
        $this->entityManager   = $entityManager;
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
        $queryBuilder = $this->configureBuilder($this->entityManager->getConnection()->createQueryBuilder(), $options);

        if ($queryBuilder->getType() !== QueryBuilder::SELECT) {
            throw new InvalidReportQueryException('Only SELECT statements are valid');
        }

        return $queryBuilder;
    }

    /**
     * Configures builder
     *
     * This method configures the ReportBuilder. It has to return
     * a configured Doctrine DBAL QueryBuilder.
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $queryBuilder Doctrine ORM query builder
     * @param array                             $options      Options array
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function configureBuilder(QueryBuilder $queryBuilder, array $options)
    {
        $source   = $this->entity->getSource();
        $columns  = $options['table_list'][$source]['columns'];
        $fields   = $this->entity->getColumns();

        $selectColumns = array();

        foreach ($fields as $field) {
            $selectColumns[] = 'r.' . $columns[$field];
        }

        $queryBuilder
            ->select(implode(', ', $selectColumns))
            ->from(MAUTIC_TABLE_PREFIX . $source, 'r');

        // Add filters as AND values to the WHERE clause if present
        $filters = $this->entity->getFilters();

        if (count($filters)) {
            $expr = $queryBuilder->expr();
            $and  = $expr->andX();

            foreach ($filters as $filter) {
                if ($filter['condition'] == 'notEmpty') {
                    $and->add(
                        $expr->isNotNull('r.' . $columns[$filter['column']])
                    );
                    $and->add(
                        $expr->neq('r.' . $columns[$filter['column']], $expr->literal(''))
                    );
                } elseif ($filter['condition'] == 'empty') {
                    $and->add(
                        $expr->isNull('r.' . $columns[$filter['column']])
                    );
                    $and->add(
                        $expr->eq('r.' . $columns[$filter['column']], $expr->literal(''))
                    );
                } else {
                    $and->add(
                        $expr->{$filter['condition']}('r.' . $columns[$filter['column']], $expr->literal($filter['value']))
                    );
                }
            }

            $queryBuilder->where($and);
        }

        // TODO - We might not always want to apply these options, expand the array to make the options optional
        if ($options['orderBy'] != '' && $options['orderByDir'] != '') {
            $queryBuilder->orderBy($options['orderBy'], $options['orderByDir']);
        } else {
            $queryBuilder->orderBy($selectColumns[0], 'ASC');
        }

        if ($options['limit'] > 0) {
            $queryBuilder->setFirstResult($options['start'])
                ->setMaxResults($options['limit']);
        }

        return $queryBuilder;
    }
}
