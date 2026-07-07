<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Helper;

use Mautic\LeadBundle\Helper\CompanySearchScopeProvider;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class CompanySearchScopeProviderTest extends TestCase
{
    private CompanyModel&MockObject $companyModel;

    private FieldModel&MockObject $fieldModel;

    private TranslatorInterface&MockObject $translator;

    private CompanySearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->companyModel = $this->createMock(CompanyModel::class);
        $this->fieldModel   = $this->createMock(FieldModel::class);
        $this->translator   = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.lead.field.companyname' => 'Company Name',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->fieldModel->method('getFieldList')
            ->with(false, true, ['isPublished' => true, 'object' => 'company'])
            ->willReturn([
                'companyindustry' => 'Industry',
            ]);

        $this->companyModel->method('getCommandList')
            ->willReturn([
                'companyname',
                'companyindustry',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new CompanySearchScopeProvider(
            $this->companyModel,
            $this->fieldModel,
            $this->translator
        );
    }

    public function testGetScopesIncludesStandardFirst(): void
    {
        $scopes = $this->provider->getScopes();

        $this->assertSame('', $scopes[0]['command']);
        $this->assertSame('mautic.core.search.scope.standard', $scopes[0]['label']);
        $this->assertTrue($scopes[0]['default'] ?? false);
    }

    public function testGetScopesIncludesCompanyFields(): void
    {
        $scopes = $this->provider->getScopes();

        $commands = array_column($scopes, 'command');

        $this->assertContains('companyindustry', $commands);
        $this->assertSame(count($commands), count(array_unique($commands)));
    }
}
