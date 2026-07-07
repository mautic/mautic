<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Unit\Helper;

use MauticPlugin\MauticFocusBundle\Helper\FocusSearchScopeProvider;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FocusSearchScopeProviderTest extends TestCase
{
    private FocusModel&MockObject $focusModel;

    private TranslatorInterface&MockObject $translator;

    private FocusSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->focusModel = $this->createMock(FocusModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.category' => 'category',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->focusModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.category',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new FocusSearchScopeProvider(
            $this->focusModel,
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

        $this->assertContains('category', $commands);
        $this->assertCount(count(array_unique($commands)), $commands);
    }
}
