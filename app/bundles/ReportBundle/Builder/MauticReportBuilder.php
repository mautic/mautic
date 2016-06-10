<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
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
     * @var array
     */
    const OPERATORS = [
        'default'     => [
            'eq'       => 'mautic.lead.list.form.operator.equals',
            'gt'       => 'mautic.lead.list.form.operator.greaterthan',
            'gte'      => 'mautic.lead.list.form.operator.greaterthanequals',
            'lt'       => 'mautic.lead.list.form.operator.lessthan',
            'lte'      => 'mautic.lead.list.form.operator.lessthanequals',
            'neq'      => 'mautic.lead.list.form.operator.notequals',
            'like'     => 'mautic.lead.list.form.operator.islike',
            'notLike'  => 'mautic.lead.list.form.operator.isnotlike',
            'empty'    => 'mautic.lead.list.form.operator.isempty',
            'notEmpty' => 'mautic.lead.list.form.operator.isnotempty',
        ],
        'bool'        => [
            'eq'  => 'mautic.lead.list.form.operator.equals',
            'neq' => 'mautic.lead.list.form.operator.notequals',
        ],
        'multiselect' => [
            'in'  => 'mautic.lead.list.form.operator.in',
            '!in' => 'mautic.lead.list.form.operator.notin'
        ],
        'select'      => [
            'eq'       => 'mautic.lead.list.form.operator.equals',
            'neq'      => 'mautic.lead.list.form.operator.notequals',
            'empty'    => 'mautic.lead.list.form.operator.isempty',
            'notEmpty' => 'mautic.lead.list.form.operator.isnotempty',
            'in'       => 'mautic.lead.list.form.operator.in',
            '!in'      => 'mautic.lead.list.form.operator.notin',
        ],
        'text'        => [
            'eq'       => 'mautic.lead.list.form.operator.equals',
            'neq'      => 'mautic.lead.list.form.operator.notequals',
            'empty'    => 'mautic.lead.list.form.operator.isempty',
            'notEmpty' => 'mautic.lead.list.form.operator.isnotempty',
            'like'     => 'mautic.lead.list.form.operator.islike',
            'notLike'  => 'mautic.lead.list.form.operator.isnotlike',
        ],
    ];

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
     */
    public function __construct(SecurityContextInterface $securityContext, Report $entity)
    {
        $this->securityContext = $securityContext;
        $this->entity          = $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidReportQueryException
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
     * This method configures the ReportBuilder. It has to return a configured Doctrine DBAL QueryBuilder.
     *
     * @param array $options Options array
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function configureBuilder(array $options)
    {
        $source = $this->entity->getSource();
        $fields = $this->entity->getColumns();
        $order  = $this->entity->getTableOrder();

        // Trigger the REPORT_ON_GENERATE event to initialize the QueryBuilder
        /** @type \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher $dispatcher */
        $dispatcher = $options['dispatcher'];

        $event = new ReportGeneratorEvent($source);
        $dispatcher->dispatch(ReportEvents::REPORT_ON_GENERATE, $event);
        $queryBuilder = $event->getQueryBuilder();

        $selectColumns = [];
        foreach ($fields as $field) {
            if (isset($options['columns'][$field])) {
                $selectColumns[] = "$field as \"{$options['columns'][$field]['label']}\"";
            }
        }

        // Set Content Template
        $this->contentTemplate = $event->getContentTemplate();

        $queryBuilder->select(implode(', ', $selectColumns));

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

        if (!empty($options['order'])) {
            if (is_array($options['order'])) {
                list($column, $dir) = $options['order'];
            } else {
                $column = $options['order'];
                $dir    = 'ASC';
            }
            $queryBuilder->orderBy($column, $dir);
        } elseif (!empty($order)) {
            foreach ($order as $o) {
                if (!empty($o['column'])) {
                    $queryBuilder->orderBy($o['column'], $o['direction']);
                }
            }
        }

        if (!empty($options['limit'])) {
            $queryBuilder->setFirstResult($options['start'])
                ->setMaxResults($options['limit']);
        }

        return $queryBuilder;
    }
}
