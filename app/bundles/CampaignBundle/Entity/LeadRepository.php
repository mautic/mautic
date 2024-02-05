<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CampaignBundle\Entity\Result\CountResult;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Lead>
 */
class LeadRepository extends CommonRepository
{
    use ContactLimiterTrait;
    use ReplicaConnectionTrait;

    /**
     * Get the details of leads added to a campaign.
     */
    public function getLeadDetails($campaignId, $leads = null): array
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->from(\Mautic\CampaignBundle\Entity\Lead::class, 'lc')
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
     * @return array
     */
    public function getLeads($campaignId, $eventId = null)
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->from(\Mautic\CampaignBundle\Entity\Lead::class, 'lc')
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

        if (null != $eventId) {
            $dq = $this->getEntityManager()->createQueryBuilder();
            $dq->select('el.id')
                ->from(\Mautic\CampaignBundle\Entity\LeadEventLog::class, 'ell')
                ->leftJoin('ell.lead', 'el')
                ->leftJoin('ell.event', 'ev')
                ->where(
                    $dq->expr()->eq('ev.id', ':eventId')
                );

            $q->andWhere('l.id NOT IN('.$dq->getDQL().')')
                ->setParameter('eventId', $eventId);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     */
    public function updateLead($fromLeadId, $toLeadId): void
    {
        // First check to ensure the $toLead doesn't already exist
        $results = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('cl.campaign_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where('cl.lead_id = '.$toLeadId)
            ->executeQuery()
            ->fetchAllAssociative();

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
            )->executeStatement();

            // Delete remaining leads as the new lead already belongs
            $this->getEntityManager()->getConnection()->createQueryBuilder()
                ->delete(MAUTIC_TABLE_PREFIX.'campaign_leads')
                ->where('lead_id = '.(int) $fromLeadId)
                ->executeStatement();
        } else {
            $q->executeStatement();
        }
    }

    /**
     * Check Lead in campaign.
     *
     * @param Lead  $lead
     * @param array $options
     */
    public function checkLeadInCampaigns($lead, $options = []): bool
    {
        if (empty($options['campaigns'])) {
            return false;
        }
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('l.campaign_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'l');
        $q->where(
            $q->expr()->and(
                $q->expr()->eq('l.lead_id', ':leadId'),
                $q->expr()->in('l.campaign_id', $options['campaigns'])
            )
        );

        if (!empty($options['dataAddedLimit'])) {
            $q->andWhere($q->expr()
                ->{$options['expr']}('l.date_added', ':dateAdded'))
                ->setParameter('dateAdded', $options['dateAdded']);
        }

        $q->setParameter('leadId', $lead->getId());

        return (bool) $q->executeQuery()->fetchOne();
    }

    /**
     * @param int $campaignId
     * @param int $decisionId
     * @param int $parentDecisionId
     *
     * @return array<string, \DateTimeInterface>
     */
    public function getInactiveContacts($campaignId, $decisionId, $parentDecisionId, ContactLimiter $limiter): array
    {
        // Main query
        $q = $this->getReplicaConnection($limiter)->createQueryBuilder();
        $q->select('l.lead_id, l.date_added')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'l')
            ->where(
                $q->expr()->and(
                    $q->expr()->eq('l.campaign_id', ':campaignId'),
                    $q->expr()->eq('l.manually_removed', 0)
                )
            )
            // Order by ID so we can query by greater than X contact ID when batching
            ->orderBy('l.lead_id')
            ->setMaxResults($limiter->getBatchLimit())
            ->setParameter('campaignId', (int) $campaignId)
            ->setParameter('decisionId', (int) $decisionId);

        // Contact IDs
        $this->updateQueryFromContactLimiter('l', $q, $limiter);

        // Limit to events that have not been executed or scheduled yet
        $eventQb = $this->getReplicaConnection($limiter)->createQueryBuilder();
        $eventQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log')
            ->where(
                $eventQb->expr()->and(
                    $eventQb->expr()->eq('log.event_id', ':decisionId'),
                    $eventQb->expr()->eq('log.lead_id', 'l.lead_id'),
                    $eventQb->expr()->eq('log.rotation', 'l.rotation')
                )
            );
        $q->andWhere(
            sprintf('NOT EXISTS (%s)', $eventQb->getSQL())
        );

        if ($parentDecisionId) {
            // Limit to events  whose grandparent has already been executed
            $grandparentQb = $this->getReplicaConnection($limiter)->createQueryBuilder();
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
        } else {
            // Limit to events that have no grandparent and any of events was already executed by jump to event
            $anyEventQb = $this->getReplicaConnection($limiter)->createQueryBuilder();
            $anyEventQb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'any_log')
                ->where(
                    $anyEventQb->expr()->eq('any_log.lead_id', 'l.lead_id'),
                    $anyEventQb->expr()->eq('any_log.campaign_id', 'l.campaign_id'),
                    $anyEventQb->expr()->eq('any_log.rotation', 'l.rotation')
                );

            $q->andWhere(
                sprintf('NOT EXISTS (%s)', $anyEventQb->getSQL())
            );
        }

