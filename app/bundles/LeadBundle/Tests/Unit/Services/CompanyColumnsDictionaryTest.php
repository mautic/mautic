<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Services\CompanyColumnsDictionary;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CompanyColumnsDictionaryTest extends TestCase
{
    private FieldList&MockObject $fieldList;

    private TranslatorInterface&MockObject $translator;

    private CoreParametersHelper&MockObject $coreParametersHelper;

    private CompanyColumnsDictionary $dictionary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldList            = $this->createMock(FieldList::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $this->translator->method('trans')->willReturnArgument(0);

        $this->fieldList->expects($this->once())
            ->method('getFieldList')
            ->with(false, true, ['isPublished' => true, 'object' => 'company'])
            ->willReturn(['annual_revenue' => 'Annual Revenue']);

        $this->dictionary = new CompanyColumnsDictionary(
            $this->fieldList,
            $this->translator,
            $this->coreParametersHelper,
        );
    }

    public function testGetColumnsResolvesLabelsAndOrder(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('company_columns', [])
            ->willReturn([
                'companywebsite',
                'companyname',
            ]);

        $columns = $this->dictionary->getColumns();

        self::assertSame(['companywebsite' => 'mautic.company.website', 'companyname' => 'mautic.company.name'], $columns);
    }

    public function testGetFieldsMergesCoreAndCompanyCustomFields(): void
    {
        $fields = $this->dictionary->getFields();

        self::assertArrayHasKey('companyname', $fields);
        self::assertArrayHasKey('leadcount', $fields);
        self::assertArrayHasKey('annual_revenue', $fields);
        self::assertSame('Annual Revenue', $fields['annual_revenue']);
    }
}
