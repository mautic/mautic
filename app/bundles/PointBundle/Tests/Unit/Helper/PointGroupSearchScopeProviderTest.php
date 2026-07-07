<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Helper;

use Mautic\PointBundle\Helper\PointGroupSearchScopeProvider;
use Mautic\PointBundle\Model\PointGroupModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointGroupSearchScopeProviderTest extends TestCase
{
    private PointGroupModel&MockObject $pointGroupModel;

    private TranslatorInterface&MockObject $translator;

    private PointGroupSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->pointGroupModel = $this->createMock(PointGroupModel::class);
        $this->translator      = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $this->pointGroupModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ids',
            ]);

        $this->provider = new PointGroupSearchScopeProvider(
            $this->pointGroupModel,
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

        $this->assertContains('ids', $commands);
        $this->assertSame(count($commands), count(array_unique($commands)));
    }
}
