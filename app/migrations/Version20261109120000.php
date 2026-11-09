<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20261109120000 extends AbstractMauticMigration
{
    public function getDescription(): string
    {
        return 'Preserve the historical company filter scope for existing segments';
    }

    public function up(Schema $schema): void
    {
        $table = $this->prefix.'lead_lists';
        $rows  = $this->connection->executeQuery(
            'SELECT id, filters FROM '.$table.' WHERE filters LIKE :filter',
            ['filter' => '%company%']
        )->fetchAllAssociative();

        foreach ($rows as $row) {
            $filters = unserialize($row['filters'], ['allowed_classes' => false]);

            if (!is_array($filters)) {
                continue;
            }

            $changed = false;
            foreach ($filters as $index => $filter) {
                if (!is_array($filter) || 'company' !== ($filter['object'] ?? null)) {
                    continue;
                }

                $filters[$index]['object'] = 'company_all';
                $changed                   = true;
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
    }
}
