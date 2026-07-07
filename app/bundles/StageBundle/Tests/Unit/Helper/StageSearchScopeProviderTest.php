<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Unit\Helper;

use Mautic\StageBundle\Helper\StageSearchScopeProvider;
use Mautic\StageBundle\Model\StageModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class StageSearchScopeProviderTest extends TestCase
{
    private StageModel&MockObject $stageModel;

    private TranslatorInterface&MockObject $translator;

    private StageSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->stageModel = $this->createMock(StageModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                'mautic.project.searchcommand.name' => 'project:name',
                default => $key,
            });

        $this->stageModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ismine',
                'mautic.project.searchcommand.name',
            ]);

        $this->provider = new StageSearchScopeProvider(
            $this->stageModel,
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
