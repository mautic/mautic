<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Entity;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * ReportRepository.
 */
class ReportRepository extends CommonRepository
{
    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $q = $this
            ->createQueryBuilder('r')
            ->select('r');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * {@inheritdoc}
     */
    protected function addCatchAllWhereClause(QueryBuilder $q, $filter)
    {
        return $this->addStandardCatchAllWhereClause(
            $q,
            $filter,
            [
                'r.name',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function addSearchCommandWhereClause(QueryBuilder $q, $filter)
    {
        $command                 = $filter->command;
        $unique                  = $this->generateRandomParameterName();
        $returnParameter         = false; //returning a parameter that is not used will lead to a Doctrine error
        list($expr, $parameters) = parent::addSearchCommandWhereClause($q, $filter);

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.ispublished'):
            case $this->translator->trans('mautic.core.searchcommand.ispublished', [], null, 'en_US'):
                $expr            = $q->expr()->eq('r.isPublished', ":$unique");
                $forceParameters = [$unique => true];

                break;
            case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
            case $this->translator->trans('mautic.core.searchcommand.isunpublished', [], null, 'en_US'):
                $expr            = $q->expr()->eq('r.isPublished', ":$unique");
                $forceParameters = [$unique => false];

                break;
            case $this->translator->trans('mautic.core.searchcommand.ismine'):
            case $this->translator->trans('mautic.core.searchcommand.ismine', [], null, 'en_US'):
                $expr = $q->expr()->eq('IDENTITY(r.createdBy)', $this->currentUser->getId());
                break;
        }

        if ($expr && $filter->not) {
            $expr = $q->expr()->not($expr);
        }

        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif ($returnParameter) {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = ["$unique" => $string];
        }

        return [$expr, $parameters];
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCommands()
    {
        $commands = [
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.ismine',
        ];

        return array_merge($commands, parent::getSearchCommands());
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder()
    {
        return [
            ['r.name', 'ASC'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'r';
    }

    /**
     * @param $viewOther
     */
    public function findReportsWithGraphs($ownedBy = null)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('r.id, r.name, r.graphs')
            ->from(MAUTIC_TABLE_PREFIX.'reports', 'r')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('r.graphs'),
                    $qb->expr()->neq('r.graphs', $qb->expr()->literal('a:0:{}')),
                    $qb->expr()->eq('r.is_published', ':true')
                )
            );
        $qb->setParameter('true', true, 'boolean');

        if ($ownedBy) {
            $qb->andWhere(
                $qb->expr()->eq('r.created_by', (int) $ownedBy)
            );
        }

        $qb->orderBy('r.name');

        return $qb->execute()->fetchAll();
    }
}
