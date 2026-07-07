<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Helper;

use Mautic\PointBundle\Helper\PointSearchScopeProvider;
use Mautic\PointBundle\Model\PointModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PointSearchScopeProviderTest extends TestCase
{
    private PointModel&MockObject $pointModel;

    private TranslatorInterface&MockObject $translator;

    private PointSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->pointModel = $this->createMock(PointModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                'mautic.project.searchcommand.name' => 'project:name',
                default => $key,
            });

        $this->pointModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ismine',
                'mautic.project.searchcommand.name',
            ]);

        $this->provider = new PointSearchScopeProvider(
            $this->pointModel,
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
