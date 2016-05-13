<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class StageRepository
 */
class StageRepository extends CommonRepository
{

    /**
     * {@inheritdoc}
     */
    public function getEntities($args = array())
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select($this->getTableAlias() . ', cat')
            ->from('MauticStageBundle:Stage', $this->getTableAlias())
            ->leftJoin($this->getTableAlias().'.category', 'cat');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'p';
    }

    /**
     * Get array of published actions based on type
     *
     * @param string $type
     *
     * @return array
     */
    public function getPublishedByType($type)
    {
        $q = $this->createQueryBuilder('p')
            ->select('partial p.{id, type, name, properties}')
            ->setParameter('type', $type);

        //make sure the published up and down dates are good
        $expr = $this->getPublishedByDateExpression($q);
        $expr->add($q->expr()->eq('p.type', ':type'));

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
            ->from(MAUTIC_TABLE_PREFIX . 'stage_lead_action_log', 'x')
            ->innerJoin('x', MAUTIC_TABLE_PREFIX . 'stages', 's', 'x.stage_id = s.id');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('s.type', ':type'),
                $q->expr()->eq('x.lead_id', (int) $leadId)
            )
        )
            ->setParameter('type', $type);

        $results = $q->execute()->fetchAll();

        $return = array();

        foreach ($results as $r) {
            $return[$r['id']] = $r;
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, array(
            's.name',
            's.description'
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
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
}
