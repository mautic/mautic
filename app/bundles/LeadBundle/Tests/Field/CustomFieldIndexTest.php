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

    protected function setUp(): void
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
     */
    public function testHasMatchingUniqueIdentifierIndex(string $object, string $field, string $fieldKey): void
    {
        $this->leadFieldMock->expects($this->once())
            ->method('getObject')
            ->willReturn($object);
        $this->fieldsWithUniqueIdentifierMock->expects($this->once())
            ->method('getLiveFields')
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
     * @return array<mixed>>
     */
    public function getHasMatchingUniqueIdentifierIndexProvider(): array
    {
        return [
            'Lead object'    => ['lead', 'email', 'email_key'],
            'Company object' => ['company', 'company_email', 'company_email_key'],
        ];
    }
}
