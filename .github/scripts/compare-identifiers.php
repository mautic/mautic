<?php

declare(strict_types=1);

/**
 * Fails when an existing table changes shape in a way that is invisible in review.
 *
 * The mapping drivers assemble a composite identifier in the order its parts are
 * declared, and AttributeAndStaticPhpDriver reads attributes before loadMetadata().
 * Moving one part of a key to #[ORM\Id], or reordering the properties of a fully
 * attribute-mapped entity, reorders the PRIMARY KEY columns away from what every
 * existing installation has. The same silence applies to a table, column or join column
 * that quietly changes name: nothing in the diff says "this renames a table".
 *
 * Nothing else in CI sees any of it. doctrine:schema:validate runs with --skip-sync, so
 * the mapping is never compared against a real schema, and the test databases are built
 * from that same metadata, so they agree with the mapping whatever it says.
 *
 * Renames and reorderings of things that already exist are errors. Anything added or
 * removed is a deliberate schema change and is only reported.
 */
$beforeFile = $argv[1] ?? null;
$afterFile  = $argv[2] ?? null;

if (null === $beforeFile || null === $afterFile) {
    fwrite(STDERR, 'Usage: compare-identifiers.php <before.json> <after.json>'.PHP_EOL);

    exit(2);
}

$before = json_decode((string) file_get_contents($beforeFile), true, 512, JSON_THROW_ON_ERROR);
$after  = json_decode((string) file_get_contents($afterFile), true, 512, JSON_THROW_ON_ERROR);

$beforeEntities = $before['entities'];
$afterEntities  = $after['entities'];

/** @var list<array{string, string, list<string>, list<string>}> $reorderedKeys */
$reorderedKeys = [];
/** @var list<string> $renames */
$renames = [];
/** @var list<string> $reorderedIndexes */
$reorderedIndexes = [];
/** @var list<string> $other */
$other = [];

/**
 * @param array<string, list<string>> $was
 * @param array<string, list<string>> $now
 * @param list<string>                $reordered
 * @param list<string>                $other
 */
function compareIndexes(string $entity, string $kind, array $was, array $now, array &$reordered, array &$other): void
{
    foreach ($was as $name => $columns) {
        if (!isset($now[$name])) {
            $other[] = sprintf('  %s drops %s %s(%s)', $entity, $kind, $name, implode(', ', $columns));
        }
    }

    foreach ($now as $name => $columns) {
        if (!isset($was[$name])) {
            $other[] = sprintf('  %s adds %s %s(%s)', $entity, $kind, $name, implode(', ', $columns));

            continue;
        }

        if ($was[$name] === $columns) {
            continue;
        }

        $sortedWas = $was[$name];
        $sortedNow = $columns;
        sort($sortedWas);
        sort($sortedNow);

        if ($sortedWas === $sortedNow) {
            $reordered[] = sprintf(
                '  %s %s %s: (%s) -> (%s)',
                $entity,
                $kind,
                $name,
                implode(', ', $was[$name]),
                implode(', ', $columns)
            );

            continue;
        }

        $other[] = sprintf(
            '  %s changes %s %s from (%s) to (%s)',
            $entity,
            $kind,
            $name,
            implode(', ', $was[$name]),
            implode(', ', $columns)
        );
    }
}

