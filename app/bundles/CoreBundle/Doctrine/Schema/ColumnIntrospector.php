<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Schema;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;

/**
 * Introspects a table's columns keyed by column name.
 *
 * DBAL 4 deprecated AbstractSchemaManager::listTableColumns() in favour of
 * introspectTableColumnsByUnquotedName(), but the two differ in more than name: the
 * replacement returns a plain list, while listTableColumns() returned the columns keyed
 * by lower-cased column name. Mautic looks columns up by name throughout, so the keys
 * are restored here rather than at each call site.
 *
 * The identifier's value is used rather than the name's toString(), which renders the
 * name quoted ("is_published") and would not match a plain column name.
 */
final class ColumnIntrospector
{
    /**
     * @return array<string, Column> keyed by lower-cased column name
     */
    public static function listColumns(AbstractSchemaManager $schemaManager, string $table): array
    {
        $columns = [];

        foreach ($schemaManager->introspectTableColumnsByUnquotedName($table) as $column) {
            $columns[strtolower($column->getObjectName()->getIdentifier()->getValue())] = $column;
        }

        return $columns;
    }
}
