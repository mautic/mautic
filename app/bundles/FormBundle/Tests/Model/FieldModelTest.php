<?php

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Model\FieldModel;
use PHPUnit\Framework\TestCase;

class FieldModelTest extends TestCase
{
    public function testGenerateAlias()
    {
        $connection = $this->createMock(Connection::class);

        $platform = new class() {
            public function getReservedKeywordsList(): object
            {
                return new class() {
                    public function isKeyword(): void
                    {
                    }
                };
            }

            public function isKeyword(): void
            {
            }
        };

        $connection->method('getDatabasePlatform')
            ->willReturn($platform);

        $leadFieldModel = $this->createMock(\Mautic\LeadBundle\Model\FieldModel::class);
        $fieldModel     = new FieldModel($leadFieldModel);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $fieldModel->setEntityManager($entityManager);

        $aliases = [
            'existed_alias',
            'existed_alias_with_space',
        ];

        $strings = [
            'existed_alias1'            => 'existed alias',
            'not_existed'               => 'not existed',
            'existed_alias_with_space1' => 'existed alias with space',
            'alias_test'                => 'alias test',
        ];

        foreach ($strings as $expected => $string) {
            $alias = $fieldModel->generateAlias($string, $aliases);
            $this->assertEquals($expected, $alias);
        }
    }

    /**
     * @dataProvider dataProvider
     *
     * @param array<string, int> $properties
     */
    public function testHasChoices(string $type, array $properties, bool $result): void
    {
        $leadFieldModel = $this->createMock(\Mautic\LeadBundle\Model\FieldModel::class);
        $fieldModel     = new FieldModel($leadFieldModel);

        $field          = $this->createMock(Field::class);

        $field->expects($this->once())
            ->method('getType')
            ->willReturn($type);
        $field->expects($this->once())
            ->method('getProperties')
            ->willReturn($properties);

        $this->assertEquals($result, $fieldModel->hasChoices($field));
    }

    /**
     * @return array<int, mixed>
     */
    public function dataProvider(): iterable
    {
        yield ['string', [], false];
        yield ['string', ['multiple' => 0], false];
        yield ['string', ['multiple' => 1], true];
        yield ['checkboxgrp', [], true];
        yield ['checkboxgrp', ['multiple' => 0], true];
        yield ['checkboxgrp', ['multiple' => 1], true];
    }
}
