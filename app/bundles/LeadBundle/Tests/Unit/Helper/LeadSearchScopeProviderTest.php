<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Helper\LeadSearchScopeProvider;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LeadSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): LeadSearchScopeProvider
    {
        $leadModel  = $this->createMock(LeadModel::class);
        $fieldList  = $this->createMock(FieldList::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.lead.field.instagram' => 'Instagram',
                'mautic.lead.field.firstname' => 'First Name',
                'mautic.lead.lead.searchcommand.list' => 'segment',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $fieldList->method('getFieldList')
            ->willReturn([
                'instagram' => 'Instagram',
                'custom_field' => 'My Custom Field',
            ]);

        $leadModel->method('getCommandList')
            ->willReturn([
                'mautic.lead.lead.searchcommand.list',
                'instagram',
                'custom_field',
                'mautic.core.searchcommand.ismine',
            ]);

        return new LeadSearchScopeProvider($leadModel, $fieldList, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['instagram', 'custom_field'];
    }

    public function testSegmentCommandAppearsExactlyOnce(): void
    {
        $commands = array_column($this->getScopes(), 'command');

        $this->assertCount(1, array_filter($commands, static fn (string $command): bool => 'segment' === $command));
    }

    public function testCustomFieldIsIndentedAndSortedAfterKnownFields(): void
    {
        $scopes = $this->getScopes();

        $byCommand = array_column($scopes, null, 'command');

        $this->assertFalse($byCommand['instagram']['indent'] ?? false);
        $this->assertTrue($byCommand['custom_field']['indent'] ?? false);

        $commands = array_column($scopes, 'command');
        $this->assertGreaterThan(array_search('instagram', $commands, true), array_search('custom_field', $commands, true));
        $this->assertGreaterThan(array_search('segment', $commands, true), array_search('custom_field', $commands, true));
    }
}
