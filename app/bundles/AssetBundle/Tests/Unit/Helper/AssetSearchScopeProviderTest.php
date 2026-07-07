<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Unit\Helper;

use Mautic\AssetBundle\Helper\AssetSearchScopeProvider;
use Mautic\AssetBundle\Model\AssetModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class AssetSearchScopeProviderTest extends TestCase
{
    private AssetModel&MockObject $assetModel;

    private TranslatorInterface&MockObject $translator;

    private AssetSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->assetModel = $this->createMock(AssetModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.asset.asset.searchcommand.lang' => 'lang',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->assetModel->method('getCommandList')
            ->willReturn([
                'mautic.asset.asset.searchcommand.lang',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new AssetSearchScopeProvider(
            $this->assetModel,
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

    public function testGetScopesDoesNotDuplicatePinnedCommands(): void
    {
        $scopes = $this->provider->getScopes();

        $commands = array_column($scopes, 'command');

        $this->assertContains('lang', $commands);
        $this->assertSame(count($commands), count(array_unique($commands)));
    }
}
