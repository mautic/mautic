<?php
namespace MauticPlugin\MauticCrmBundle\Tests\DoctrineExtensions;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class TablePrefix
{
    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * TablePrefix constructor.
     *
     * @param string $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = (string) $prefix;
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadataInfo $classMetadata */
        $classMetadata = $eventArgs->getClassMetadata();

        if (!$classMetadata->isInheritanceTypeSingleTable() || $classMetadata->getName() === $classMetadata->rootEntityName) {
            $table = $classMetadata->table;

            $this->addPrefixToIndexes($this->prefix, $table, 'indexes');
            $this->addPrefixToIndexes($this->prefix, $table, 'uniqueConstraints');

            $classMetadata->setPrimaryTable($table);
        }
    }

    private function addPrefixToIndexes($prefix, array &$table, $key)
    {
        if (!isset($table[$key])) {
            return;
        }

        $indexes = &$table[$key];

        foreach ($indexes as $name => $index)
        {
            $newName = uniqid($prefix . $name);
            $indexes[$newName] = $index;
            unset($indexes[$name]);
        }
    }
}