        if ($limiter->hasCampaignLimit() && $limiter->getCampaignLimitRemaining() < $limiter->getBatchLimit()) {
            $q->setMaxResults($limiter->getCampaignLimitRemaining());
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        $contacts = [];
        foreach ($results as $result) {
            $contacts[$result['lead_id']] = new \DateTime($result['date_added'], new \DateTimeZone('UTC'));
        }

        if ($limiter->hasCampaignLimit()) {
            $limiter->reduceCampaignLimitRemaining(count($contacts));
        }

        return $contacts;
    }

    /**
     * This is approximate because the query that fetches contacts per decision is based on if the grandparent has been executed or not.
     */
    public function getInactiveContactCount($campaignId, array $decisionIds, ContactLimiter $limiter): int
    {
        // We have to loop over each decision to get a count or else any contact that has executed any single one of the decision IDs
        // will not be included potentially resulting in not having the inactive path analyzed

        $totalCount = 0;

        foreach ($decisionIds as $decisionId) {
            // Main query
            $q = $this->getReplicaConnection()->createQueryBuilder();
            $q->select('count(*)')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'l')
                ->where(
                    $q->expr()->and(
                        $q->expr()->eq('l.campaign_id', ':campaignId'),
                        $q->expr()->eq('l.manually_removed', 0)
                    )
                )
                // Order by ID so we can query by greater than X contact ID when batching
                ->orderBy('l.lead_id')
                ->setParameter('campaignId', (int) $campaignId);

            // Contact IDs
            $this->updateQueryFromContactLimiter('l', $q, $limiter, true);

            // Limit to events that have not been executed or scheduled yet
            $eventQb = $this->getReplicaConnection($limiter)->createQueryBuilder();
            $eventQb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log')
                ->where(
                    $eventQb->expr()->and(
                        $eventQb->expr()->eq('log.event_id', $decisionId),
                        $eventQb->expr()->eq('log.lead_id', 'l.lead_id'),
                        $eventQb->expr()->eq('log.rotation', 'l.rotation')
                    )
                );
            $q->andWhere(
                sprintf('NOT EXISTS (%s)', $eventQb->getSQL())
            );

            $totalCount += (int) $q->executeQuery()->fetchOne();
        }

