<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Helper;

use Mautic\PointBundle\Helper\PointInsightSearchScopeProvider;
use Mautic\PointBundle\Model\InsightModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointInsightSearchScopeProviderTest extends TestCase
{
    private InsightModel&MockObject $insightModel;

    private TranslatorInterface&MockObject $translator;

    private PointInsightSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->insightModel = $this->createMock(InsightModel::class);
        $this->translator   = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->insightModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new PointInsightSearchScopeProvider(
            $this->insightModel,
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

        $this->assertContains('is:mine', $commands);
        $this->assertSame(count($commands), count(array_unique($commands)));
    }
}
