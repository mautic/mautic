<?php

namespace Mautic\CoreBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Id\BigIntegerIdentityGenerator;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;
use Mautic\CoreBundle\Entity\DeprecatedInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsDoctrineListener(Events::loadClassMetadata)]
#[AsDoctrineListener(ToolEvents::postGenerateSchema)]
final class DoctrineEventsSubscriber
{
    private array $deprecatedEntityTables = [];

    /**
     * @param string $tablePrefix
     */
    public function __construct(
        #[Autowire(param: 'mautic.db_table_prefix')]
        private $tablePrefix,
    ) {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        if (defined('MAUTIC_INSTALLER') && defined('MAUTIC_TABLE_PREFIX')) {
            $this->tablePrefix = MAUTIC_TABLE_PREFIX;
        }
        // in the installer
        if (!defined('MAUTIC_TABLE_PREFIX') && empty($this->tablePrefix)) {
            return;
        }
        if (empty($this->tablePrefix)) {
            $this->tablePrefix = MAUTIC_TABLE_PREFIX;
        }

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $args->getClassMetadata();

        // Do not re-apply the prefix in an inheritance hierarchy or embedded tables.
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
                    if (isset($uc['options']['lengths']) && !$args->getEntityManager()->getConnection()->getDatabasePlatform()->supportsColumnLengthIndexes()) {
                        unset($uc['options']['lengths']);
                    }
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
                if (ClassMetadataInfo::MANY_TO_MANY == $mapping['type']
                    && isset($classMetadata->associationMappings[$fieldName]['joinTable']['name'])
                ) {
                    $mappedTableName                                                     = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
                    $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->tablePrefix.$mappedTableName;
                }
            }

            // Prefix sequences if supported by the DB platform
            if ($classMetadata->isIdGeneratorSequence()) {
                $definition      = $classMetadata->sequenceGeneratorDefinition;
                $newSequenceName = $this->generateSequenceName($definition['sequenceName']);

                // only redefine if it changed
                if ($definition['sequenceName'] != $newSequenceName) {
                    $definition['sequenceName'] = $newSequenceName;
                    $classMetadata->setSequenceGeneratorDefinition($definition);
                }

                $em = $args->getEntityManager();
                if (isset($classMetadata->idGenerator)) {
                    $sequenceGenerator = new SequenceGenerator(
                        $em->getConfiguration()->getQuoteStrategy()->getSequenceName(
                            $definition,
                            $classMetadata,
                            $em->getConnection()->getDatabasePlatform()
                        ),
                        $definition['allocationSize']
                    );
                    $classMetadata->setIdGenerator($sequenceGenerator);
                }
            } elseif ($classMetadata->isIdGeneratorIdentity() && $classMetadata->isRootEntity()) {
                $em = $args->getEntityManager();
                $platform = $em->getConnection()->getDatabasePlatform();
                if (DatabasePlatform::isPostgreSQL($platform)) {
                    $definition = ['sequenceName' => $classMetadata->getSequenceName($platform)];
                    if (!empty($classMetadata->table['quoted']) || !empty($classMetadata->fieldMappings[$classMetadata->getSingleIdentifierFieldName()]['quoted'])) {
                        $definition['quoted'] = true;
                    }
                    $sequenceName = $em->getConfiguration()->getQuoteStrategy()->getSequenceName($definition, $classMetadata, $platform);
                    $classMetadata->setIdGenerator($classMetadata->idGenerator instanceof BigIntegerIdentityGenerator
                        ? new BigIntegerIdentityGenerator($sequenceName)
                        : new IdentityGenerator($sequenceName));
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
        $platform = $args->getEntityManager()->getConnection()->getDatabasePlatform();
        $supportsIndexLengths = $platform->supportsColumnLengthIndexes();

        if (DatabasePlatform::isPostgreSQL($platform)) {
            $args->getEntityManager()->getConnection()->executeStatement(PostgreSqlCompatibilityFunctions::COLLATION_SQL);
            foreach ($tables as $table) {
                foreach ($table->getColumns() as $column) {
                    $typeDeclaration = $column->getType()->getSQLDeclaration($column->toArray(), $platform);
                    if (preg_match('/^(?:CHAR|VARCHAR|TEXT)\b/i', $typeDeclaration) && !$column->hasPlatformOption('collation')) {
                        $column->setPlatformOption('collation', 'mautic_unicode_ci');
                    }
                }
            }
        }

        foreach ($tables as $table) {
            if (in_array($table->getName(), $this->deprecatedEntityTables)) {
                // remove table from schema
                $schema->dropTable($table->getName());
            }
            // Check tables for obsolete indexes.
            // Single column indexes that are the leftmost column of another index are obsolete.
            // That leftmost column is available to look up rows.
            // @see https://dev.mysql.com/doc/refman/8.4/en/multiple-column-indexes.html
            $pk              = $table->getPrimaryKey();
            $pk_first_column = $this->trimQuotes(strtolower($pk->getColumns()[0]));

            foreach ($table->getIndexes() as $id => $index) {
                if (!$supportsIndexLengths && $index->hasOption('lengths')) {
                    $options = $index->getOptions();
                    unset($options['lengths']);
                    $table->dropIndex($id);
                    if ($index->isPrimary()) {
                        $table->setPrimaryKey($index->getColumns(), $index->getName());
                    } elseif ($index->isUnique()) {
                        $table->addUniqueIndex($index->getColumns(), $index->getName(), $options);
                    } else {
                        $table->addIndex($index->getColumns(), $index->getName(), $index->getFlags(), $options);
                    }
                }
                $index_first_column = $this->trimQuotes(strtolower($index->getColumns()[0]));

                if (!$index->isPrimary() && 1 === count($index->getColumns()) && $index_first_column === $pk_first_column) {
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

    private function generateSequenceName(string $sequenceName): string
    {
        // Remove prefix if present (test_, mautic_, etc.)
        if (str_starts_with($sequenceName, $this->tablePrefix)) {
            $sequenceName = substr($sequenceName, strlen($this->tablePrefix));
        }

        return $this->tablePrefix.$sequenceName;
    }
}
