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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
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
     * Gets the query instance with default parameters
     *
     * @param array $options Options array
     *
     * @return \Doctrine\ORM\Query
     * @throws InvalidReportQueryException
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function getQuery(array $options)
    {
        $queryBuilder = $this->configureBuilder($this->entityManager->createQueryBuilder(), $options);

        if ($queryBuilder->getType() === \Doctrine\DBAL\Query\QueryBuilder::SELECT) {
            $query = $queryBuilder->getQuery();
        }
        else {
            throw new InvalidReportQueryException('Only SELECT statements are valid');
        }
        return $query;
    }

    /**
     * Configures builder
     *
     * This method configures the ReportBuilder. It has to return
     * a configured Doctrine QueryBuilder.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder Doctrine ORM query builder
     * @param array                      $options      Options array
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function configureBuilder(QueryBuilder $queryBuilder, array $options)
    {
        // TODO - Rethink how the source is stored
        $source   = $this->entity->getSource();
        $metadata = $this->entityManager->getClassMetadata('Mautic\\' . $source . 'Bundle\\Entity\\' . $source);
        $columns  = $metadata->getFieldNames();
        $fields   = $this->entity->getColumns();
        $key      = $metadata->getSingleIdentifierFieldName();

        // Getting creative to build the entity name
        // Explode the FQCN of the entity into an array, should be 4 elements in the form of Mautic\PageBundle\Entity\Page
        $fullEntityName = explode('\\', $metadata->name);

        // Build the entity reference for the FROM clause by creating <0><1>:<3> (or MauticPageBundle:Page)
        $entityName = $fullEntityName[0] . $fullEntityName[1] . ':' . $fullEntityName[3];

        $selectColumns = array();

        foreach ($fields as $field) {
            $selectColumns[] = 'r.' . $columns[$field];
        }

        $queryBuilder
            ->select(implode(', ', $selectColumns))
            ->from($entityName, 'r', 'r.' . $key);

        // Add filters as AND values to the WHERE clause if present
        $filters = $this->entity->getFilters();

        // Also need the Connection object to quote the user input
        $connection = $this->entityManager->getConnection();

        if (count($filters)) {
            $expr = $queryBuilder->expr();
            $and  = $expr->andX();

            foreach ($filters as $filter) {
                $and->add(
                    $expr->{$filter['condition']}('r.' . $columns[$filter['column']], $connection->quote($filter['value']))
                );
            }

            $queryBuilder->where($and);
        }

        // TODO - We might not always want to apply these options, expand the array to make the options optional
        if ($options['orderBy'] != '' && $options['orderByDir'] != '') {
            $queryBuilder->orderBy($options['orderBy'], $options['orderByDir']);
        } else {
            $queryBuilder->orderBy('r.' . $key, 'ASC');
        }

        if ($options['limit'] > 0) {
            $queryBuilder->setFirstResult($options['start'])
                ->setMaxResults($options['limit']);
        }

        return $queryBuilder;
    }
}
