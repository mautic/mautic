<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Unit\Helper;

use Mautic\AssetBundle\Helper\AssetSearchScopeProvider;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AssetSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): AbstractSearchScopeProvider
    {
        $assetModel = $this->createMock(AssetModel::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.asset.asset.searchcommand.lang' => 'lang',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $assetModel->method('getCommandList')
            ->willReturn([
                'mautic.asset.asset.searchcommand.lang',
                'mautic.core.searchcommand.ismine',
            ]);

        return new AssetSearchScopeProvider($assetModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['lang'];
    }
}
