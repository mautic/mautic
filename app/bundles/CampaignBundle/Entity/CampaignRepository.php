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
            $q->select('c, l, partial ll.{id}')
                ->leftJoin('c.events', 'e')
                ->leftJoin('e.log', 'o');
        }

        if ($leadId || !$forList) {
            $q->leftJoin('c.leads', 'l');
        }

        $q->leftJoin('c.lists', 'll')
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
        $q = $this->_em->createQueryBuilder()
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
     * Get array of list IDs assigned to this campaign
     *
     * @param $id
     */
    public function getCampaignListIds($id = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leadlist_xref', 'cl');

        if ($id) {
            $q->select('cl.leadlist_id')
                ->where(
                    $q->expr()->eq('cl.campaign_id', $id)
                );
        } else {
            // Retrieve a list of unique IDs that are assigned to a campaign
            $q->select('DISTINCT cl.leadlist_id');
        }

        $lists  = array();
        $results = $q->execute()->fetchAll();

        foreach ($results as $r) {
            $lists[] = $r['leadlist_id'];
        }

        return $lists;
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

    /**
     * @param array $lists
     *
     * @return array|int
     */
    public function getCampaignLeadsFromLists($id, array $lists, $args = array())
    {
        $newOnly       = (!array_key_exists('newOnly', $args)) ? false : $args['newOnly'];
        $batchLimiters = (!array_key_exists('batchLimiters', $args)) ? false : $args['batchLimiters'];
        $countOnly     = (!array_key_exists('countOnly', $args)) ? false : $args['countOnly'];
        $filterOutIds  = (!array_key_exists('filterOutIds', $args)) ? false : $args['filterOutIds'];
        $start         = (!array_key_exists('start', $args)) ? false : $args['start'];
        $limit         = (!array_key_exists('limit', $args)) ? false : $args['limit'];

        $leads = ($countOnly) ? 0 : array();

        $q = $this->_em->getConnection()->createQueryBuilder();
        if ($countOnly) {
            $q->select('max(ll.lead_id) as max_id, count(distinct(ll.lead_id)) as lead_count')
                ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll');
        } else {
            $q->select('distinct(ll.lead_id) as id')
                ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll');
        }

        $expr = $q->expr()->andX();


        $expr->addMultiple(
            array(
                $q->expr()->in('ll.leadlist_id', $lists),
                $q->expr()->eq('ll.manually_removed', ':false')
            )
        );

        $q->setParameter('false', false, 'boolean');

        // Set batch limiters to ensure the same group is used
        if ($batchLimiters) {
            $expr->add(
            // Only leads in the list at the time of count
                $q->expr()->lte('ll.date_added', $q->expr()->literal($batchLimiters['dateTime']))
            );

            if (!empty($batchLimiters['maxId'])) {
                // Only leads that existed at the time of count
                $expr->add(
                    $q->expr()->lte('ll.lead_id', $batchLimiters['maxId'])
                );
            }
        }

        // Set limits if applied
        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        if ($filterOutIds) {
            $expr->add(
                $q->expr()->notIn('ll.lead_id', $filterOutIds)
            );
        }

        $q->where($expr);

        // Exclude those that have been manually removed from the campagin
        $dq = $this->_em->getConnection()->createQueryBuilder();
        $dq->select('cl.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl');

        $expr = $dq->expr()->andX(
            $dq->expr()->eq('cl.campaign_id', (int) $id)
        );

        if (!$newOnly) {
            $expr->add(
                $dq->expr()->eq('cl.manually_removed', ':true')
            );
            $q->setParameter('true', true, 'boolean');
        }

        $dq->where($expr);

        $q->andWhere('ll.lead_id NOT IN '.sprintf('(%s)', $dq->getSQL()));

        $q->orderBy('ll.lead_id', 'ASC');

        $results = $q->execute()->fetchAll();

        foreach ($results as $r) {
            if ($countOnly) {
                $leads = array(
                    'count' => $r['lead_count'],
                    'maxId' => $r['max_id']
                );
            } else {
                $leads[] = $r['id'];
            }
        }

        unset($parameters, $q, $expr, $results);

        return $leads;
    }

    /**
     * Get leads that do not belong based on lead lists
     *
     * @param       $id
     * @param array $lists
     * @param array $args
     *
     * @return array|int\
     */
    public function getCampaignOrphanLeads($id, array $lists, $args = array())
    {
        $batchLimiters = (!array_key_exists('batchLimiters', $args)) ? false : $args['batchLimiters'];
        $countOnly     = (!array_key_exists('countOnly', $args)) ? false : $args['countOnly'];
        $filterOutIds  = (!array_key_exists('filterOutIds', $args)) ? false : $args['filterOutIds'];
        $start         = (!array_key_exists('start', $args)) ? false : $args['start'];
        $limit         = (!array_key_exists('limit', $args)) ? false : $args['limit'];

        $leads = ($countOnly) ? 0 : array();

        $q = $this->_em->getConnection()->createQueryBuilder();
        if ($countOnly) {
            $q->select('max(cl.lead_id) as max_id, count(distinct(cl.lead_id)) as lead_count')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl');
        } else {
            $q->select('distinct(cl.lead_id) as id')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl');
        }

        $expr = $q->expr()->andX();

        $expr->add(
            $q->expr()->eq('cl.campaign_id', (int) $id),
            $q->expr()->eq('cl.manually_removed', ':false'),
            $q->expr()->eq('cl.manually_added', ':false')
        );

        $q->setParameter('false', false, 'boolean')
            ->setParameter('true', true, 'boolean');

        // Set batch limiters to ensure the same group is used
        if ($batchLimiters) {
            $expr->add(
            // Only leads in the list at the time of count
                $q->expr()->lte('cl.date_added', $q->expr()->literal($batchLimiters['dateTime']))
            );

            if (!empty($batchLimiters['maxId'])) {
                // Only leads that existed at the time of count
                $expr->add(
                    $q->expr()->lte('cl.lead_id', $batchLimiters['maxId'])
                );
            }
        }

        // Set limits if applied
        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        if ($filterOutIds) {
            $expr->add(
                $q->expr()->notIn('cl.lead_id', $filterOutIds)
            );
        }

        $q->where($expr);

        // Find those that no longer belong to the campaign's lists
        $dq = $this->_em->getConnection()->createQueryBuilder();
        $dq->select('ll.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll');

        $dq->where(
            $dq->expr()->andX(
                $dq->expr()->in('ll.leadlist_id', $lists),
                $dq->expr()->eq('ll.manually_removed', ':false')
            )
        );

        $q->andWhere('cl.lead_id NOT IN ' . sprintf('(%s)', $dq->getSQL()));

        $q->orderBy('cl.lead_id', 'ASC');

        $results = $q->execute()->fetchAll();

        foreach ($results as $r) {
            if ($countOnly) {
                $leads = array(
                    'count' => $r['lead_count'],
                    'maxId' => $r['max_id']
                );
            } else {
                $leads[] = $r['id'];
            }
        }

        unset($parameters, $q, $expr, $results);

        return $leads;
    }

    /**
     * Get a count of leads that belong to the campaign
     *
     * @param       $campaignId
     * @param array $ignoreLeads
     *
     * @return mixed
     */
    public function getCampaignLeadCount($campaignId, $ignoreLeads = array())
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(cl.lead_id) as lead_count')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('cl.campaign_id', (int) $campaignId),
                    $q->expr()->eq('cl.manually_removed', ':false')
                )
            )
            ->setParameter('false', false, 'boolean')
            ->orderBy('cl.lead_id', 'ASC');

        if (!empty($ignoreLeads)) {
            $q->andWhere(
                $q->expr()->notIn('cl.lead_id', $ignoreLeads)
            );
        }

        $results = $q->execute()->fetchAll();

        return $results[0]['lead_count'];
    }

    /**
     * Get lead IDs of a campaign
     *
     * @param $campaignId
     */
    public function getCampaignLeadIds($campaignId, $start = 0, $limit = false, $ignoreLeads = array())
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('cl.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('cl.campaign_id', (int) $campaignId),
                    $q->expr()->eq('cl.manually_removed', ':false')
                )
            )
            ->setParameter('false', false, 'boolean')
            ->orderBy('cl.lead_id', 'ASC');

        if (!empty($ignoreLeads)) {
            $q->andWhere(
                $q->expr()->notIn('cl.lead_id', $ignoreLeads)
            );
        }

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        $results = $q->execute()->fetchAll();

        $leads = array();
        foreach ($results as $r) {
            $leads[] = $r['lead_id'];
        }

        unset($results);

        return $leads;
    }
}
