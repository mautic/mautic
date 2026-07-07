<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Unit\Helper;

use Mautic\DynamicContentBundle\Helper\DynamicContentSearchScopeProvider;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class DynamicContentSearchScopeProviderTest extends TestCase
{
    private DynamicContentModel&MockObject $dynamicContentModel;

    private TranslatorInterface&MockObject $translator;

    private DynamicContentSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->dynamicContentModel = $this->createMock(DynamicContentModel::class);
        $this->translator          = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.lang' => 'lang',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->dynamicContentModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.lang',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new DynamicContentSearchScopeProvider(
            $this->dynamicContentModel,
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
