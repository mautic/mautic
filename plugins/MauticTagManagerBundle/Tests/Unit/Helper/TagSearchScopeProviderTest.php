<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use MauticPlugin\MauticTagManagerBundle\Entity\TagRepository;
use MauticPlugin\MauticTagManagerBundle\Helper\TagSearchScopeProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TagSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): TagSearchScopeProvider
    {
        // TagRepository is final; PHPStan cannot resolve createMock()'s return type for final classes.
        // @phpstan-ignore method.unresolvableReturnType
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
