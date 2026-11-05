<?php

declare(strict_types=1);

/**
 * Prints the physical shape of every entity's table as JSON: primary key, column names,
 * and index columns, all in declaration order.
 *
 * Run against two revisions and compare with compare-identifiers.php to catch schema
 * drift that no other check sees. Needs no database connection.
 */
// Keep stdout pure JSON.
ini_set('display_errors', 'stderr');

// Pin the platform version so DBAL never opens a connection to detect it. AppKernel
// only does this itself when Mautic is not installed, so without it this would need a
// reachable database on any machine that has a config/local.php. The mapping does not
// depend on the value.
define('MAUTIC_DB_SERVER_VERSION', '8.4');

// The same bootstrap bin/console uses: it loads .env and fills in the environment the
// kernel expects, including MAUTIC_TABLE_PREFIX. Booting the kernel without it warns.
require __DIR__.'/../../app/config/bootstrap.php';

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;

$kernel = new AppKernel('prod', false);
$kernel->boot();

$registry = $kernel->getContainer()->get('doctrine');
assert($registry instanceof ManagerRegistry);

$entityManager = $registry->getManager();
assert($entityManager instanceof EntityManagerInterface);

/**
 * @param array<int|string, array<string, mixed>> $definitions
 *
 * @return array<array-key, list<string>>
 */
function columnsByName(array $definitions): array
{
    $out = [];
    foreach ($definitions as $name => $definition) {
        $columns = array_values((array) ($definition['columns'] ?? []));

        $out[(string) $name] = array_map(static fn (mixed $column): string => (string) $column, $columns);
    }
    ksort($out);

    return $out;
}

$out     = [];
$skipped = ['mappedSuperclass' => 0, 'embeddable' => 0];

foreach ($entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
    if (!$metadata instanceof ClassMetadata) {
        continue;
    }

    if ($metadata->isMappedSuperclass) {
        ++$skipped['mappedSuperclass'];

        continue;
    }

    if ($metadata->isEmbeddedClass) {
        ++$skipped['embeddable'];

        continue;
    }

    $columns = [];
    foreach ($metadata->fieldMappings as $field => $mapping) {
        $columns[$field] = is_array($mapping) ? $mapping['columnName'] : $mapping->columnName;
    }

    $joinColumns = [];
    foreach ($metadata->associationMappings as $field => $mapping) {
        // ORM 2 hands out arrays here and ORM 3 objects, but both answer to array
        // access. Casting the ORM 3 object with (array) instead would silently drop
        // isOwningSide, which is a method there rather than a property, and leave every
        // entity looking as if it had no join columns at all.
        if (!($mapping['isOwningSide'] ?? false)) {
            continue;
        }

        $names = [];
        foreach ($mapping['joinColumns'] ?? [] as $joinColumn) {
            $names[] = ($joinColumn['name'] ?? '?').' -> '.($joinColumn['referencedColumnName'] ?? '?');
        }

        if ([] !== $names) {
            $joinColumns[$field] = $names;
        }
    }

    ksort($columns);
    ksort($joinColumns);

    $out[$metadata->getName()] = [
        'table' => $metadata->getTableName(),
        // The actual PRIMARY KEY columns, not the field names: an identifier built from
        // an association is the join column, so "lead" is really "lead_id".
        'identifierColumns' => array_values($metadata->getIdentifierColumnNames()),
        'identifierFields'  => array_values($metadata->identifier),
        'columns'           => $columns,
        'joinColumns'       => $joinColumns,
        'indexes'           => columnsByName($metadata->table['indexes'] ?? []),
        'uniqueConstraints' => columnsByName($metadata->table['uniqueConstraints'] ?? []),
    ];
}

ksort($out);

echo json_encode(['skipped' => $skipped, 'entities' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
