<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\LeadBundle\Segment\OperatorOptions;

final class Version20250804003400 extends AbstractMauticMigration
{
    private const PATTERN = '%s:4:"type";s:11:"multiselect";%';

    private function getLeadListsTable(): string
    {
        return $this->prefix.'lead_lists';
    }

    /**
     * @return array<mixed>
     */
    private function getMultiselectLists(): array
    {
        $table = $this->getLeadListsTable();

        /** @var QueryBuilder $qb */
        $qb = $this->connection->createQueryBuilder();
        $qb->select('id', 'filters')
            ->from($table)
            ->where($qb->expr()->like('filters', ':pattern'))
            ->setParameter('pattern', self::PATTERN);

        return $qb->executeQuery()->fetchAllAssociative();
    }

    public function up(Schema $schema): void
    {
        $table = $this->getLeadListsTable();
        $lists = $this->getMultiselectLists();

        foreach ($lists as $listData) {
            $filters = unserialize($listData['filters'] ?? '', ['allowed_classes' => false]);
            if (!is_array($filters)) {
                continue;
            }

            $changed = false;
            foreach ($filters as &$filter) {
                if (($filter['type'] ?? '') !== 'multiselect') {
                    continue;
                }

                if (($filter['operator'] ?? '') === OperatorOptions::INCLUDING_ANY) {
                    $filter['operator'] = OperatorOptions::INCLUDING_ALL;
                    $changed            = true;
                } elseif (($filter['operator'] ?? '') === OperatorOptions::EXCLUDING_ANY) {
                    $filter['operator'] = OperatorOptions::EXCLUDING_ALL;
                    $changed            = true;
                }
            }
            unset($filter);

            if ($changed) {
                $this->connection->update(
                    $table,
                    ['filters' => serialize($filters)],
                    ['id'      => (int) $listData['id']]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $table = $this->getLeadListsTable();
        $lists = $this->getMultiselectLists();

        foreach ($lists as $listData) {
            $filters = unserialize($listData['filters'] ?? '', ['allowed_classes' => false]);
            if (!is_array($filters)) {
                continue;
            }

            $changed = false;
            foreach ($filters as &$filter) {
                if (($filter['type'] ?? '') !== 'multiselect') {
                    continue;
                }

                if (($filter['operator'] ?? '') === OperatorOptions::INCLUDING_ALL) {
                    $filter['operator'] = OperatorOptions::INCLUDING_ANY;
                    $changed            = true;
                } elseif (($filter['operator'] ?? '') === OperatorOptions::EXCLUDING_ALL) {
                    $filter['operator'] = OperatorOptions::EXCLUDING_ANY;
                    $changed            = true;
                }
            }
            unset($filter);

            if ($changed) {
                $this->connection->update(
                    $table,
                    ['filters' => serialize($filters)],
                    ['id'      => (int) $listData['id']]
                );
            }
        }
    }
}
