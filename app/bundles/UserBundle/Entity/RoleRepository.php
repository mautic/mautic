<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * RoleRepository.
 */
class RoleRepository extends CommonRepository
{
    /**
     * Get a list of roles.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $q = $this->createQueryBuilder('r');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * Get a list of roles.
     *
     * @param string $search
     * @param int    $limit
     * @param int    $start
     *
     * @return array
     */
    public function getRoleList($search = '', $limit = 10, $start = 0)
    {
        $q = $this->_em->createQueryBuilder();

        $q->select('partial r.{id, name}')
            ->from('MauticUserBundle:Role', 'r');

        if (!empty($search)) {
            $q->where('r.name LIKE :search')
                ->setParameter('search', "{$search}%");
        }

        $q->orderBy('r.name');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
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
                'r.description',
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
            case $this->translator->trans('mautic.user.user.searchcommand.isadmin'):
            case $this->translator->trans('mautic.user.user.searchcommand.isadmin', [], null, 'en_US'):
                $expr = $q->expr()->eq('r.isAdmin', 1);
                break;
            case $this->translator->trans('mautic.core.searchcommand.name'):
            case $this->translator->trans('mautic.core.searchcommand.name', [], null, 'en_US'):
                $expr            = $q->expr()->like('r.name', ':'.$unique);
                $returnParameter = true;
                break;
        }

        if ($filter->not) {
            $expr = $q->expr()->not($expr);
        }

        if ($returnParameter) {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = ["$unique" => $string];
        }

        return [
            $expr,
            $parameters,
        ];
    }

    /**
     * Get a count of users that belong to the role.
     *
     * @param $roleIds
     *
     * @return array
     */
    public function getUserCount($roleIds)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(u.id) as thecount, u.role_id')
            ->from(MAUTIC_TABLE_PREFIX.'users', 'u');

        $returnArray = (is_array($roleIds));

        if (!$returnArray) {
            $roleIds = [$roleIds];
        }

        $q->where(
            $q->expr()->in('u.role_id', $roleIds)
        )
            ->groupBy('u.role_id');

        $result = $q->execute()->fetchAll();

        $return = [];
        foreach ($result as $r) {
            $return[$r['role_id']] = $r['thecount'];
        }

        // Ensure lists without leads have a value
        foreach ($roleIds as $r) {
            if (!isset($return[$r])) {
                $return[$r] = 0;
            }
        }

        return ($returnArray) ? $return : $return[$roleIds[0]];
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCommands()
    {
        $commands = [
            'mautic.user.user.searchcommand.isadmin',
            'mautic.core.searchcommand.name',
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
}
