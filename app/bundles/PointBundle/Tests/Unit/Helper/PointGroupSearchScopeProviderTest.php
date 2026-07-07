<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\PointBundle\Helper\PointGroupSearchScopeProvider;
use Mautic\PointBundle\Model\PointGroupModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointGroupSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): AbstractSearchScopeProvider
    {
        $pointGroupModel = $this->createMock(PointGroupModel::class);
        $translator      = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $pointGroupModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ids',
            ]);

        return new PointGroupSearchScopeProvider($pointGroupModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['ids'];
    }
}
