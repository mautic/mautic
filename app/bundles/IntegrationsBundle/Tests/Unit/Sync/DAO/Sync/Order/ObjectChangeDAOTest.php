<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO as ReportFieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class ObjectChangeDAOTest extends TestCase
{
    public function testGetUnchangedFields(): void
    {
        $fieldDAO = new FieldDAO('email', new NormalizedValueDAO('email', 'test@test.com'));

        $objectChangeDAO = new ObjectChangeDAO('foo', 'bar', 1, 'contact', 1);
        $objectChangeDAO->addField($fieldDAO, ReportFieldDAO::FIELD_UNCHANGED);

        $unchangedFields = $objectChangeDAO->getUnchangedFields();
        Assert::assertCount(1, $unchangedFields);
        Assert::assertArrayHasKey('email', $unchangedFields);
        Assert::assertSame($fieldDAO, $unchangedFields['email']);
    }

    public function testSetAndGetObjectMapping(): void
    {
        $objectChangeDAO = new ObjectChangeDAO('foo', 'bar', 1, 'contact', 1);
        $objectMapping   = new ObjectMapping();

        $objectChangeDAO->setObjectMapping($objectMapping);

        Assert::assertSame($objectMapping, $objectChangeDAO->getObjectMapping());
    }

    public function testThatFieldCanBeRemoved(): void
    {
        $objectChangeDAO = new ObjectChangeDAO('foo', 'bar', 1, 'contact', 1);
        $value           = new NormalizedValueDAO('type', 1);
        $field           = new FieldDAO('fieldName', $value);

        Assert::assertCount(0, $objectChangeDAO->getFields());
        $objectChangeDAO->addField($field);
        Assert::assertCount(1, $objectChangeDAO->getFields());
        $objectChangeDAO->removeField('fieldName');
        Assert::assertCount(0, $objectChangeDAO->getFields());
    }
}
