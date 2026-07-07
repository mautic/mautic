<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Unit\Helper;

use Mautic\ProjectBundle\Entity\ProjectRepository;
use Mautic\ProjectBundle\Helper\ProjectSearchScopeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectSearchScopeProviderTest extends TestCase
{
    private ProjectRepository&MockObject $projectRepository;

    private TranslatorInterface&MockObject $translator;

    private ProjectSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->projectRepository = $this->createMock(ProjectRepository::class);
        $this->translator        = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $this->projectRepository->method('getSearchCommands')
            ->willReturn([
                'mautic.core.searchcommand.ids',
            ]);

        $this->provider = new ProjectSearchScopeProvider(
            $this->projectRepository,
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
