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

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * LeadRepository.
 */
class LeadRepository extends CommonRepository
{
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
                    $q->expr()->in('l.campaign_id', $options['campaigns'])
                )
            );

        if (!empty($options['dataAddedLimit'])) {
            $q->andWhere(
                    $q->expr()->{$options['expr']}('l.date_added', ':dateAdded')
                )->setParameter('dateAdded', $options['dateAdded']);
        }

        $q->setParameter('leadId', $lead->getId());

        return (bool) $q->execute()->fetchColumn();
    }
}
