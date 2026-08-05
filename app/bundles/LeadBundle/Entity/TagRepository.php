<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Tag>
 */
class TagRepository extends CommonRepository
{
    private const LEAD_TABLE_NAME           = 'leads';

    private const LEAD_TAGS_XREF_TABLE_NAME = 'lead_tags_xref';

    private const TAG_ACTION_ADD            = 'add';

    /**
     * Delete an entity through the repository.
     *
     * @param Tag  $entity
     * @param bool $flush  true by default; use false if persisting in batches
     */
    public function deleteEntity($entity, $flush = true): void
    {
        if ($entity instanceof Tag && null !== $entity->getId()) {
            $this->deleteLeadAssociations((int) $entity->getId());
        }

        parent::deleteEntity($entity, $flush);
    }

    private function deleteLeadAssociations(int $tagId): void
    {
        $connection = $this->_em->getConnection();

        $connection->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.self::LEAD_TAGS_XREF_TABLE_NAME)
            ->where('tag_id = :tagId')
            ->setParameter('tagId', $tagId)
            ->executeStatement();

        if (!$connection->createSchemaManager()->tablesExist([MAUTIC_TABLE_PREFIX.Company::TAGS_XREF_TABLE_NAME])) {
            return;
        }

        $connection->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.Company::TAGS_XREF_TABLE_NAME)
            ->where('tag_id = :tagId')
            ->setParameter('tagId', $tagId)
            ->executeStatement();
    }

    /**
     * Delete orphan tags that are not associated with any lead.
     */
    public function deleteOrphans(): void
    {
        $connection = $this->_em->getConnection();
        $qb         = $connection->createQueryBuilder();
        $havingQb   = $connection->createQueryBuilder();
        $conditions = [];

        $havingQb->select('count(x.lead_id) as the_count')
            ->from(MAUTIC_TABLE_PREFIX.self::LEAD_TAGS_XREF_TABLE_NAME, 'x')
            ->where('x.tag_id = t.id');
        $conditions[] = sprintf('(%s)', $havingQb->getSQL()).' = 0';

        if ($connection->createSchemaManager()->tablesExist([MAUTIC_TABLE_PREFIX.Company::TAGS_XREF_TABLE_NAME])) {
            $companyHavingQb = $connection->createQueryBuilder();
            $companyHavingQb->select('count(cx.company_id) as the_count')
                ->from(MAUTIC_TABLE_PREFIX.Company::TAGS_XREF_TABLE_NAME, 'cx')
                ->where('cx.tag_id = t.id');
            $conditions[] = sprintf('(%s)', $companyHavingQb->getSQL()).' = 0';
        }

        $qb->select('t.id')
            ->from(MAUTIC_TABLE_PREFIX.Tag::TABLE_NAME, 't')
            ->having(implode(' AND ', $conditions));
        $delete = $qb->executeQuery()->fetchFirstColumn();

        if (count($delete)) {
            $qb->resetQueryParts();
            $qb->delete(MAUTIC_TABLE_PREFIX.Tag::TABLE_NAME)
                ->where(
                    $qb->expr()->in('id', ':deleteIds')
                )
                ->setParameter('deleteIds', $delete, ArrayParameterType::INTEGER)
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
        if ([] === $tags) {
            return [];
        }

        $tags = $this->removeMinusFromTags($tags);
        $qb   = $this->createQueryBuilder('t', 't.tag');

        if ([] !== $tags) {
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
        return array_map(fn ($val) => (str_starts_with((string) $val, '-')) ? substr((string) $val, 1) : $val, $tags);
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
            ->from(MAUTIC_TABLE_PREFIX.self::LEAD_TABLE_NAME, 'l')
            ->join('l', MAUTIC_TABLE_PREFIX.self::LEAD_TAGS_XREF_TABLE_NAME, 'x', 'l.id = x.lead_id')
            ->join('l', MAUTIC_TABLE_PREFIX.Tag::TABLE_NAME, 't', 'x.tag_id = t.id')
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

        $this->_em->flush();

        $entityIds = $this->getExistingEntityIds($entityIds, $entityClass);
        if (empty($entityIds)) {
            return $result;
        }

        $tagIds = array_map(static fn (Tag $tag): int => (int) $tag->getId(), $tags);
        if (self::TAG_ACTION_ADD === $addOrRemove) {
            $this->insertTagRelations($entityClass, $entityIds, $tagIds);
        } else {
            $this->deleteTagRelations($entityClass, $entityIds, $tagIds);
        }

        $this->_em->clear();

        foreach ($entityIds as $entityId) {
            foreach ($tagIds as $tagId) {
                $result[$entityId][$tagId] = true;
            }
        }

        return $result;
    }

    /**
     * @param array<int> $entityIds
     *
     * @return array<int>
     */
    private function getExistingEntityIds(array $entityIds, string $entityClass): array
    {
        $entityIds = array_values(array_unique(array_map(intval(...), $entityIds)));
        if (empty($entityIds)) {
            return [];
        }

        $tableName = $this->getTaggableEntityTableName($entityClass);

        $ids = $this->_em->getConnection()->createQueryBuilder()
            ->select('id')
            ->from(MAUTIC_TABLE_PREFIX.$tableName)
            ->where('id IN (:entityIds)')
            ->setParameter('entityIds', $entityIds, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchFirstColumn();

        return array_map(intval(...), $ids);
    }

    private function getTaggableEntityTableName(string $entityClass): string
    {
        return match ($entityClass) {
            Lead::class    => self::LEAD_TABLE_NAME,
            Company::class => Company::TABLE_NAME,
            default        => throw new \InvalidArgumentException(sprintf('Unsupported taggable entity "%s".', $entityClass)),
        };
    }

    /**
     * @param array<int> $entityIds
     * @param array<int> $tagIds
     */
    private function insertTagRelations(string $entityClass, array $entityIds, array $tagIds): void
    {
        $connection        = $this->_em->getConnection();
        $relationConfig    = $this->getTagRelationConfig($entityClass);
        $existingRelations = $this->getExistingTagRelations($relationConfig['table'], $relationConfig['entityColumn'], $entityIds, $tagIds);

        $connection->transactional(function () use ($connection, $relationConfig, $entityIds, $tagIds, $existingRelations): void {
            foreach ($entityIds as $entityId) {
                foreach ($tagIds as $tagId) {
                    if (isset($existingRelations[$this->getTagRelationKey($entityId, $tagId)])) {
                        continue;
                    }

                    $connection->insert(
                        MAUTIC_TABLE_PREFIX.$relationConfig['table'],
                        [
                            $relationConfig['entityColumn'] => $entityId,
                            'tag_id'                        => $tagId,
                        ]
                    );
                }
            }
        });
    }

    /**
     * @param array<int> $entityIds
     * @param array<int> $tagIds
     */
    private function deleteTagRelations(string $entityClass, array $entityIds, array $tagIds): void
    {
        $relationConfig = $this->getTagRelationConfig($entityClass);

        $this->_em->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.$relationConfig['table'])
            ->where($relationConfig['entityColumn'].' IN (:entityIds)')
            ->andWhere('tag_id IN (:tagIds)')
            ->setParameter('entityIds', $entityIds, ArrayParameterType::INTEGER)
            ->setParameter('tagIds', $tagIds, ArrayParameterType::INTEGER)
            ->executeStatement();
    }

    /**
     * @param array<int> $entityIds
     * @param array<int> $tagIds
     *
     * @return array<string, true>
     */
    private function getExistingTagRelations(string $relationTable, string $entityColumn, array $entityIds, array $tagIds): array
    {
        $rows = $this->_em->getConnection()->createQueryBuilder()
            ->select($entityColumn, 'tag_id')
            ->from(MAUTIC_TABLE_PREFIX.$relationTable)
            ->where($entityColumn.' IN (:entityIds)')
            ->andWhere('tag_id IN (:tagIds)')
            ->setParameter('entityIds', $entityIds, ArrayParameterType::INTEGER)
            ->setParameter('tagIds', $tagIds, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        $relations = [];
        foreach ($rows as $row) {
            $relations[$this->getTagRelationKey((int) $row[$entityColumn], (int) $row['tag_id'])] = true;
        }

        return $relations;
    }

    /**
     * @return array{table: string, entityColumn: string}
     */
    private function getTagRelationConfig(string $entityClass): array
    {
        return match ($entityClass) {
            Lead::class => [
                'table'        => self::LEAD_TAGS_XREF_TABLE_NAME,
                'entityColumn' => 'lead_id',
            ],
            Company::class => [
                'table'        => Company::TAGS_XREF_TABLE_NAME,
                'entityColumn' => 'company_id',
            ],
            default => throw new \InvalidArgumentException(sprintf('Unsupported taggable entity "%s".', $entityClass)),
        };
    }

    private function getTagRelationKey(int $entityId, int $tagId): string
    {
        return $entityId.':'.$tagId;
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
            ->from(MAUTIC_TABLE_PREFIX.Tag::TABLE_NAME, 'lt')
            ->where('lt.id IN (:tag)')
            ->setParameter('tag', $tagIds, ArrayParameterType::INTEGER)
            ->executeQuery()->fetchAllKeyValue();

        if (empty($tagsIdName)) {
            return [];
        }

        return $this->getTagsByName($tagsIdName);
    }

    /**
     * @return array<mixed>
     */
    protected function addCatchAllWhereClause($qb, $filter): array
    {
        $alias = $this->getTableAlias();

        return $this->addStandardCatchAllWhereClause($qb, $filter, [
            $alias.'.tag',
            $alias.'.description',
        ]);
    }
}
