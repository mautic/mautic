<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Acquia, Inc.
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Field;

use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldsWithUniqueIdentifierTest extends TestCase
{
    /**
     * @var MockObject|FieldList
     */
    private $fieldList;

    /**
     * @var FieldsWithUniqueIdentifier
     */
    private $fieldsWithUniqueIdentifier;

    protected function setUp()
    {
        parent::setUp();

        $this->fieldList                  = $this->createMock(FieldList::class);
        $this->fieldsWithUniqueIdentifier = new FieldsWithUniqueIdentifier($this->fieldList);
    }

    public function testCacheIsUsed()
    {
        $fields = ['cached fields'];
        $this->fieldList->expects($this->once())
            ->method('getFieldList')
            ->willReturn($fields);

        Assert::assertSame($fields, $this->fieldsWithUniqueIdentifier->getFieldsWithUniqueIdentifier(['isPublished' => false]));

        // The cache should be used on subsequent requests and a second call to getFieldList not made
        Assert::assertSame($fields, $this->fieldsWithUniqueIdentifier->getFieldsWithUniqueIdentifier(['isPublished' => false]));
    }

    public function testCacheIsNotUsed()
    {
        $fields = ['cached fields'];
        $this->fieldList->expects($this->exactly(2))
            ->method('getFieldList')
            ->willReturn($fields);

        Assert::assertSame($fields, $this->fieldsWithUniqueIdentifier->getLiveFields(['isPublished' => false]));

        // The cache should not be used on subsequent requests
        Assert::assertSame($fields, $this->fieldsWithUniqueIdentifier->getLiveFields(['isPublished' => false]));
    }
}
