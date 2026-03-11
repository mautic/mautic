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
}