        return $totalCount;
    }

    public function getCampaignMembers(array $contactIds, Campaign $campaign): array
    {
        $qb = $this->createQueryBuilder('l');

        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->eq('l.campaign', ':campaign'),
                $qb->expr()->in('IDENTITY(l.lead)', ':contactIds')
            )
        )
            ->setParameter('campaign', $campaign)
            ->setParameter('contactIds', $contactIds, \Doctrine\DBAL\ArrayParameterType::INTEGER);

        $results = $qb->getQuery()->getResult();

        $campaignMembers = [];

        /** @var Lead $result */
        foreach ($results as $result) {
            $campaignMembers[$result->getLead()->getId()] = $result;
        }

        return $campaignMembers;
    }

    public function getContactRotations(array $contactIds, $campaignId): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('cl.lead_id, cl.rotation')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('cl.campaign_id', ':campaignId'),
                    $qb->expr()->in('cl.lead_id', ':contactIds')
                )
            )
            ->setParameter('campaignId', (int) $campaignId)
            ->setParameter('contactIds', $contactIds, \Doctrine\DBAL\ArrayParameterType::INTEGER);

        $results = $qb->executeQuery()->fetchAllAssociative();

        $contactRotations = [];
        foreach ($results as $result) {
            $contactRotations[$result['lead_id']] = $result['rotation'];
        }

        return $contactRotations;
    }

    /**
     * @param int  $campaignId
     * @param bool $campaignCanBeRestarted
     */
    public function getCountsForCampaignContactsBySegment($campaignId, ContactLimiter $limiter, $campaignCanBeRestarted = false): CountResult
    {
        if (!$segments = $this->getCampaignSegments($campaignId)) {
            return new CountResult(0, 0, 0);
        }

        $qb = $this->getReplicaConnection($limiter)->createQueryBuilder();
        $qb->select('min(ll.lead_id) as min_id, max(ll.lead_id) as max_id, count(distinct(ll.lead_id)) as the_count')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('ll.manually_removed', 0),
                    $qb->expr()->in('ll.leadlist_id', $segments)
                )
            );

        $this->updateQueryFromContactLimiter('ll', $qb, $limiter, true);
        $this->updateQueryWithExistingMembershipExclusion((int) $campaignId, $qb, (bool) $campaignCanBeRestarted);

        if (!$campaignCanBeRestarted) {
            $this->updateQueryWithHistoryExclusion($campaignId, $qb);
        }

        $result = $qb->executeQuery()->fetchAssociative();

        return new CountResult($result['the_count'], $result['min_id'], $result['max_id']);
    }

    /**
     * Get all contacts based on the campaigns segment
     * a limit for how many contacts to process at one time
     * and the campaign setting if a contact is allowed to restart
     * a campaign.
     *
     * @param int  $campaignId
     * @param bool $campaignCanBeRestarted
     *
     * @return array<int|string, string>
     */
    public function getCampaignContactsBySegments($campaignId, ContactLimiter $limiter, $campaignCanBeRestarted = false): array
    {
        if (!$segments = $this->getCampaignSegments($campaignId)) {
            return [];
        }

        $qb = $this->getReplicaConnection($limiter)->createQueryBuilder();
        $qb->select('distinct(ll.lead_id) as id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('ll.manually_removed', 0),
                    $qb->expr()->in('ll.leadlist_id', $segments)
                )
            );

        $this->updateQueryFromContactLimiter('ll', $qb, $limiter);
        $this->updateQueryWithExistingMembershipExclusion((int) $campaignId, $qb, (bool) $campaignCanBeRestarted);

        if (!$campaignCanBeRestarted) {
            $this->updateQueryWithHistoryExclusion($campaignId, $qb);
        }

        $results = $qb->executeQuery()->fetchAllAssociative();

        $contacts = [];
        foreach ($results as $result) {
            $contacts[$result['id']] = $result['id'];
        }

        return $contacts;
    }

    /**
     * @param int $campaignId
     */
    public function getCountsForOrphanedContactsBySegments($campaignId, ContactLimiter $limiter): CountResult
    {
        $segments = $this->getCampaignSegments($campaignId);

        $qb = $this->getReplicaConnection($limiter)->createQueryBuilder();
        $qb->select('min(cl.lead_id) as min_id, max(cl.lead_id) as max_id, count(cl.lead_id) as the_count')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('cl.campaign_id', (int) $campaignId),
                    $qb->expr()->eq('cl.manually_removed', 0),
                    $qb->expr()->eq('cl.manually_added', 0)
                )
            );

        $this->updateQueryFromContactLimiter('cl', $qb, $limiter, true);
        $this->updateQueryWithSegmentMembershipExclusion($segments, $qb);

        $result = $qb->executeQuery()->fetchAssociative();

        return new CountResult($result['the_count'], $result['min_id'], $result['max_id']);
    }

    public function getOrphanedContacts($campaignId, ContactLimiter $limiter): array
    {
        $segments = $this->getCampaignSegments($campaignId);

        $qb = $this->getReplicaConnection($limiter)->createQueryBuilder();
        $qb->select('cl.lead_id as id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('cl.campaign_id', (int) $campaignId),
                    $qb->expr()->eq('cl.manually_removed', 0),
                    $qb->expr()->eq('cl.manually_added', 0)
                )
            );

        $this->updateQueryFromContactLimiter('cl', $qb, $limiter, false);
        $this->updateQueryWithSegmentMembershipExclusion($segments, $qb);

        $results = $qb->executeQuery()->fetchAllAssociative();

        $contacts = [];
        foreach ($results as $result) {
            $contacts[$result['id']] = $result['id'];
        }

        return $contacts;
    }

    /**
     * Takes an array of contact ID's and increments
     * their current rotation in a campaign by 1.
     *
     * @param int[] $contactIds
     * @param int   $campaignId
     */
    public function incrementCampaignRotationForContacts(array $contactIds, $campaignId): void
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->set('cl.rotation', 'cl.rotation + 1')
            ->where(
                $q->expr()->and(
                    $q->expr()->in('cl.lead_id', ':contactIds'),
                    $q->expr()->eq('cl.campaign_id', ':campaignId')
                )
            )
            ->setParameter('contactIds', $contactIds, \Doctrine\DBAL\ArrayParameterType::INTEGER)
            ->setParameter('campaignId', (int) $campaignId)
            ->executeStatement();
    }

    private function getCampaignSegments($campaignId): array
    {
        // Get published segments for this campaign
        $segmentResults = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('cl.leadlist_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leadlist_xref', 'cl')
            ->join('cl', MAUTIC_TABLE_PREFIX.'lead_lists', 'll', 'll.id = cl.leadlist_id and ll.is_published = 1')
            ->where('cl.campaign_id = '.(int) $campaignId)
            ->executeQuery()
            ->fetchAllAssociative();

        if (empty($segmentResults)) {
            // No segments so no contacts
            return [];
        }

        $segments = [];
        foreach ($segmentResults as $result) {
            $segments[] = $result['leadlist_id'];
        }

        return $segments;
    }

    private function updateQueryWithExistingMembershipExclusion(int $campaignId, QueryBuilder $qb, bool $campaignCanBeRestarted = false): void
    {
        $membershipConditions = $qb->expr()->and(
            $qb->expr()->eq('cl.lead_id', 'll.lead_id'),
            $qb->expr()->eq('cl.campaign_id', (int) $campaignId)
        );

        if ($campaignCanBeRestarted) {
            $alreadyInCampaign           = $qb->expr()->eq('cl.manually_removed', 0);
            $removedFromCampaignManually = $qb->expr()->and(
                $qb->expr()->eq('cl.manually_removed', 1),
                $qb->expr()->isNull('cl.date_last_exited'),
            );

            $membershipConditions = $qb->expr()->and(
                $membershipConditions,
                $qb->expr()->or($alreadyInCampaign, $removedFromCampaignManually)
            );
        }

        $subq = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $qb->expr()->and($membershipConditions)
            );

        $qb->andWhere(
            sprintf('NOT EXISTS (%s)', $subq->getSQL())
        );
    }

    private function updateQueryWithSegmentMembershipExclusion(array $segments, QueryBuilder $qb): void
    {
        if (0 === count($segments)) {
            // No segments so nothing to exclude
            return;
        }

        $subq = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('ll.lead_id', 'cl.lead_id'),
                    $qb->expr()->eq('ll.manually_removed', 0),
                    $qb->expr()->in('ll.leadlist_id', $segments)
                )
            );

        $qb->andWhere(
            sprintf('NOT EXISTS (%s)', $subq->getSQL())
        );
    }

    /**
     * Exclude contacts with any previous campaign history; this is mainly BC for pre 2.14.0 where the membership entry was deleted.
     */
    private function updateQueryWithHistoryExclusion($campaignId, QueryBuilder $qb): void
    {
        $subq = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'el')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('el.lead_id', 'll.lead_id'),
                    $qb->expr()->eq('el.campaign_id', (int) $campaignId)
                )
            );

        $qb->andWhere(
            sprintf('NOT EXISTS (%s)', $subq->getSQL())
        );
    }
}
