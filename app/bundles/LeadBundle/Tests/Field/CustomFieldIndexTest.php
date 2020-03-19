<?php

namespace Mautic\LeadBundle\Tests\Field;

use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\CustomFieldIndex;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Monolog\Logger;

final class CustomFieldIndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|IndexSchemaHelper
     */
    private $indexSchemaHelperMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Logger
     */
    private $loggerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FieldsWithUniqueIdentifier
     */
    private $fieldsWithUniqueIdentifierMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LeadField
     */
    private $leadFieldMock;

    /**
     * @var CustomFieldIndex
     */
    private $customFieldIndex;

    protected function setUp()
    {
        $this->indexSchemaHelperMock          = $this->createMock(IndexSchemaHelper::class);
        $this->loggerMock                     = $this->createMock(Logger::class);
        $this->fieldsWithUniqueIdentifierMock = $this->createMock(FieldsWithUniqueIdentifier::class);
        $this->customFieldIndex               = new CustomFieldIndex($this->indexSchemaHelperMock, $this->loggerMock, $this->fieldsWithUniqueIdentifierMock);
        $this->leadFieldMock                  = $this->createMock(LeadField::class);
    }

    /**
     * @dataProvider getHasMatchingUniqueIdentifierIndexProvider
     *
     * Test getting unique identifier if object is lead or company.
     *
     * @param string $object
     * @param string $field
     * @param string $fieldKey
     */
    public function testHasMatchingUniqueIdentifierIndex($object, $field, $fieldKey)
    {
        $this->indexSchemaHelperMock->expects($this->once())
            ->method('hasUniqueIdentifierIndex')
            ->with($this->leadFieldMock)
            ->willReturn(true);
        $this->leadFieldMock->expects($this->once())
            ->method('getIsUniqueIdentifier')
            ->willReturn(true);
        $this->leadFieldMock->expects($this->once())
            ->method('getObject')
            ->willReturn($object);
        $this->fieldsWithUniqueIdentifierMock->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
            ->with(['object' => $object])
            ->willReturn([$fieldKey => $field]);
        $this->indexSchemaHelperMock->expects($this->once())
            ->method('hasMatchingUniqueIdentifierIndex')
            ->with($this->leadFieldMock, [$fieldKey])
            ->willReturn(true);
        $this->customFieldIndex->hasMatchingUniqueIdentifierIndex($this->leadFieldMock);
    }

    /**
     * Provides data for testHasMatchingUniqueIdentifierIndex.
     *
     * @return array
     */
    public function getHasMatchingUniqueIdentifierIndexProvider()
    {
        return [
            'Lead object'    => ['lead', 'email', 'email_key'],
            'Company object' => ['company', 'company_email', 'company_email_key'],
        ];
    }
}
