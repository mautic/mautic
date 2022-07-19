<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
}
