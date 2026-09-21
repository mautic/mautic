<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\LeadBundle\Entity\TagRepository;
use MauticPlugin\MauticTagManagerBundle\Helper\TagSearchScopeProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TagSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): TagSearchScopeProvider
    {
        $tagRepository = $this->createMock(TagRepository::class);
        $translator    = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $tagRepository->method('getSearchCommands')
            ->willReturn([
                'mautic.core.searchcommand.ids',
            ]);

        return new TagSearchScopeProvider($tagRepository, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['ids'];
    }
}
