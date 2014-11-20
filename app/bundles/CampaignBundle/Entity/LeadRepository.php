<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Helper\GraphHelper;

/**
 * LeadRepository
 */
class LeadRepository extends EntityRepository
{
    /**
     * Get the details of leads added to a campaign
     *
     * @param      $campaignId
     * @param null $leads
     */
    public function getLeadDetails($campaignId, $leads = null)
    {
        $q = $this->_em->createQueryBuilder()
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
     * @param      $campaignId
     * @param null $eventId
     */
    public function getLeads($campaignId, $eventId = null)
    {
        $q = $this->_em->createQueryBuilder()
            ->from('MauticCampaignBundle:Lead', 'lc')
            ->select('lc, l')
            ->leftJoin('lc.campaign', 'c')
            ->leftJoin('lc.lead', 'l');
        $q->where(
            $q->expr()->eq('c.id', ':campaign')
        )->setParameter('campaign', $campaignId);

        if ($eventId != null) {
            $dq = $this->_em->createQueryBuilder();
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
     * COunt leads for a specific campaign
     *
     * @param      $campaignId
     * @return int
     */
    public function countLeads($campaignId)
    {
        $q = $this->createQueryBuilder('cl')
            ->select('count(cl.lead) as theCount');
        $q->where(
            $q->expr()->eq('cl.campaign', ':campaign')
        )->setParameter('campaign', $campaignId);

        $result = $q->getQuery()->getSingleResult();

        return (int) $result['theCount'];
    }

    /**
     * Fetch Lead stats for some period of time.
     *
     * @param integer $quantity of units
     * @param string $unit of time php.net/manual/en/class.dateinterval.php#dateinterval.props
     *
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStats($quantity, $unit)
    {
        $graphData = GraphHelper::prepareLineGraphData($quantity, $unit, array('viewed'));

        // Load points for selected period
        $q = $this->createQueryBuilder('cl');
        $q->select('cl.dateAdded');

        $q->andwhere($q->expr()->gte('cl.dateAdded', ':date'))
            ->setParameter('date', $graphData['fromDate'])
            ->orderBy('cl.dateAdded', 'ASC');

        $leads = $q->getQuery()->getArrayResult();

        // Count total until date
        $q2 = $this->createQueryBuilder('cl');
        $q2->select('count(cl.lead) as total');

        $q2->andwhere($q->expr()->lt('cl.dateAdded', ':date'))
            ->setParameter('date', $graphData['fromDate']);

        $total = $q2->getQuery()->getSingleResult();
        $total = (int) $total['total'];

        return GraphHelper::mergeLineGraphData($graphData, $leads, $unit, 0, 'dateAdded', null, false, $total);
    }
}
