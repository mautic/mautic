<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Helper\CompanySearchScopeProvider;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CompanySearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): CompanySearchScopeProvider
    {
        $companyModel = $this->createMock(CompanyModel::class);
        $fieldList    = $this->createMock(FieldList::class);
        $translator   = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.lead.field.companyname' => 'Company Name',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $fieldList->method('getFieldList')
            ->with(false, true, ['isPublished' => true, 'object' => 'company'])
            ->willReturn([
                'companyindustry' => 'Industry',
            ]);

        $companyModel->method('getCommandList')
            ->willReturn([
                'companyname',
                'companyindustry',
                'mautic.core.searchcommand.ismine',
            ]);

        return new CompanySearchScopeProvider($companyModel, $fieldList, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['companyindustry'];
    }

    public function testCustomFieldIsIndentedAndSortedAfterKnownFields(): void
    {
        $scopes = $this->getScopes();

        $byCommand = array_column($scopes, null, 'command');

        $this->assertTrue($byCommand['companyindustry']['indent'] ?? false);

        $commands = array_column($scopes, 'command');
        $this->assertGreaterThan(array_search('is:mine', $commands, true), array_search('companyindustry', $commands, true));
    }
}
