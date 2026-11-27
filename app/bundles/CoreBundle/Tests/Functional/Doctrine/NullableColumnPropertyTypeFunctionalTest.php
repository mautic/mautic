<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class NullableColumnPropertyTypeFunctionalTest extends MauticMysqlTestCase
{
    /**
     * Checks that every nullable mapped column is backed by a property that accepts null, so a row holding NULL always loads.
     */
    public function testNullableColumnsMapToPropertiesThatAcceptNull(): void
    {
        $mismatches = [];

        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $metadata) {
            foreach ($metadata->getFieldNames() as $fieldName) {
                if (str_contains($fieldName, '.') || !$metadata->isNullable($fieldName)) {
                    continue;
                }

                $type = $this->findProperty($metadata, $fieldName)?->getType();

                if (null !== $type && !$type->allowsNull()) {
                    $mismatches[] = $metadata->getName().'::$'.$fieldName;
                }
            }
        }

        $this->assertSame([], $mismatches);
    }

    /**
     * Finds the property behind a mapped field, also in parent classes where it can be private.
     *
     * @param ClassMetadata<object> $metadata  the metadata of the entity
     * @param string                $fieldName the mapped field name
     *
     * @return \ReflectionProperty|null the property, or null when no class in the hierarchy declares it
     */
    private function findProperty(ClassMetadata $metadata, string $fieldName): ?\ReflectionProperty
    {
        for ($class = new \ReflectionClass($metadata->getName()); false !== $class; $class = $class->getParentClass()) {
            if ($class->hasProperty($fieldName)) {
                return $class->getProperty($fieldName);
            }
        }

        return null;
    }
}
