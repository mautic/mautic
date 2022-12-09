<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<LeadCategory>
 */
class LeadCategoryRepository extends CommonRepository
{
    /**
     * @return array<mixed, array<string, mixed>>
     */
    public function getLeadCategories(Lead $lead): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('lc.id, lc.category_id, lc.date_added, lc.manually_added, lc.manually_removed, c.alias, c.title')
            ->from(MAUTIC_TABLE_PREFIX.'lead_categories', 'lc')
            ->join('lc', MAUTIC_TABLE_PREFIX.'categories', 'c', 'c.id = lc.category_id')
            ->where('lc.lead_id = :lead')
            ->andWhere('lc.manually_removed = 0')
            ->setParameter('lead', $lead->getId());

        $results = $q->executeQuery()
            ->fetchAllAssociative();

        $categories = [];
        foreach ($results as $category) {
            $categories[$category['category_id']] = $category;
        }

        return $categories;
    }

    /**
     * @return mixed[]
     */
    public function getUnsubscribedLeadCategories(Lead $lead): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('lc.id, lc.category_id, lc.date_added, lc.manually_added, lc.manually_removed, c.alias, c.title')
            ->from(MAUTIC_TABLE_PREFIX.'lead_categories', 'lc')
            ->join('lc', MAUTIC_TABLE_PREFIX.'categories', 'c', 'c.id = lc.category_id')
            ->where('lc.lead_id = :lead')
            ->andWhere('lc.manually_removed = 1')
            ->setParameter('lead', $lead->getId());

        $results = $q->executeQuery()->fetchAllAssociative();

        $categories = [];
        foreach ($results as $category) {
            $categories[$category['category_id']] = $category;
        }

        return $categories;
    }

    /**
     * @param string[] $types
     *
     * @return mixed[]
     */
    public function getSubscribedAndNewCategories(Lead $lead, array $types): array
    {
        $qb = $this->_em->getConnection()->createQueryBuilder();

        // Fetch the records from categories.
        $parentQ = clone $qb;
        $parentQ->select('c.id, c.title, c.alias, c.bundle');
        $parentQ->from(MAUTIC_TABLE_PREFIX.'categories', 'c');
        $parentQ->where('c.is_published = 1');
        $parentQ->andWhere($qb->expr()->in('c.bundle', ':bundles'));
        $parentQ->setParameter('bundles', $types, Connection::PARAM_STR_ARRAY);

        // Get the category ids for particular lead
        $subQ = clone $qb;
        $subQ->select('lc.category_id');
        $subQ->from(MAUTIC_TABLE_PREFIX.'lead_categories', 'lc');
        $subQ->where($qb->expr()->eq('lc.lead_id', ':leadId'));
        $subQ->andWhere($qb->expr()->eq('lc.manually_removed', 1));
        $subQ->setParameter('leadId', $lead->getId(), Types::INTEGER);

        // Add sub-query
        $parentQ->andWhere($qb->expr()->notIn('c.id', $subQ->getSQL()));

        // Add sub-query parameter.
        $parentQ->setParameter('leadId', $lead->getId(), Types::INTEGER);

        return $parentQ->execute()
            ->fetchAllAssociative();
    }
}
