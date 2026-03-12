<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\CompanySegment;
use PHPUnit\Framework\TestCase;

class CompanySegmentTest extends TestCase
{
    /**
     * @dataProvider provideEmptyFields
     */
    public function testEntitySetsPublicNameAndAliasIfNameIsSet(?string $alias, ?string $publicName): void
    {
        $name   = 'The name';
        $entity = new CompanySegment();
        $entity->setName($name);
        $entity->setAlias($alias);
        $entity->setPublicName($publicName);

        self::assertSame($name, $entity->getPublicName());
        self::assertSame($name, $entity->getAlias());
    }

    public static function provideEmptyFields(): \Generator
    {
        yield 'null alias, null publicName' => [null, null];
        yield 'empty string alias, null publicName' => ['', null];
        yield 'null alias, empty string publicName' => [null, ''];
        yield 'empty string alias, empty string publicName' => ['', ''];
    }

    /**
     * @dataProvider provideNullOrEmptyString
     */
    public function testSettingAliasNullOrEmptyStringFetchesFromName(?string $value): void
    {
        $name   = 'The name';
        $entity = new CompanySegment();
        $entity->setName($name);
        $entity->setAlias($value);

        self::assertSame($name, $entity->getAlias());
    }

    /**
     * @dataProvider provideNullOrEmptyString
     */
    public function testSettingPublicNullOrEmptyStringFetchesFromName(?string $value): void
    {
        $name   = 'The name';
        $entity = new CompanySegment();
        $entity->setName($name);
        $entity->setPublicName($value);

        self::assertSame($name, $entity->getPublicName());
    }

    public static function provideNullOrEmptyString(): \Generator
    {
        yield 'null value' => [null];
        yield 'empty string' => [''];
    }

    public function testCloneResetsId(): void
    {
        $entity = new CompanySegment();
        $entity->setName('Test Segment');
        // Simulate a persisted entity by using reflection to set ID
        $reflection = new \ReflectionClass($entity);
        $property   = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, 123);

        $clonedEntity = clone $entity;

        self::assertNull($clonedEntity->getId());
        self::assertSame('Test Segment', $clonedEntity->getName());
    }

    public function testCloneResetsIsPublished(): void
    {
        $entity = new CompanySegment();
        $entity->setIsPublished(true);

        $clonedEntity = clone $entity;

        self::assertFalse($clonedEntity->isPublished());
    }

    public function testCloneResetsAlias(): void
    {
        $entity = new CompanySegment();
        $entity->setName('Test');
        $entity->setAlias('test-alias');

        $clonedEntity = clone $entity;

        // After cloning, setAlias('') is called which falls back to the name
        self::assertSame('Test', $clonedEntity->getAlias());
        self::assertNotSame('test-alias', $clonedEntity->getAlias());
    }

    public function testCloneResetsLastBuiltDate(): void
    {
        $entity = new CompanySegment();
        $entity->setLastBuiltDate(new \DateTime());

        $clonedEntity = clone $entity;

        self::assertNull($clonedEntity->getLastBuiltDate());
    }

    public function testCloneResetsCompaniesSegments(): void
    {
        $entity = new CompanySegment();
        // companiesSegments should be a new empty collection after cloning

        $clonedEntity = clone $entity;

        self::assertCount(0, $clonedEntity->getCompaniesSegments());
        self::assertNotSame($entity->getCompaniesSegments(), $clonedEntity->getCompaniesSegments());
    }
}
