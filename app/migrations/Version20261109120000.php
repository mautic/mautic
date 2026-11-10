<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\CoreBundle\Helper\Serializer;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;

final class Version20261109120000 extends PreUpAssertionMigration
{
    private const BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Preserve the historical company filter scope for existing segments';
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema): bool => !$this->hasUnmigratedCompanyFilters(),
            'All legacy company filters have already been migrated'
        );
    }

    public function up(Schema $schema): void
    {
        $table = $this->prefix.'lead_lists';
        $lastId = 0;
        do {
            $rows = $this->connection->executeQuery(
                'SELECT id, filters FROM '.$table.' WHERE id > :last_id AND filters LIKE :filter ORDER BY id ASC LIMIT '.self::BATCH_SIZE,
                [
                    'last_id' => $lastId,
                    'filter'  => '%s:6:"object";s:7:"company"%',
                ]
            )->fetchAllAssociative();

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                try {
                    $filters = Serializer::decode($row['filters']);
                } catch (\ErrorException|\InvalidArgumentException) {
                    $this->write(sprintf('<comment>Skipping segment %d: filters cannot be decoded</comment>', $lastId));

                    continue;
                }

                if (!is_array($filters)) {
                    continue;
                }

                $changed = false;
                foreach ($filters as $index => $filter) {
                    if (!is_array($filter) || 'company' !== ($filter['object'] ?? null)) {
                        continue;
                    }

                    $filters[$index][ContactSegmentFilterCrate::LEGACY_COMPANY_FILTER_SCOPE] = ContactSegmentFilterCrate::LEGACY_COMPANY_FILTER_SCOPE_VALUE;
                    $changed = true;
                }

                if ($changed) {
                    $this->addSql(
                        'UPDATE '.$table.' SET filters = :filters WHERE id = :id',
                        [
                            'filters' => serialize($filters),
                            'id'      => $row['id'],
                        ]
                    );
                }
            }
        } while (self::BATCH_SIZE === count($rows));
    }

    private function hasUnmigratedCompanyFilters(): bool
    {
        $table = $this->prefix.'lead_lists';

        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM '.$table.' WHERE filters LIKE :filter LIMIT 1',
            ['filter' => '%s:6:"object";s:7:"company"%']
        );
    }
}
