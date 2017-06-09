<?php

/*
 * @copyright   2017 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class FocusCampaignRepository extends CommonRepository
{
    /**
     * @param type $focusId
     *
     * @return type
     */
    public function checkFocusCampagin($focusId)
    {
        $q = $this->createQueryBuilder('f');
        $q->select('f')
            ->leftJoin('f.campaign', 'c')
            ->where('f.focus = :focusId')
            ->andWhere('c.isPublished = :isPublished')
            ->setParameter('focusId', $focusId)
            ->setParameter('isPublished', 1);

        $result = $q->getQuery()->getResult();

        return (!empty($result)) ? true : false;
    }

    public function focusCampaignLeadFocus($focusId, $leadId)
    {
        $q = $this->createQueryBuilder('f');
        $q->select('f')
            ->where('f.focus = :focusId')
            ->andWhere('f.lead = :leadId')
            ->setParameter('focusId', $focusId)
            ->setParameter('leadId', $leadId);
    }

    public function campaignLeadInFocus($focusId, $leadId)
    {
        $q      = $this->focusCampaignLeadFocus($focusId, $leadId);
        $result = $q->getQuery()->getResult();

        return (!empty($result)) ? true : false;
    }

    public function eventLogFromFocusLeads($focusid, $leadid)
    {
        $result = $this->findOneBy(['focus' => $focusid, 'lead' => $leadid], ['id' => 'DESC']);

        return (!empty($result) && is_object($result)) ? $result->getLeadEventLog() : false;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'fc';
    }
}
