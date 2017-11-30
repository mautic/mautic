<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Mautic\CoreBundle\Entity\DeprecatedInterface;

/**
 * Class DoctrineEventsSubscriber.
 */
class DoctrineEventsSubscriber implements EventSubscriber
{
    protected $tablePrefix;

    /**
     * @var
     */
    protected $deprecatedEntityTables = [];

    /**
     * DoctrineEventsSubscriber constructor.
     *
     * @param $tablePrefix
     */
    public function __construct($tablePrefix)
    {
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            'loadClassMetadata',
            'postGenerateSchema',
        ];
    }

    /**
     * @param LoadClassMetadataEventArgs $args
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        //in the installer
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

        if (false !== strpos($classMetadata->namespace, 'Mautic')) {
            //if in the installer, use the prefix set by it rather than what is cached

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
                if ($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY
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

    /**
     * @param GenerateSchemaEventArgs $args
     */
    public function postGenerateSchema(GenerateSchemaEventArgs $args)
    {
        $schema = $args->getSchema();
        $tables = $schema->getTables();

        foreach ($tables as $table) {
            if (in_array($table->getName(), $this->deprecatedEntityTables)) {
                // remove table from schema
                $schema->dropTable($table->getName());
            }
        }
    }
}
