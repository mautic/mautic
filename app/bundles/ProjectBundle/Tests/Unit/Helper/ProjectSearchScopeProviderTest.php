<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\ProjectBundle\Entity\ProjectRepository;
use Mautic\ProjectBundle\Helper\ProjectSearchScopeProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProjectSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): ProjectSearchScopeProvider
    {
        // ProjectRepository is final; PHPStan cannot resolve createMock()'s return type for final classes.
        // @phpstan-ignore method.unresolvableReturnType
        $projectRepository = $this->createMock(ProjectRepository::class);
        $translator        = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $projectRepository->method('getSearchCommands')
            ->willReturn([
                'mautic.core.searchcommand.ids',
            ]);

        return new ProjectSearchScopeProvider($projectRepository, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['ids'];
    }
}
