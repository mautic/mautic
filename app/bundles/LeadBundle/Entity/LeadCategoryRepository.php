<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class LeadCategoryRepository.
 */
class LeadCategoryRepository extends CommonRepository
{
    public function getLeadCategories(Lead $lead)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('lc.id, lc.category_id, lc.date_added, lc.manually_added, lc.manually_removed, c.alias, c.title')
            ->from(MAUTIC_TABLE_PREFIX.'lead_categories', 'lc')
            ->join('lc', MAUTIC_TABLE_PREFIX.'categories', 'c', 'c.id = lc.category_id')
            ->where('lc.lead_id = :lead')->setParameter('lead', $lead->getId());
        $results = $q->execute()
            ->fetchAll();

        $categories = [];
        foreach ($results as $category) {
            $categories[$category['category_id']] = $category;
        }

        return $categories;
    }

    /**
     * @param string[] $types
     *
     * @return array<int, int>
     */
    public function getSubscribedAndNewCategoryIds(Lead $lead, array $types): array
    {
        return $this->getLeadCategoriesMapping($lead, $types, true);
    }

    /**
     * @param string[] $types
     *
     * @return array<int, int>
     */
    public function getNonAssociatedCategoryIdsForAContact(Lead $lead, array $types): array
    {
        return $this->getLeadCategoriesMapping($lead, $types);
    }

    /**
     * @param string[] $types
     *
     * @return array<int, int>
     */
    private function getLeadCategoriesMapping(Lead $lead, array $types, bool $manuallyRemoved = false): array
    {
        $qb = $this->_em->getConnection()->createQueryBuilder();

        // Fetch the records from categories.
        $parentQ = clone $qb;
        $parentQ->select('c.id');
        $parentQ->from(MAUTIC_TABLE_PREFIX.'categories', 'c');
        $parentQ->where('c.is_published = 1');
        $parentQ->andWhere($qb->expr()->in('c.bundle', ':bundles'));
        $parentQ->setParameter('bundles', $types, Connection::PARAM_STR_ARRAY);

        // Get the category ids for particular lead
        $subQ = clone $qb;
        $subQ->select('lc.category_id');
        $subQ->from(MAUTIC_TABLE_PREFIX.'lead_categories', 'lc');
        $subQ->where($qb->expr()->eq('lc.lead_id', ':leadId'));
        $subQ->setParameter('leadId', $lead->getId(), Types::INTEGER);

        if ($manuallyRemoved) {
            $subQ->andWhere($qb->expr()->eq('lc.manually_removed', 1));
        }

        // Add sub-query
        $parentQ->andWhere($qb->expr()->notIn('c.id', $subQ->getSQL()));

        // Add sub-query parameter.
        $parentQ->setParameter('leadId', $lead->getId(), Types::INTEGER);

        $leadCategories = $parentQ->execute()
            ->fetchAllAssociative();

        $leadCategoryList = [];
        foreach ($leadCategories as $category) {
            $id = (int) $category['id'];

            $leadCategoryList[$id] = $id;
        }

        return $leadCategoryList;
    }
}
