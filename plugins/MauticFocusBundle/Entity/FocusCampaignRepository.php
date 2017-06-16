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

    /**
     * @param type $focusId
     * @param type $leadId
     *
     * @return type
     */
    public function campaignLeadInFocus($focusId, $leadId)
    {
        $q = $this->createQueryBuilder('f');
        $q->select('f')
            ->where('f.focus = :focusId')
            ->andWhere('f.lead = :leadId')
            ->setParameter('focusId', $focusId)
            ->setParameter('leadId', $leadId);

        $result = $q->getQuery()->getResult();

        return (!empty($result)) ? true : false;
    }

    public function focusIdsInCampaign()
    {
        $q = $this->createQueryBuilder('f');
        $q->select('IDENTITY(f.focus) AS focusid')
           ->groupBy('focusid');

        $result = $q->getQuery()->getResult();

        return (!empty($result)) ? array_column($result, 'focusid') : false;
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
