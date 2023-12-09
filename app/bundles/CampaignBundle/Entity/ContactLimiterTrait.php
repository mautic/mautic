<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;

trait ContactLimiterTrait
{
    /**
     * @param string $alias
     * @param bool   $isCount
     */
    private function updateQueryFromContactLimiter($alias, DbalQueryBuilder $qb, ContactLimiter $contactLimiter, $isCount = false): void
    {
        $minContactId = $contactLimiter->getMinContactId();
        $maxContactId = $contactLimiter->getMaxContactId();
        if ($contactId = $contactLimiter->getContactId()) {
            $qb->andWhere(
                $qb->expr()->eq("$alias.lead_id", ':contactId')
            )
                ->setParameter('contactId', $contactId, ParameterType::INTEGER);
        } elseif ($contactIds = $contactLimiter->getContactIdList()) {
            $qb->andWhere(
                $qb->expr()->in("$alias.lead_id", ':contactIds')
            )
                ->setParameter('contactIds', $contactIds, ArrayParameterType::INTEGER);
        } elseif ($minContactId && $maxContactId) {
            $qb->andWhere(
                "$alias.lead_id BETWEEN :minContactId AND :maxContactId"
            )
                ->setParameter('minContactId', $minContactId, ParameterType::INTEGER)
                ->setParameter('maxContactId', $maxContactId, ParameterType::INTEGER);
        } elseif ($minContactId) {
            $qb->andWhere(
                $qb->expr()->gte("$alias.lead_id", ':minContactId')
            )
                ->setParameter('minContactId', $minContactId, ParameterType::INTEGER);
        } elseif ($maxContactId) {
            $qb->andWhere(
                $qb->expr()->lte("$alias.lead_id", ':maxContactId')
            )
                ->setParameter('maxContactId', $maxContactId, ParameterType::INTEGER);
        }

        if ($threadId = $contactLimiter->getThreadId()) {
            if ($maxThreads = $contactLimiter->getMaxThreads()) {
                if ($threadId <= $maxThreads) {
                    $qb->andWhere("MOD(($alias.lead_id + :threadShift), :maxThreads) = 0")
                        ->setParameter('threadShift', $threadId - 1, ParameterType::INTEGER)
                        ->setParameter('maxThreads', $maxThreads, ParameterType::INTEGER);
                }
            }
        }

        if (!$isCount && $limit = $contactLimiter->getBatchLimit()) {
            $qb->setMaxResults($limit);
        }
    }

    /**
     * @param string $alias
     * @param bool   $isCount
     */
    private function updateOrmQueryFromContactLimiter($alias, OrmQueryBuilder $qb, ContactLimiter $contactLimiter, $isCount = false): void
    {
        $minContactId = $contactLimiter->getMinContactId();
        $maxContactId = $contactLimiter->getMaxContactId();
        if ($contactId = $contactLimiter->getContactId()) {
            $qb->andWhere(
                $qb->expr()->eq("IDENTITY($alias.lead)", ':contact')
            )
                ->setParameter('contact', $contactId, ParameterType::INTEGER);
        } elseif ($contactIds = $contactLimiter->getContactIdList()) {
            $qb->andWhere(
                $qb->expr()->in("IDENTITY($alias.lead)", ':contactIds')
            )
                ->setParameter('contactIds', $contactIds, ArrayParameterType::INTEGER);
        } elseif ($minContactId && $maxContactId) {
            $qb->andWhere(
                "IDENTITY($alias.lead) BETWEEN :minContactId AND :maxContactId"
            )
                ->setParameter('minContactId', $minContactId, ParameterType::INTEGER)
                ->setParameter('maxContactId', $maxContactId, ParameterType::INTEGER);
        } elseif ($minContactId) {
            $qb->andWhere(
                $qb->expr()->gte("IDENTITY($alias.lead)", ':minContactId')
            )
                ->setParameter('minContactId', $minContactId, ParameterType::INTEGER);
        } elseif ($maxContactId) {
            $qb->andWhere(
                $qb->expr()->lte("IDENTITY($alias.lead)", ':maxContactId')
            )
                ->setParameter('maxContactId', $maxContactId, ParameterType::INTEGER);
        }

        if ($threadId = $contactLimiter->getThreadId()) {
            if ($maxThreads = $contactLimiter->getMaxThreads()) {
                $qb->andWhere("MOD((IDENTITY($alias.lead) + :threadShift), :maxThreads) = 0")
                    ->setParameter('threadShift', $threadId - 1, ParameterType::INTEGER)
                    ->setParameter('maxThreads', $maxThreads, ParameterType::INTEGER);
            }
        }

        if (!$isCount && $limit = $contactLimiter->getBatchLimit()) {
            $qb->setMaxResults($limit);
        }
    }
}
