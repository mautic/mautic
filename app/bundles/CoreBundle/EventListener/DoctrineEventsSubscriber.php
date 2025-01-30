<?php

namespace Mautic\CoreBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Mautic\CoreBundle\Entity\DeprecatedInterface;

class DoctrineEventsSubscriber implements EventSubscriber
{
    private array $deprecatedEntityTables = [];

    /**
     * @param string $tablePrefix
     */
    public function __construct(
        private $tablePrefix,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            'loadClassMetadata',
            'postGenerateSchema',
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        // in the installer
        if (!defined('MAUTIC_TABLE_PREFIX') && empty($this->tablePrefix)) {
            return;
        } elseif (empty($this->tablePrefix)) {
            $this->tablePrefix = MAUTIC_TABLE_PREFIX;
        }

        /** @var \Doctrine\ORM\Mapping\ClassMetadataInfo $classMetadata */
        $classMetadata = $args->getClassMetadata();

        // Do not re-apply the prefix in an inheritance hierarchy.
        if ($classMetadata->isInheritanceTypeSingleTable() && !$classMetadata->isRootEntity()) {
            return;
        }

        if (str_contains($classMetadata->namespace, 'Mautic')) {
            // if in the installer, use the prefix set by it rather than what is cached

            // Prefix indexes
            $uniqueConstraints = [];
            if (isset($classMetadata->table['uniqueConstraints'])) {
                foreach ($classMetadata->table['uniqueConstraints'] as $name => $uc) {
                    $uniqueConstraints[$this->tablePrefix.$name] = $uc;
                }
            }

            $indexes = [];
            if (isset($classMetadata->table['indexes'])) {
                foreach ($classMetadata->table['indexes'] as $name => $uc) {
                    $indexes[$this->tablePrefix.$name] = $uc;
                }
            }

            // Prefix the table
            $classMetadata->setPrimaryTable(
                [
                    'name'              => $this->tablePrefix.$classMetadata->getTableName(),
                    'indexes'           => $indexes,
                    'uniqueConstraints' => $uniqueConstraints,
                ]
            );

            foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
                if (\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY == $mapping['type']
                    && isset($classMetadata->associationMappings[$fieldName]['joinTable']['name'])
                ) {
                    $mappedTableName                                                     = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
                    $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->tablePrefix.$mappedTableName;
                }
            }

            // Prefix sequences if supported by the DB platform
            if ($classMetadata->isIdGeneratorSequence()) {
                $newDefinition                 = $classMetadata->sequenceGeneratorDefinition;
                $newDefinition['sequenceName'] = $this->tablePrefix.$newDefinition['sequenceName'];

                $classMetadata->setSequenceGeneratorDefinition($newDefinition);
                $em = $args->getEntityManager();
                if (isset($classMetadata->idGenerator)) {
                    $sequenceGenerator = new \Doctrine\ORM\Id\SequenceGenerator(
                        $em->getConfiguration()->getQuoteStrategy()->getSequenceName(
                            $newDefinition,
                            $classMetadata,
                            $em->getConnection()->getDatabasePlatform()
                        ),
                        $newDefinition['allocationSize']
                    );
                    $classMetadata->setIdGenerator($sequenceGenerator);
                }
            }

            // Note deprecated entities so they can be removed from the schema before it's generated
            if ($classMetadata->reflClass->implementsInterface(DeprecatedInterface::class)) {
                $this->deprecatedEntityTables[] = $classMetadata->getTableName();
            }
        }
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();
        $tables = $schema->getTables();

        foreach ($tables as $table) {
            if (in_array($table->getName(), $this->deprecatedEntityTables)) {
                // remove table from schema
                $schema->dropTable($table->getName());
            }
            // Check tables for obsolete indexes.
            // Single column indexes that are the leftmost column of another index are obsolete.
            // That leftmost column is available to look up rows.
            // @see https://dev.mysql.com/doc/refman/5.7/en/multiple-column-indexes.html
            $pk              = $table->getPrimaryKey();
            $pk_first_column = $this->trimQuotes(strtolower($pk->getColumns()[0]));

            foreach ($table->getIndexes() as $id => $index) {
                $index_first_column = $this->trimQuotes(strtolower($index->getColumns()[0]));

                if (!$index->isPrimary() && 1 == count($index->getColumns()) && $index_first_column === $pk_first_column) {
                    $table->dropIndex($id);
                }
            }
        }
    }

    /**
     * Trim quotes from the identifier.
     */
    private function trimQuotes(string $identifier): string
    {
        return str_replace(['`', '"', '[', ']'], '', $identifier);
    }
}
