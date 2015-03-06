<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class CampaignRepository
 *
 * @package Mautic\CampaignBundle\Entity
 */
class CampaignRepository extends CommonRepository
{

    /**
     * {@inheritdoc}
     */
    public function getEntities($args = array())
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select($this->getTableAlias() . ', cat')
            ->from('MauticCampaignBundle:Campaign', $this->getTableAlias())
            ->leftJoin($this->getTableAlias().'.category', 'cat');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * {@inheritdoc}
     *
     * @param object $entity
     * @param bool   $flush
     */
    public function deleteEntity($entity, $flush = true)
    {
        // Null parents of associated events first
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX . 'campaign_events')
            ->set('parent_id', ':null')
            ->setParameter('null', null)
            ->where('campaign_id = ' . $entity->getId())
            ->execute();

        // Delete events
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->delete(MAUTIC_TABLE_PREFIX . 'campaign_events')
            ->where('campaign_id = ' . $entity->getId())
            ->execute();

        parent::deleteEntity($entity, $flush);
    }

    /**
     * Returns a list of all published (and active) campaigns (optionally for a specific lead)
     *
     * @param null $specificId
     * @param null $leadId
     * @param bool $forList If true, returns ID and name only
     *
     * @return array
     */
    public function getPublishedCampaigns($specificId = null, $leadId = null, $forList = false)
    {
        $q   = $this->_em->createQueryBuilder()
            ->from('MauticCampaignBundle:Campaign', 'c', 'c.id');

        if ($forList) {
            $q->select('partial c.{id, name}, partial ll.{id}');
        } else {
            $q->select('c, l, partial ll.{id}');
        }

        $q->leftJoin('c.leads', 'l')
            ->leftJoin('c.lists', 'll')
            ->leftJoin('c.events', 'e')
            ->leftJoin('e.log', 'o')
            ->where($this->getPublishedByDateExpression($q));


        if (!empty($specificId)) {
            $q->andWhere(
                $q->expr()->eq('c.id', (int) $specificId)
            );
        }

        if (!empty($leadId)) {
            $q->andWhere(
                $q->expr()->eq('IDENTITY(l.lead)', (int) $leadId)
            );
            $q->andWhere(
                $q->expr()->eq('l.manuallyRemoved', ':manuallyRemoved')
            )->setParameter('manuallyRemoved', false);
        }

        $results = $q->getQuery()->getArrayResult();
        return $results;
    }

    /**
     * Returns a list of all published (and active) campaigns that specific lead lists are part of
     *
     * @param array $leadLists
     * @param bool $forList If true, returns ID and name only
     * @param array $ignoreIds array of IDs to ignore (because they are already known)
     *
     * @return array
     */
    public function getPublishedCampaignsByLeadLists(array $leadLists, $forList = false, array $ignoreIds = array())
    {
        $q   = $this->_em->createQueryBuilder()
            ->from('MauticCampaignBundle:Campaign', 'c', 'c.id');

        if ($forList) {
            $q->select('partial c.{id, name}, partial ll.{id}');
        } else {
            $q->select('c, ll');
        }

        $q->leftJoin('c.lists', 'll')
            ->leftJoin('c.leads', 'l')
            ->where($this->getPublishedByDateExpression($q));

        $q->andWhere(
            $q->expr()->in('ll.id', ':lists')
        )->setParameter('lists', $leadLists);

        if (!empty($ignoreIds)) {
            $q->andWhere(
                $q->expr()->notIn('c.id', ':ignoreIds')
            )->setParameter('ignoreIds', $ignoreIds);
        }

        $results = ($forList) ? $q->getQuery()->getArrayResult() : $q->getQuery()->getResult();
        return $results;
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'c';
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, array(
            'c.name',
            'c.description'
        ));
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * Get a list of popular (by logs) campaigns
     *
     * @param integer $limit
     * @return array
     */
    public function getPopularCampaigns($limit = 10)
    {
        $q  = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(cl.ipAddress_id) as hits, c.id AS campaign_id, c.name')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'cl')
            ->leftJoin('cl', MAUTIC_TABLE_PREFIX.'campaign_events', 'ce', 'cl.event_id = ce.id')
            ->leftJoin('ce', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'ce.campaign_id = c.id')
            ->orderBy('hits', 'DESC')
            ->groupBy('c.id, c.name')
            ->setMaxResults($limit);

        $results = $q->execute()->fetchAll();

        return $results;
    }
}
