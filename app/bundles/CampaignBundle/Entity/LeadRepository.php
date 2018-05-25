<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Connection;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * LeadRepository.
 */
class LeadRepository extends CommonRepository
{
    use ContactLimiterTrait;

    /**
     * Get the details of leads added to a campaign.
     *
     * @param      $campaignId
     * @param null $leads
     *
     * @return array
     */
    public function getLeadDetails($campaignId, $leads = null)
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->from('MauticCampaignBundle:Lead', 'lc')
            ->select('lc')
            ->leftJoin('lc.campaign', 'c')
            ->leftJoin('lc.lead', 'l');
        $q->where(
            $q->expr()->eq('c.id', ':campaign')
        )->setParameter('campaign', $campaignId);

        if (!empty($leads)) {
            $q->andWhere(
                $q->expr()->in('l.id', ':leads')
            )->setParameter('leads', $leads);
        }

        $results = $q->getQuery()->getArrayResult();

        $return = [];
        foreach ($results as $r) {
            $return[$r['lead_id']][] = $r;
        }

        return $return;
    }

    /**
     * Get leads for a specific campaign.
     *
     * @deprecated  2.1.0; Use MauticLeadBundle\Entity\LeadRepository\getEntityContacts() instead
     *
     * @param $args
     *
     * @return array
     */
    public function getLeadsWithFields($args)
    {
        return $this->getEntityManager()->getRepository('MauticLeadBundle:Lead')->getEntityContacts(
            $args,
            'campaign_leads',
            isset($args['campaign_id']) ? $args['campaign_id'] : 0,
            ['manually_removed' => 0],
            'campaign_id'
        );
    }

    /**
     * Get leads for a specific campaign.
     *
     * @param      $campaignId
     * @param null $eventId
     *
     * @return array
     */
    public function getLeads($campaignId, $eventId = null)
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->from('MauticCampaignBundle:Lead', 'lc')
            ->select('lc, l')
            ->leftJoin('lc.campaign', 'c')
            ->leftJoin('lc.lead', 'l');
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('lc.manuallyRemoved', ':false'),
                $q->expr()->eq('c.id', ':campaign')
            )
        )
            ->setParameter('false', false, 'boolean')
            ->setParameter('campaign', $campaignId);

        if ($eventId != null) {
            $dq = $this->getEntityManager()->createQueryBuilder();
            $dq->select('el.id')
                ->from('MauticCampaignBundle:LeadEventLog', 'ell')
                ->leftJoin('ell.lead', 'el')
                ->leftJoin('ell.event', 'ev')
                ->where(
                    $dq->expr()->eq('ev.id', ':eventId')
                );

            $q->andWhere('l.id NOT IN('.$dq->getDQL().')')
                ->setParameter('eventId', $eventId);
        }

        $result = $q->getQuery()->getResult();

        return $result;
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        // First check to ensure the $toLead doesn't already exist
        $results = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('cl.campaign_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where('cl.lead_id = '.$toLeadId)
            ->execute()
            ->fetchAll();
        $campaigns = [];
        foreach ($results as $r) {
            $campaigns[] = $r['campaign_id'];
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'campaign_leads')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId);

        if (!empty($campaigns)) {
            $q->andWhere(
                $q->expr()->notIn('campaign_id', $campaigns)
            )->execute();

            // Delete remaining leads as the new lead already belongs
            $this->getEntityManager()->getConnection()->createQueryBuilder()
                ->delete(MAUTIC_TABLE_PREFIX.'campaign_leads')
                ->where('lead_id = '.(int) $fromLeadId)
                ->execute();
        } else {
            $q->execute();
        }
    }

    /**
     * Check Lead in campaign.
     *
     * @param Lead  $lead
     * @param array $options
     *
     * @return bool
     */
    public function checkLeadInCampaigns($lead, $options = [])
    {
        if (empty($options['campaigns'])) {
            return false;
        }
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('l.campaign_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'l');
        $q->where(
                $q->expr()->andX(
                    $q->expr()->eq('l.lead_id', ':leadId'),
                    $q->expr()->in('l.campaign_id', $options['campaigns'], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
                )
            );

        if (!empty($options['dataAddedLimit'])) {
            $q->andWhere($q->expr()
                ->{$options['expr']}('l.date_added', ':dateAdded'))
                ->setParameter('dateAdded', $options['dateAdded']);
        }

        $q->setParameter('leadId', $lead->getId());

        return (bool) $q->execute()->fetchColumn();
    }

    /**
     * @param int            $campaignId
     * @param int            $decisionId
     * @param int            $parentDecisionId
     * @param ContactLimiter $limiter
     *
     * @return array
     */
    public function getInactiveContacts($campaignId, $decisionId, $parentDecisionId, ContactLimiter $limiter)
    {
        // Main query
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('l.lead_id, l.date_added')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'l')
            ->where($q->expr()->eq('l.campaign_id', ':campaignId'))
            // Order by ID so we can query by greater than X contact ID when batching
            ->orderBy('l.lead_id')
            ->setMaxResults($limiter->getBatchLimit())
            ->setParameter('campaignId', (int) $campaignId)
            ->setParameter('decisionId', (int) $decisionId);

        // Contact IDs
        $this->updateQueryFromContactLimiter('l', $q, $limiter);

        // Limit to events that have not been executed or scheduled yet
        $eventQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $eventQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log')
            ->where(
                $eventQb->expr()->andX(
                    $eventQb->expr()->eq('log.event_id', ':decisionId'),
                    $eventQb->expr()->eq('log.lead_id', 'l.lead_id'),
                    $eventQb->expr()->eq('log.rotation', 'l.rotation')
                )
            );
        $q->andWhere(
            sprintf('NOT EXISTS (%s)', $eventQb->getSQL())
        );

        if ($parentDecisionId) {
            // Limit to events that have no grandparent or whose grandparent has already been executed
            $grandparentQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
            $grandparentQb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'grandparent_log')
                ->where(
                    $grandparentQb->expr()->eq('grandparent_log.event_id', ':grandparentId'),
                    $grandparentQb->expr()->eq('grandparent_log.lead_id', 'l.lead_id'),
                    $grandparentQb->expr()->eq('grandparent_log.rotation', 'l.rotation')
                );
            $q->setParameter('grandparentId', (int) $parentDecisionId);

            $q->andWhere(
                sprintf('EXISTS (%s)', $grandparentQb->getSQL())
            );
        }

        $results  = $q->execute()->fetchAll();
        $contacts = [];
        foreach ($results as $result) {
            $contacts[$result['lead_id']] = new \DateTime($result['date_added'], new \DateTimeZone('UTC'));
        }

        return $contacts;
    }

    /**
     * This is approximate because the query that fetches contacts per decision is based on if the grandparent has been executed or not.
     *
     * @param int  $decisionId
     * @param int  $parentDecisionId
     * @param null $specificContactId
     *
     * @return int
     */
    public function getInactiveContactCount($campaignId, array $decisionIds, ContactLimiter $limiter)
    {
        // We have to loop over each decision to get a count or else any contact that has executed any single one of the decision IDs
        // will not be included potentially resulting in not having the inactive path analyzed

        $totalCount = 0;

        foreach ($decisionIds as $decisionId) {
            // Main query
            $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
            $q->select('count(*)')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'l')
                ->where($q->expr()->eq('l.campaign_id', ':campaignId'))
                // Order by ID so we can query by greater than X contact ID when batching
                ->orderBy('l.lead_id')
                ->setParameter('campaignId', (int) $campaignId);

            // Contact IDs
            $this->updateQueryFromContactLimiter('l', $q, $limiter, true);

            // Limit to events that have not been executed or scheduled yet
            $eventQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
            $eventQb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log')
                ->where(
                    $eventQb->expr()->andX(
                        $eventQb->expr()->eq('log.event_id', $decisionId),
                        $eventQb->expr()->eq('log.lead_id', 'l.lead_id'),
                        $eventQb->expr()->eq('log.rotation', 'l.rotation')
                    )
                );
            $q->andWhere(
                sprintf('NOT EXISTS (%s)', $eventQb->getSQL())
            );

            $totalCount += (int) $q->execute()->fetchColumn();
        }

        return $totalCount;
    }

    /**
     * @param array    $contactIds
     * @param Campaign $campaign
     *
     * @return array
     */
    public function getCampaignMembers(array $contactIds, Campaign $campaign)
    {
        $qb = $this->createQueryBuilder('l');

        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->eq('l.campaign', ':campaign'),
                $qb->expr()->in('IDENTITY(l.lead)', ':contactIds')
            )
        )
            ->setParameter('campaign', $campaign)
            ->setParameter('contactIds', $contactIds, Connection::PARAM_INT_ARRAY);

        $results = $qb->getQuery()->getResult();

        $campaignMembers = [];

        /** @var Lead $result */
        foreach ($results as $result) {
            $campaignMembers[$result->getLead()->getId()] = $result;
        }

        return $campaignMembers;
    }

    /**
     * @param array $contactIds
     * @param       $campaignId
     *
     * @return array
     */
    public function getContactRotations(array $contactIds, $campaignId)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('cl.lead_id, cl.rotation')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('cl.campaign_id', ':campaignId'),
                    $qb->expr()->in('cl.lead_id', ':contactIds')
                )
            )
            ->setParameter('campaignId', (int) $campaignId)
            ->setParameter('contactIds', $contactIds, Connection::PARAM_INT_ARRAY);

        $results = $qb->execute()->fetchAll();

        $contactRotations = [];
        foreach ($results as $result) {
            $contactRotations[$result['lead_id']] = $result['rotation'];
        }

        return $contactRotations;
    }
}
