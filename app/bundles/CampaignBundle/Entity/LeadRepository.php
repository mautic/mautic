<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\GraphHelper;

/**
 * LeadRepository
 */
class LeadRepository extends CommonRepository
{
    /**
     * Get the details of leads added to a campaign
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

        $return = array();
        foreach ($results as $r) {
            $return[$r['lead_id']][] = $r;
        }

        return $return;
    }

    /**
     * Get leads for a specific campaign
     *
     * @param $args
     *
     * @return array
     */
    public function getLeadsWithFields($args)
    {
        //DBAL
        $dq = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $dq->select('count(*) as count')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l');

        //Fix arguments if necessary
        $args = $this->convertOrmProperties('Mautic\\LeadBundle\\Entity\\Lead', $args);

        $sq = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $sq->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl');

        $expr = $sq->expr()->andX(
            $sq->expr()->eq('cl.lead_id', 'l.id'),
            $sq->expr()->eq('cl.manually_removed', ':false')
        );
        $dq->setParameter('false', false, 'boolean');

        if (isset($args['campaign_id'])) {
            $expr->add(
                $sq->expr()->eq('cl.campaign_id', (int) $args['campaign_id'])
            );
        }
        $sq->where($expr);

        $dq->andWhere(
            sprintf('EXISTS (%s)', $sq->getSQL())
        );

        //get a total count
        $result = $dq->execute()->fetchAll();
        $total  = $result[0]['count'];

        //now get the actual paginated results
        $this->buildOrderByClause($dq, $args);
        $this->buildLimiterClauses($dq, $args);

        $dq->resetQueryPart('select')
            ->select('l.*');

        $leads = $dq->execute()->fetchAll();

        return (!empty($args['withTotalCount'])) ?
            array(
                'count' => $total,
                'results' => $leads
            ) : $leads;
    }

    /**
     * Get leads for a specific campaign
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
     * Fetch Lead stats for some period of time.
     *
     * @param integer $quantity of units
     * @param string $unit of time php.net/manual/en/class.dateinterval.php#dateinterval.props
     * @param array $options
     *
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStats($quantity, $unit, $options = array())
    {
        $graphData = GraphHelper::prepareDatetimeLineGraphData($quantity, $unit, array('viewed'));

        // Load points for selected period
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select(
            sprintf('count(*) as count, DATE(cl.date_added) as date_added')
        )
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl');

        $utc = new \DateTimeZone('UTC');
        $graphData['fromDate']->setTimezone($utc);

        $q->andwhere(
            $q->expr()->andX(
                $q->expr()->gte('cl.date_added', ':date'),
                $q->expr()->eq('cl.manually_removed', ':false')
            )
        )
            ->setParameter('date', $graphData['fromDate']->format('Y-m-d H:i:s'))
            ->setParameter('false', false, 'boolean')
            ->groupBy('DATE(cl.date_added)')
            ->orderBy('date_added', 'ASC');

        if (isset($options['campaign_id'])) {
            $q->andwhere($q->expr()->gte('cl.campaign_id', (int) $options['campaign_id']));
        }

        $datesAdded = $q->execute()->fetchAll();

        $format         = GraphHelper::getDateLabelFormat($unit);
        $formattedDates = array();
        $dt             = new DateTimeHelper();
        foreach ($datesAdded as &$date) {
            $dt->setDateTime($date['date_added'], 'Y-m-d', 'utc');
            $key                  = $dt->getDateTime()->format($format);
            $formattedDates[$key] = (int) $date['count'];
        }

        foreach ($graphData['labels'] as $key => $label) {
            $graphData['datasets'][0]['data'][$key] = (isset($formattedDates[$label])) ? $formattedDates[$label] : 0;
        }

        unset($graphData['fromDate']);

        return $graphData;
    }


    /**
     * Updates lead ID (e.g. after a lead merge)
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        // First check to ensure the $toLead doesn't already exist
        $results = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('cl.campaign_id')
            ->from(MAUTIC_TABLE_PREFIX . 'campaign_leads', 'cl')
            ->where('cl.lead_id = ' . $toLeadId)
            ->execute()
            ->fetchAll();
        $campaigns = array();
        foreach ($results as $r) {
            $campaigns[] = $r['campaign_id'];
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX . 'campaign_leads')
            ->set('lead_id', (int)$toLeadId)
            ->where('lead_id = ' . (int)$fromLeadId);

        if (!empty($campaigns)) {
            $q->andWhere(
                $q->expr()->notIn('campaign_id', $campaigns)
            )->execute();

            // Delete remaining leads as the new lead already belongs
            $this->getEntityManager()->getConnection()->createQueryBuilder()
                ->delete(MAUTIC_TABLE_PREFIX . 'campaign_leads')
                ->where('lead_id = ' . (int)$fromLeadId)
                ->execute();
        } else {
            $q->execute();
        }
    }
}
