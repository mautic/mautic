<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<FieldGroup>
 */
final class FieldGroupRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'fg';
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator<FieldGroup>|array<FieldGroup>
     */
    public function getEntities(array $args = [])
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select($this->getTableAlias())
            ->from(FieldGroup::class, $this->getTableAlias());

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * Returns [alias => fieldCount] for all custom groups.
     *
     * @return array<string, int>
     */
    public function getFieldCountPerGroup(): array
    {
        $conn   = $this->_em->getConnection();
        $prefix = MAUTIC_TABLE_PREFIX;

        $rows = $conn->fetchAllAssociative(
            "SELECT fg.alias, COUNT(lf.id) AS field_count
             FROM {$prefix}lead_field_groups fg
             LEFT JOIN {$prefix}lead_fields lf ON lf.field_group = fg.alias
             GROUP BY fg.id, fg.alias"
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['alias']] = (int) $row['field_count'];
        }

        return $counts;
    }

    /**
     * Returns all existing group aliases (used to keep generated aliases unique).
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return $this->_em->getConnection()->fetchFirstColumn(
            'SELECT alias FROM '.MAUTIC_TABLE_PREFIX.'lead_field_groups'
        );
    }

    /**
     * Returns the highest field_order currently used (0 when there are no groups).
     */
    public function getMaxOrder(): int
    {
        $max = $this->_em->getConnection()->fetchOne(
            'SELECT MAX(field_order) FROM '.MAUTIC_TABLE_PREFIX.'lead_field_groups'
        );

        return (int) $max;
    }

    /**
     * Returns true when the group (by id) still has fields assigned.
     */
    public function hasFields(int $groupId): bool
    {
        $conn   = $this->_em->getConnection();
        $prefix = MAUTIC_TABLE_PREFIX;

        $alias = $conn->fetchOne(
            "SELECT alias FROM {$prefix}lead_field_groups WHERE id = ?",
            [$groupId]
        );

        if (!$alias) {
            return false;
        }

        $count = $conn->fetchOne(
            "SELECT COUNT(*) FROM {$prefix}lead_fields WHERE field_group = ?",
            [$alias]
        );

        return $count > 0;
    }
}
