<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;

trait ContactLimiterTrait
{
    /**
     * @param string         $alias
     * @param QueryBuilder   $qb
     * @param ContactLimiter $contactLimiter
     */
    private function updateQueryFromContactLimiter($alias, QueryBuilder $qb, ContactLimiter $contactLimiter)
    {
        $minContactId = $contactLimiter->getMinContactId();
        $maxContactId = $contactLimiter->getMaxContactId();
        if ($contactId = $contactLimiter->getContactId()) {
            $qb->andWhere(
                $qb->expr()->eq("$alias.lead_id", ':contactId')
            )
                ->setParameter('contactId', $contactId);
        } elseif ($contactIds = $contactLimiter->getContactIdList()) {
            $qb->andWhere(
                $qb->expr()->in("$alias.lead_id", ':contactIds')
            )
                ->setParameter('contactIds', $contactIds, Connection::PARAM_INT_ARRAY);
        } elseif ($minContactId && $maxContactId) {
            $qb->andWhere(
                "$alias.lead_id BETWEEN :minContactId AND :maxContactId"
            )
                ->setParameter('minContactId', $minContactId)
                ->setParameter('maxContactId', $maxContactId);
        } elseif ($minContactId) {
            $qb->andWhere(
                $qb->expr()->gte("$alias.lead_id", ':minContactId')
            )
                ->setParameter('minContactId', $minContactId);
        } elseif ($maxContactId) {
            $qb->andWhere(
                $qb->expr()->lte("$alias.lead_id", ':maxContactId')
            )
                ->setParameter('maxContactId', $maxContactId);
        }

        if ($limit = $contactLimiter->getBatchLimit()) {
            $qb->setMaxResults($limit);
        }
    }
}
