<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Tag>
 */
class TagRepository extends CommonRepository
{
    /**
     * Delete orphan tags that are not associated with any lead.
     */
    public function deleteOrphans(): void
    {
        $connection   = $this->_em->getConnection();
        $queryBuilder = $connection->createQueryBuilder();

        $queryBuilder->delete(MAUTIC_TABLE_PREFIX.'lead_tags', 't')
            ->where('NOT EXISTS (SELECT 1 FROM '.MAUTIC_TABLE_PREFIX.'lead_tags_xref x WHERE x.tag_id = t.id)');

        if ($connection->createSchemaManager()->tablesExist([MAUTIC_TABLE_PREFIX.'companies_tags_xref'])) {
            $queryBuilder->andWhere('NOT EXISTS (SELECT 1 FROM '.MAUTIC_TABLE_PREFIX.'companies_tags_xref cx WHERE cx.tag_id = t.id)');
        }

        $queryBuilder->executeStatement();
    }

    /**
     * Get tag entities by name.
     *
     * @param string[] $tags
     *
     * @return Tag[]
     */
    public function getTagsByName(array $tags): array
    {
        if (empty($tags)) {
            return [];
        }

        $tags = $this->removeMinusFromTags($tags);
        $qb   = $this->createQueryBuilder('t', 't.tag');

        if ($tags) {
            $qb->where(
                $qb->expr()->in('t.tag', ':tags')
            )
                ->setParameter('tags', $tags);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Goes through each element in the array expecting it to be a tag label and removes the '-' character infront of it.
     * The minus character is used to identify that the tag should be removed.
     */
    public function removeMinusFromTags(array $tags): array
    {
        return array_map(fn ($val) => (str_starts_with($val, '-')) ? substr($val, 1) : $val, $tags);
    }

    /**
     * Check Lead tags by Ids.
     */
    public function checkLeadByTags(Lead $lead, $tags): bool
    {
        if (empty($tags)) {
            return false;
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->join('l', MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'x', 'l.id = x.lead_id')
            ->join('l', MAUTIC_TABLE_PREFIX.'lead_tags', 't', 'x.tag_id = t.id')
            ->where(
                $q->expr()->and(
                    $q->expr()->in('t.tag', ':tags'),
                    $q->expr()->eq('l.id', ':leadId')
                )
            )
            ->setParameter('tags', $tags, ArrayParameterType::STRING)
            ->setParameter('leadId', $lead->getId());

        return (bool) $q->executeQuery()->fetchOne();
    }

    /**
     * @param string $name
     *
     * @return Tag
     */
    public function getTagByNameOrCreateNewOne($name)
    {
        $tag = new Tag($name, true);

        /** @var Tag|null $existingTag */
        $existingTag = $this->findOneBy(
            [
                'tag' => $tag->getTag(),
            ]
        );

        return $existingTag ?? $tag;
    }

    /**
     * Add tags to leads.
     *
     * @param array<int> $leadIds
     * @param array<int> $tagIds
     *
     * @return array<mixed>
     */
    public function addTagsToLeads(array $leadIds, array $tagIds): array
    {
        return $this->updateTagsInLeads($leadIds, $tagIds);
    }

    /**
     * Update tags in leads.
     *
     * @param array<int> $leadIds
     * @param array<int> $tagIds
     *
     * @return array<mixed>
     */
    public function updateTagsInLeads(array $leadIds, array $tagIds, string $addOrRemove = 'add'): array
    {
        return $this->updateTagsInEntities($leadIds, $tagIds, Lead::class, $addOrRemove);
    }

    /**
     * Remove tags from leads.
     *
     * @param array<int> $leadIds
     * @param array<int> $tagIds
     *
     * @return array<mixed>
     */
    public function removeTagsFromLeads(array $leadIds, array $tagIds): array
    {
        return $this->updateTagsInLeads($leadIds, $tagIds, 'remove');
    }

    /**
     * Add tags to companies.
     *
     * @param array<int> $companyIds
     * @param array<int> $tagIds
     *
     * @return array<mixed>
     */
    public function addTagsToCompanies(array $companyIds, array $tagIds): array
    {
        return $this->updateTagsInCompanies($companyIds, $tagIds);
    }

    /**
     * Update tags in companies.
     *
     * @param array<int> $companyIds
     * @param array<int> $tagIds
     *
     * @return array<mixed>
     */
    public function updateTagsInCompanies(array $companyIds, array $tagIds, string $addOrRemove = 'add'): array
    {
        return $this->updateTagsInEntities($companyIds, $tagIds, Company::class, $addOrRemove);
    }

    /**
     * Remove tags from companies.
     *
     * @param array<int> $companyIds
     * @param array<int> $tagIds
     *
     * @return array<mixed>
     */
    public function removeTagsFromCompanies(array $companyIds, array $tagIds): array
    {
        return $this->updateTagsInCompanies($companyIds, $tagIds, 'remove');
    }

    /**
     * @param array<int> $entityIds
     * @param array<int> $tagIds
     *
     * @return array<mixed>
     */
    private function updateTagsInEntities(array $entityIds, array $tagIds, string $entityClass, string $addOrRemove = 'add'): array
    {
        $result = [];

        if (empty($entityIds) || empty($tagIds)) {
            return $result;
        }

        $tags = $this->getTagById($tagIds);

        if (empty($tags)) {
            return $result;
        }

        $batchSize = 100;
        $persisted = 0;

        foreach ($entityIds as $entityId) {
            $entity = null;
            if (Lead::class === $entityClass) {
                $entity = $this->_em->find(Lead::class, (int) $entityId);
            } elseif (Company::class === $entityClass) {
                $entity = $this->_em->find(Company::class, (int) $entityId);
            }

            if (!$entity instanceof Lead && !$entity instanceof Company) {
                continue;
            }

            foreach ($tags as $tag) {
                if ('add' === $addOrRemove) {
                    $entity->addTag($tag);
                } else {
                    $entity->removeTag($tag);
                }

                $result[(int) $entityId][$tag->getId()] = true;
            }

            $this->_em->persist($entity);

            if (0 === ++$persisted % $batchSize) {
                $this->_em->flush();
            }
        }

        if ($persisted > 0) {
            $this->_em->flush();
        }

        return $result;
    }

    /**
     * Get tags by Id.
     *
     * @param array<int>|int $tagIds
     *
     * @return array<mixed>
     */
    public function getTagById(array|int $tagIds): array
    {
        if (empty($tagIds)) {
            return [];
        }

        if (!is_array($tagIds)) {
            $tagIds = [$tagIds];
        }

        $qb         = $this->_em->getConnection()->createQueryBuilder();
        $tagsIdName = $qb->select('lt.id,lt.tag')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags', 'lt')
            ->where('lt.id IN (:tag)')
            ->setParameter('tag', $tagIds, ArrayParameterType::INTEGER)
            ->executeQuery()->fetchAllKeyValue();

        if (empty($tagsIdName)) {
            return [];
        }

        return $this->getTagsByName($tagsIdName);
    }
}
