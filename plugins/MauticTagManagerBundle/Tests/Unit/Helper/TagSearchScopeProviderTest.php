<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Tests\Unit\Helper;

use MauticPlugin\MauticTagManagerBundle\Entity\TagRepository;
use MauticPlugin\MauticTagManagerBundle\Helper\TagSearchScopeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TagSearchScopeProviderTest extends TestCase
{
    private TagRepository&MockObject $tagRepository;

    private TranslatorInterface&MockObject $translator;

    private TagSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->tagRepository = $this->createMock(TagRepository::class);
        $this->translator    = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $this->tagRepository->method('getSearchCommands')
            ->willReturn([
                'mautic.core.searchcommand.ids',
            ]);

        $this->provider = new TagSearchScopeProvider(
            $this->tagRepository,
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
        $this->assertCount(count(array_unique($commands)), $commands);
    }
}
