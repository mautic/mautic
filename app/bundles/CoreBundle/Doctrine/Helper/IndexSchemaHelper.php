<?php

namespace Mautic\CoreBundle\Doctrine\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Index\IndexedColumn;
use Doctrine\DBAL\Types\TextType;
use Mautic\CoreBundle\Doctrine\Schema\AssetName;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\LeadBundle\Entity\LeadField;

final class IndexSchemaHelper
{
    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractMySQLPlatform>
     */
    private readonly \Doctrine\DBAL\Schema\AbstractSchemaManager $sm;

    private ?\Doctrine\DBAL\Schema\Table $table = null;

    /**
     * @var array
     */
    private $allowedColumns = [];

    private array $changedIndexes = [];

    private array $addedIndexes = [];

    private array $dropIndexes = [];

    public function __construct(
        private readonly Connection $db,
        private readonly ?string $prefix,
    ) {
        $this->sm = $this->db->createSchemaManager();
    }

    /**
     * @throws SchemaException
     */
    public function setName($name): static
    {
        if (!$this->sm->tablesExist([$this->prefix.$name])) {
            throw new SchemaException("Table {$name} does not exist!");
        }

        $this->table = $this->sm->introspectTableByUnquotedName($this->prefix.$name);

        return $this;
    }

    public function allowColumn($name): void
    {
        $this->allowedColumns[] = $name;
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function addIndex($columns, $name, $options = []): static
    {
        $textColumns = $this->getTextColumns($columns);

        if ([] === $textColumns) {
            return $this;
        }

        $index = new Index($this->prefix.$name, $textColumns, false, false, $options);

        if ($this->table->hasIndex($this->prefix.$name)) {
            $this->changedIndexes[] = $index;

            return $this;
        }

        $this->addedIndexes[] = $index;

        return $this;
    }

    /**
     * @param mixed  $columns
     * @param string $name
     * @param array  $options
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function dropIndex($columns, $name, $options = []): static
    {
        $textColumns = $this->getTextColumns($columns);

        $index = new Index($this->prefix.$name, $textColumns, false, false, $options);
        if ($this->table->hasIndex($this->prefix.$name)) {
            $this->dropIndexes[] = $index;
        }

        return $this;
    }

    public function executeChanges(): void
    {
        $platform = $this->db->getDatabasePlatform();

        $sql = [];
        foreach ($this->changedIndexes as $index) {
            $sql[] = $platform->getDropIndexSQL(AssetName::of($index), AssetName::of($this->table));
            $sql[] = $platform->getCreateIndexSQL($index, AssetName::of($this->table));
        }

        foreach ($this->dropIndexes as $index) {
            $sql[] = $platform->getDropIndexSQL(AssetName::of($index), AssetName::of($this->table));
        }

        foreach ($this->addedIndexes as $index) {
            $sql[] = $platform->getCreateIndexSQL($index, AssetName::of($this->table));
        }

        if (count($sql)) {
            foreach ($sql as $query) {
                $this->db->executeStatement($query);
            }
            $this->changedIndexes = [];
            $this->dropIndexes    = [];
            $this->addedIndexes   = [];
        }
    }

    /**
     * @throws SchemaException
     */
    public function hasIndex(LeadField $leadField): bool
    {
        $alias = $leadField->getAlias();
        $this->setName($leadField->getCustomFieldObject());

        return $this->table->hasIndex($this->prefix."{$alias}_search");
    }

    /**
     * @param array<mixed> $uniqueIdentifierColumns
     */
    public function hasMatchingUniqueIdentifierIndex(LeadField $leadField, array $uniqueIdentifierColumns): bool
    {
        $this->setName($leadField->getCustomFieldObject());

        $index = $this->table->getIndex($this->prefix.'unique_identifier_search');

        $columns = array_map(
            static fn (IndexedColumn $indexedColumn): string => AssetName::fromName($indexedColumn->getColumnName()),
            $index->getIndexedColumns()
        );

        asort($columns);
        asort($uniqueIdentifierColumns);

        return $columns === $uniqueIdentifierColumns;
    }

    /**
     * @throws SchemaException
     */
    public function hasUniqueIdentifierIndex(LeadField $leadField): bool
    {
        $this->setName($leadField->getCustomFieldObject());

        return $this->table->hasIndex($this->prefix.'unique_identifier_search');
    }

    /**
     * @param mixed $columns
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function getTextColumns($columns): array
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        foreach ($columns as $column) {
            if (!in_array($column, $this->allowedColumns)) {
                $columnSchema = $this->table->getColumn($column);

                $type = $columnSchema->getType();
                if (!$type instanceof TextType) {
                    $this->allowedColumns[] = AssetName::of($columnSchema);
                }
            }
        }

        // Indexes are only allowed on columns that are string
        $columns = array_intersect($columns, $this->allowedColumns);

        return $columns;
    }
}
