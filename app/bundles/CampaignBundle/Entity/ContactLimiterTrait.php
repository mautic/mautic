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
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;

trait ContactLimiterTrait
{
    /**
     * @param string           $alias
     * @param DbalQueryBuilder $qb
     * @param ContactLimiter   $contactLimiter
     * @param bool             $isCount
     */
    private function updateQueryFromContactLimiter($alias, DbalQueryBuilder $qb, ContactLimiter $contactLimiter, $isCount = false)
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

        if ($threadId = $contactLimiter->getThreadId()) {
            if ($maxThreads = $contactLimiter->getMaxThreads()) {
                if ($threadId <= $maxThreads) {
                    $qb->andWhere("MOD(($alias.lead_id + :threadShift), :maxThreads) = 0")
                        ->setParameter('threadShift', $threadId - 1)
                        ->setParameter('maxThreads', $maxThreads);
                }
            }
        }

        if (!$isCount && $limit = $contactLimiter->getBatchLimit()) {
            $qb->setMaxResults($limit);
        }
    }

    /**
     * @param string          $alias
     * @param OrmQueryBuilder $qb
     * @param ContactLimiter  $contactLimiter
     * @param bool            $isCount
     */
    private function updateOrmQueryFromContactLimiter($alias, OrmQueryBuilder $qb, ContactLimiter $contactLimiter, $isCount = false)
    {
        $minContactId = $contactLimiter->getMinContactId();
        $maxContactId = $contactLimiter->getMaxContactId();
        if ($contactId = $contactLimiter->getContactId()) {
            $qb->andWhere(
                $qb->expr()->eq("IDENTITY($alias.lead)", ':contact')
            )
                ->setParameter('contact', $contactId);
        } elseif ($contactIds = $contactLimiter->getContactIdList()) {
            $qb->andWhere(
                $qb->expr()->in("IDENTITY($alias.lead)", ':contactIds')
            )
                ->setParameter('contactIds', $contactIds, Connection::PARAM_INT_ARRAY);
        } elseif ($minContactId && $maxContactId) {
            $qb->andWhere(
                "IDENTITY($alias.lead) BETWEEN :minContactId AND :maxContactId"
            )
                ->setParameter('minContactId', $minContactId)
                ->setParameter('maxContactId', $maxContactId);
        } elseif ($minContactId) {
            $qb->andWhere(
                $qb->expr()->gte("IDENTITY($alias.lead)", ':minContactId')
            )
                ->setParameter('minContactId', $minContactId);
        } elseif ($maxContactId) {
            $qb->andWhere(
                $qb->expr()->lte("IDENTITY($alias.lead)", ':maxContactId')
            )
                ->setParameter('maxContactId', $maxContactId);
        }

        if ($threadId = $contactLimiter->getThreadId()) {
            if ($maxThreads = $contactLimiter->getMaxThreads()) {
                $qb->andWhere("MOD((IDENTITY($alias.lead) + :threadShift), :maxThreads) = 0")
                    ->setParameter('threadShift', $threadId - 1)
                    ->setParameter('maxThreads', $maxThreads);
            }
        }

        if (!$isCount && $limit = $contactLimiter->getBatchLimit()) {
            $qb->setMaxResults($limit);
        }
    }
}
