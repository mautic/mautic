<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Entity;

use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class StageRepository.
 */
class StageRepository extends CommonRepository
{
    /**
     * {@inheritdoc}
     */
    public function getEntities(array $args = [])
    {
        $q = $this
            ->createQueryBuilder($this->getTableAlias())
            ->leftJoin($this->getTableAlias().'.category', 'c');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 's';
    }

    /**
     * Get array of published actions based on type.
     *
     * @param string $type
     *
     * @return array
     */
    public function getPublishedByType($type)
    {
        $q = $this->createQueryBuilder('s')
            ->select('partial s.{id, name}')
            ->setParameter('type', $type);

        //make sure the published up and down dates are good
        $expr = $this->getPublishedByDateExpression($q);

        $q->where($expr);

        return $q->getQuery()->getResult();
    }

    /**
     * @param string $type
     * @param int    $leadId
     *
     * @return array
     */
    public function getCompletedLeadActions($type, $leadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('s.*')
            ->from(MAUTIC_TABLE_PREFIX.'stage_lead_action_log', 'x')
            ->innerJoin('x', MAUTIC_TABLE_PREFIX.'stages', 's', 'x.stage_id = s.id');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('x.lead_id', (int) $leadId)
            )
        );

        $results = $q->execute()->fetchAll();

        $return = [];

        foreach ($results as $r) {
            $return[$r['id']] = $r;
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    protected function addCatchAllWhereClause(QueryBuilder $q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            's.name',
            's.description',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addSearchCommandWhereClause(QueryBuilder $q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * Get a list of lists.
     *
     * @param bool   $user
     * @param string $alias
     * @param string $id
     *
     * @return array
     */
    public function getStages($user = false, $id = '')
    {
        static $stages = [];

        if (is_object($user)) {
            $user = $user->getId();
        }

        $key = (int) $user.$id;
        if (isset($stages[$key])) {
            return $stages[$key];
        }

        $q = $this->_em->createQueryBuilder()
            ->from('MauticStageBundle:Stage', 's', 's.id');

        $q->select('partial s.{id, name}')
            ->andWhere($q->expr()->eq('s.isPublished', ':true'))
            ->setParameter('true', true, 'boolean');

        if (!empty($user)) {
            $q->orWhere('s.createdBy = :user');
            $q->setParameter('user', $user);
        }

        if (!empty($id)) {
            $q->andWhere(
                $q->expr()->neq('s.id', $id)
            );
        }

        $q->orderBy('s.name');

        $results = $q->getQuery()->getArrayResult();

        $stages[$key] = $results;

        return $results;
    }

    /**
     * Get a list of stages.
     *
     * @param string $name
     *
     * @return array
     */
    public function getStageByName($stageName)
    {
        if (!$stageName) {
            return false;
        }

        $q = $this->_em->createQueryBuilder()
            ->from('MauticStageBundle:Stage', 's', 's.id');

        $q->select('partial s.{id, name}')
            ->andWhere($q->expr()->eq('s.isPublished', ':true'))
            ->setParameter('true', true, 'boolean');
        $q->andWhere('s.name = :stage')
            ->setParameter('stage', $stageName);

        $result = $q->getQuery()->getResult();

        if ($result) {
            $key = array_keys($result);

            return $result[$key[0]];
        }

        return null;
    }
}