foreach ($afterEntities as $entity => $now) {
    if (!isset($beforeEntities[$entity])) {
        $other[] = sprintf('  new entity %s', $entity);

        continue;
    }

    $was   = $beforeEntities[$entity];
    $short = substr((string) strrchr('\\'.$entity, '\\'), 1);

    if ($was['table'] !== $now['table']) {
        $renames[] = sprintf('  %s table: %s -> %s', $short, $was['table'], $now['table']);
    }

    if ($was['identifierColumns'] !== $now['identifierColumns']) {
        $sortedWas = $was['identifierColumns'];
        $sortedNow = $now['identifierColumns'];
        sort($sortedWas);
        sort($sortedNow);

        if ($sortedWas === $sortedNow) {
            $reorderedKeys[] = [$entity, $now['table'], $was['identifierColumns'], $now['identifierColumns']];
        } else {
            $other[] = sprintf(
                '  %s identifier changes from (%s) to (%s)',
                $short,
                implode(', ', $was['identifierColumns']),
                implode(', ', $now['identifierColumns'])
            );
        }
    }

    foreach ($now['columns'] as $field => $column) {
        if (!isset($was['columns'][$field])) {
            $other[] = sprintf('  %s adds column %s', $short, $column);

            continue;
        }

        if ($was['columns'][$field] !== $column) {
            $renames[] = sprintf('  %s::$%s column: %s -> %s', $short, $field, $was['columns'][$field], $column);
        }
    }

    foreach ($was['columns'] as $field => $column) {
        if (!isset($now['columns'][$field])) {
            $other[] = sprintf('  %s drops column %s', $short, $column);
        }
    }

    foreach ($was['joinColumns'] as $field => $joinColumns) {
        if (!isset($now['joinColumns'][$field])) {
            $other[] = sprintf('  %s drops join column %s', $short, implode(', ', $joinColumns));
        }
    }

    foreach ($now['joinColumns'] as $field => $joinColumns) {
        if (isset($was['joinColumns'][$field]) && $was['joinColumns'][$field] !== $joinColumns) {
            $renames[] = sprintf(
                '  %s::$%s join column: %s -> %s',
                $short,
                $field,
                implode(', ', $was['joinColumns'][$field]),
                implode(', ', $joinColumns)
            );
        }
    }

    compareIndexes($short, 'index', $was['indexes'], $now['indexes'], $reorderedIndexes, $other);
    compareIndexes($short, 'unique constraint', $was['uniqueConstraints'], $now['uniqueConstraints'], $reorderedIndexes, $other);
}

foreach ($beforeEntities as $entity => $was) {
    if (!isset($afterEntities[$entity])) {
        $other[] = sprintf('  removes entity %s', $entity);
    }
}

printf(
    'Compared %d entities against the base revision; %d mapped superclass(es) and %d embeddable(s) have no table of their own.%s',
    count($afterEntities),
    $after['skipped']['mappedSuperclass'],
    $after['skipped']['embeddable'],
    PHP_EOL
);

if ([] !== $other) {
    echo PHP_EOL, 'Schema changes (not an error):', PHP_EOL, implode(PHP_EOL, $other), PHP_EOL;
}

if ([] === $reorderedKeys && [] === $renames && [] === $reorderedIndexes) {
    echo PHP_EOL, 'OK: no existing table changed shape.', PHP_EOL;

    exit(0);
}

if ([] !== $reorderedKeys) {
    echo PHP_EOL, 'PRIMARY KEY column order changed - make the whole identifier attributes,', PHP_EOL;
    echo 'declared in the original order:', PHP_EOL, PHP_EOL;

    foreach ($reorderedKeys as [$entity, $table, $was, $now]) {
        printf('  %s (%s)%s', $entity, $table, PHP_EOL);
        printf('      was PRIMARY KEY(%s)%s', implode(', ', $was), PHP_EOL);
        printf('      now PRIMARY KEY(%s)%s', implode(', ', $now), PHP_EOL);
        echo PHP_EOL;
        printf('      Installed databases still have the old order. Either restore it in%s', PHP_EOL);
        printf('      the mapping, or keep the new one and ship this migration:%s%s', PHP_EOL, PHP_EOL);
        printf('          ALTER TABLE %s%s', $table, PHP_EOL);
        printf('              DROP PRIMARY KEY,%s', PHP_EOL);
        printf('              ADD PRIMARY KEY (%s);%s%s', implode(', ', $now), PHP_EOL, PHP_EOL);
    }

    echo 'Rebuilding a primary key rewrites the table and every secondary index, so it is', PHP_EOL;
    echo 'worth being sure the new order is wanted before choosing the migration.', PHP_EOL;
}

if ([] !== $renames) {
    echo PHP_EOL, 'Renamed, so installed databases would keep the old name - restore it, or', PHP_EOL;
    echo 'ship a migration that renames it:', PHP_EOL, PHP_EOL, implode(PHP_EOL, $renames), PHP_EOL;
}

if ([] !== $reorderedIndexes) {
    echo PHP_EOL, 'Index column order changed, which decides which queries the index can serve:', PHP_EOL;
    echo PHP_EOL, implode(PHP_EOL, $reorderedIndexes), PHP_EOL;
}

exit(1);
