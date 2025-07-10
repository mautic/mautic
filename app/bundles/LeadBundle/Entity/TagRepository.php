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
        $qb       = $this->_em->getConnection()->createQueryBuilder();
        $havingQb = $this->_em->getConnection()->createQueryBuilder();

        $havingQb->select('count(x.lead_id) as the_count')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'x')
            ->where('x.tag_id = t.id');

        $qb->select('t.id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags', 't')
            ->having(sprintf('(%s)', $havingQb->getSQL()).' = 0');
        $delete = $qb->executeQuery()->fetchAssociative();

        if (count($delete)) {
            $qb->resetQueryParts();
            $qb->delete(MAUTIC_TABLE_PREFIX.'lead_tags')
                ->where(
                    $qb->expr()->in('id', $delete)
                )
                ->executeStatement();
        }
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
        $result = [];

        if (empty($leadIds) || empty($tagIds)) {
            return $result;
        }

        $tags = $this->getTagById($tagIds);

        if (empty($tags)) {
            return $result;
        }

        foreach ($leadIds as $leadId) {
            $lead = $this->_em->find(Lead::class, $leadId);
            foreach ($tags as $tag) {
                if ('add' === $addOrRemove) {
                    $lead->addTag($tag);
                } else {
                    $lead->removeTag($tag);
                }
                $result[$leadId][$tag->getId()] = true;
            }
            $this->_em->persist($lead);
            $this->_em->flush();
        }

        return $result;
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
