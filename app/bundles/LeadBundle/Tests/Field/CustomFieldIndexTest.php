<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field;

use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\CustomFieldIndex;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;

final class CustomFieldIndexTest extends \PHPUnit\Framework\TestCase
{
    private MockObject&IndexSchemaHelper $indexSchemaHelperMock;

    private MockObject&Logger $loggerMock;

    private MockObject&FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifierMock;

    private MockObject&LeadField $leadFieldMock;

    private CustomFieldIndex $customFieldIndex;

    protected function setUp(): void
    {
        $this->indexSchemaHelperMock          = $this->createMock(IndexSchemaHelper::class);
        $this->loggerMock                     = $this->createMock(Logger::class);
        $this->fieldsWithUniqueIdentifierMock = $this->createMock(FieldsWithUniqueIdentifier::class);
        $this->customFieldIndex               = new CustomFieldIndex($this->indexSchemaHelperMock, $this->loggerMock, $this->fieldsWithUniqueIdentifierMock);
        $this->leadFieldMock                  = $this->createMock(LeadField::class);
    }

    /**
     * Test getting unique identifier if object is lead or company.
     *
     * @dataProvider getHasMatchingUniqueIdentifierIndexProvider
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
     * @return iterable<string, string[]>
     */
    public function getHasMatchingUniqueIdentifierIndexProvider(): iterable
    {
        yield 'Lead object'    => ['lead', 'email', 'email_key'];
        yield 'Company object' => ['company', 'company_email', 'company_email_key'];
    }
}
