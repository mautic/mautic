<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\LeadBundle\Helper\SegmentSearchScopeProvider;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SegmentSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): SegmentSearchScopeProvider
    {
        $listModel  = $this->createMock(ListModel::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.lead.list.searchcommand.isglobal' => 'is:global',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $listModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.name',
                'mautic.lead.list.searchcommand.isglobal',
                'mautic.core.searchcommand.ismine',
            ]);

        return new SegmentSearchScopeProvider($listModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:global'];
    }
}
